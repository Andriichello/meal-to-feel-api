<?php

namespace App\Http\Controllers;

use App\Helpers\BotFinder;
use App\Helpers\BotRecorder;
use App\Helpers\BotResolver;
use Exception;
use Illuminate\Http\JsonResponse;
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
                $from = BotResolver::user($msg);
                $chat = BotResolver::chat($msg, $from);
                $message = BotResolver::message($msg, $chat);

                if (!$message) {
                    throw new Exception('Failed to process message.');
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
                    $bot->sendMessage([
                        'chat_id' => $message->chat_id,
                        'text' => 'Video uploads are not supported.'
                    ]);
                }
            } catch (Throwable) {
                // report to BugSnag
                BotRecorder::update(
                    $upd,
                    'failed',
                    $user ?? null,
                    $chat ?? null,
                    $message ?? null,
                );
            }

            $bot->processCommand($upd);

            return response()->json(['message' => 'OK']);
        }

        BotRecorder::update($upd, 'skipped');

        $bot->processCommand($upd);

        return response()->json(['message' => 'OK']);
    }
}
