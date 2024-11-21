<?php

namespace App\Http\Controllers;

use App\Helpers\BotFinder;
use App\Helpers\BotRecorder;
use App\Helpers\BotResolver;
use App\Models\Message;
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
        $bot = BotFinder::byTokenOrFail($token);

        $upd = $bot->getWebhookUpdate();
        $msg = $upd->getMessage();

        (new ConsoleOutput())->writeln(json_encode($upd->toArray(), JSON_PRETTY_PRINT) . "\n\n\n");

        if ($msg instanceof \Telegram\Bot\Objects\Message) {
            try {
                $from = BotResolver::user($msg);
                $chat = BotResolver::chat($msg, $from);
                $message = BotResolver::message($msg, $chat);

                if (!$message) {
                    throw new Exception('Failed to process message.');
                }

                if ($message->type === 'document') {
                    $document = BotResolver::document($bot, $message, $chat);

                    if ($document && $document->exists) {
                        $bot->sendMessage([
                            'chat_id' => $message->chat_id,
                            'text' => $message->edited_at
                                ? 'Send file as new message for it to be processed.'
                                : 'File received.'
                        ]);
                    }
                }

                if ($message->type === 'photo') {
                    $photo = BotResolver::photo($bot, $message, $chat);

                    if ($photo && $photo->exists) {
                        $bot->sendMessage([
                            'chat_id' => $message->chat_id,
                            'text' => $message->edited_at
                                ? 'Send image as new message for it to be processed.'
                                : 'Image received.'
                        ]);
                    }
                }

                if ($message->type === 'video') {
                    $video = BotResolver::video($bot, $message, $chat);

                    if ($video && $video->exists) {
                        $bot->sendMessage([
                            'chat_id' => $message->chat_id,
                            'text' => $message->edited_at
                                ? 'Send video as new message for it to be processed.'
                                : 'Video received.'
                        ]);
                    }

                    $bot->sendMessage([
                        'chat_id' => $message->chat_id,
                        'text' => 'Video uploads are not supported.'
                    ]);
                }

                $this->after($message);
            } catch (Throwable $e) {
                // report to BugSnag
                BotRecorder::update(
                    $upd,
                    'failed',
                    $user ?? null,
                    $chat ?? null,
                    $message ?? null,
                );
            }

            return response()->json(['message' => 'OK']);
        }

        BotRecorder::update($upd, 'skipped');

        return response()->json(['message' => 'OK']);
    }

    /**
     * Perform actions after processing the webhook update.
     *
     * @param Message $message
     * @return void
     */
    protected function after(Message $message): void
    {
        if ($message->chat) {
            //
        }
    }
}
