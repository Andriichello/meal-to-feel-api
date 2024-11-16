<?php

namespace App\Http\Controllers;

use App\Helpers\BotFinder;
use App\Models\Chat;
use App\Models\File;
use App\Models\Message;
use App\Models\Update;
use App\Models\User;
use App\Repositories\FileRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;

/**
 * Class WebhookController.
 */
class WebhookController
{
    /**
     * Receives and handles Telegram Bot webhooks.
     *
     * @param string $token
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function __invoke(string $token): JsonResponse
    {
        $bot = BotFinder::byTokenOrFail($token);

        $upd = $bot->getWebhookUpdate();
        $msg = $upd->getMessage();

        if ($msg instanceof \Telegram\Bot\Objects\Message) {
            try {
                $from = $this->user($msg);
                $chat = $this->chat($msg, $from);
                $message = $this->message($msg, $chat);

                if (!$message) {
                    throw new Exception('Failed to process message');
                }

                if ($message->type === 'photo') {
                    $file = $this->loadPhoto($bot, $message, $chat);

                    if ($file && $file->exists) {
                        $bot->sendMessage([
                            'chat_id' => $message->chat_id,
                            'text' => $message->edited_at
                                ? 'Send image as new message for it to be processed.'
                                : 'Image received.'
                        ]);
                    }
                }

                if ($message->type === 'video') {
                    $bot->sendMessage([
                        'chat_id' => $message->chat_id,
                        'text' => 'Video uploads are not supported.'
                    ]);
                }
            } catch (Throwable) {
                // report to BugSnag
                $update = Update::query()
                    ->where('unique_id', $upd->updateId)
                    ->firstOrNew();

                $update->unique_id = $upd->updateId;
                $update->user_id = $from?->unique_id ?? $msg->from?->id;
                $update->chat_id = $chat?->unique_id ?? $msg->chat?->id;
                $update->message_id = $message?->unique_id ?? $msg->messageId;
                $update->type = $msg->objectType();
                $update->status = 'failed';
                $update->metadata = (object) $upd->toArray();

                $update->save();
            }

            return response()->json(['message' => 'OK']);
        }

        if (empty($update)) {
            $update = Update::query()
                ->where('unique_id', $upd->updateId)
                ->firstOrNew();

            $update->unique_id = $upd->updateId;
            $update->type = $upd->objectType();
            $update->status = 'skipped';
            $update->metadata = (object) $upd->toArray();

            $update->save();
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Resolves user from the given Telegram message.
     * Will create a new record if such user doesn't exist yet.
     * Will update an existing record if something differs
     * (`username`, `first_name`, `language_code`, `is_bot`, `is_premium`)
     *
     * @param \Telegram\Bot\Objects\Message $msg
     *
     * @return User|null
     */
    public function user(\Telegram\Bot\Objects\Message $msg): ?User
    {
        if (!$msg->from) {
            return null;
        }

        $user = User::query()
            ->withTelegram()
            ->where('unique_id', $msg->from->id)
            ->firstOrNew();

        $user->unique_id = $msg->from->id;
        $user->username = $msg->from->username;
        $user->name = $msg->from->firstName ?? $msg->from->lastName;
        $user->is_bot = $msg->from->isBot;
        $user->is_premium = (bool) data_get($msg->from, 'is_premium');
        $user->language = $msg->from->languageCode;

        $user->save();

        return $user;
    }

    /**
     * Resolves chat from the given Telegram message and user.
     * Will create a new record if such chat doesn't exist yet.
     *
     * @param \Telegram\Bot\Objects\Message $msg
     * @param User|null $from
     *
     * @return Chat|null
     */
    public function chat(\Telegram\Bot\Objects\Message $msg, ?User $from = null): ?Chat
    {
        if ($from === null && $msg->chat->username) {
            $from = User::query()
                ->withTelegram()
                ->where('username', $msg->chat->username)
                ->first();
        }

        $chat = Chat::query()
            ->where('unique_id', $msg->chat->id)
            ->firstOrNew();

        $chat->unique_id = $msg->chat->id;
        $chat->user_id = $from?->unique_id ?? $msg->from?->id;
        $chat->username = $msg->chat->username ?? $from?->username ?? $msg->from?->username;
        $chat->type = $msg->chat->type;

        $chat->save();

        return $chat;
    }

    /**
     * Resolves message from the given Telegram message and chat.
     * Will create a new record if such message doesn't exist yet.
     *
     * @param \Telegram\Bot\Objects\Message $msg
     * @param Chat|null $chat
     *
     * @return Message|null
     */
    public function message(\Telegram\Bot\Objects\Message $msg, ?Chat $chat = null): ?Message
    {
        $text = $msg->text ?? $msg->caption;
        $metadata = null;

        if ($text) {
            $hasCommand = (bool) $msg->get('entities', collect())
                ->contains('type', 'bot_command');

            $type = $hasCommand ? 'command' : 'text';
        }

        if ($msg->photo) {
            $type = 'photo';

            $metadata = [
                'photo' => $msg->photo,
            ];
        }

        if ($msg->video) {
            $type = 'video';

            $metadata = [
                ...($metadata ?? []),
                'video' => $msg->video,
            ];
        }

        if ($text || $msg->photo || $msg->video) {
            $type = $type ?? 'unknown';
            $chatId = $chat?->unique_id ?? $msg->chat->id;

            $message = Message::query()
                ->where('unique_id', $msg->messageId)
                ->where('chat_id', $chatId)
                ->firstOrNew();

            $message->unique_id = $msg->messageId;
            $message->chat_id = $chatId;
            $message->type = $type;
            $message->text = $text;
            $message->sent_at = Carbon::createFromTimestamp($msg->date);
            $message->edited_at = $msg->editDate
                ? Carbon::createFromTimestamp($msg->editDate) : null;

            if ($type === 'unknown') {
                $metadata = [
                    ...($metadata ?? []),
                    $msg->toArray(),
                ];
            }

            $message->metadata = $metadata ? (object) $metadata : null;

            $message->save();

            return $message;
        }

        return null;
    }

    /**
     * Load photo (from the given message) from Telegram
     * to Google Cloud Storage.
     *
     * @param Api $bot
     * @param Message $message
     * @param Chat $chat
     *
     * @return File|null
     * @throws TelegramSDKException
     * @throws Exception
     */
    protected function loadPhoto(Api $bot, Message $message, Chat $chat): ?File
    {
        $variants = $message->photoVariants();

        if (empty($variants)) {
            return null;
        }

        $variant = (array) end($variants);
        $file = $bot->getFile($variant);

        if (empty($file)) {
            return null;
        }

        $tempPath = Str::of(sys_get_temp_dir())
            ->finish('/')
            ->append(Str::random(6))
            ->finish('/')
            ->value();

        if ($file->filePath) {
            $fileName = Str::of($file->filePath)
                ->afterLast('/')
                ->value();

            if (!empty($fileName)) {
                $extension = Str::of($fileName)
                    ->after('.')
                    ->value();

                $tempPath .= $fileName;
            }
        }

        $resultPath = $bot->downloadFile($file, $tempPath);
        $attributes = [
            'file' => fopen($resultPath, 'r'),
            'context' => $message,
            'disk' => 'uploads',
            'disk_path' => '/' . File::slugFor($chat)
                . '/' . $chat->unique_id,
            'disk_name' => $fileName ?? null,
            'file_id' => $file->fileId,
            'unique_id' => $file->fileUniqueId,
            'path' => $file->filePath,
            'type' => mime_content_type($resultPath),
            'extension' => $extension ?? null,
            'size' => $file->fileSize,
        ];

        return (new FileRepository())
            ->create($attributes, true);
    }
}
