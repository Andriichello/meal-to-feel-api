<?php

namespace App\Http\Controllers;

use App\Helpers\BotFinder;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Console\Output\ConsoleOutput;
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
        $update = ($bot = BotFinder::byTokenOrFail($token))
            ->getWebhookUpdate();

        (new ConsoleOutput())->writeln(json_encode($update->toArray(), JSON_PRETTY_PRINT));

        $msg = $update->getMessage();

        if (!($msg instanceof \Telegram\Bot\Objects\Message)) {
            $msg = $update->getMessage()
                ->get('edited_message');
        }

        if ($msg instanceof \Telegram\Bot\Objects\Message) {
            try {
                $from = $this->user($msg);
                $chat = $this->chat($msg, $from);
                $message = $this->message($msg, $chat);

                if ($message->type === 'photo') {
                    $bot->sendMessage([
                        'chat_id' => $message->chat_id,
                        'text' => 'Image received.'
                    ]);
                }

                if ($message->type === 'video') {
                    $bot->sendMessage([
                        'chat_id' => $message->chat_id,
                        'text' => 'Video uploads are not supported.'
                    ]);
                }

                $processed = true;
            } catch (Throwable) {
                // report to BugSnag
            }
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Resolves user from the given update.
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
     * Resolves chat from the given update and user.
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
     * Resolves chat from the given update and user.
     * Will create a new record if such chat doesn't exist yet.
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
            $hasCommand = (bool) collect($msg->entities)
                ->get('entities', collect())
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
}
