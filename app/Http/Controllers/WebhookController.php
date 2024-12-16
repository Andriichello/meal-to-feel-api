<?php

namespace App\Http\Controllers;

use App\Helpers\BotFinder;
use App\Helpers\BotRecorder;
use App\Helpers\BotResolver;
use App\Helpers\FlowHelper;
use App\Models\Message;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Console\Output\ConsoleOutput;
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

        (new ConsoleOutput())->writeln(json_encode($upd->toArray(), JSON_PRETTY_PRINT) . "\n\n\n");

        if ($msg instanceof \Telegram\Bot\Objects\Message) {
            try {
                $from = BotResolver::user($msg);
                $chat = BotResolver::chat($msg, $from);
                $message = BotResolver::message($msg, $chat);

                if (!$message) {
                    throw new Exception('Failed to process message.');
                }

                if (in_array($message->type, ['document', 'photo', 'video'])) {
                    $hourlyCount = $chat->files()
                        ->where('files.created_at', '>', now()->subHour())
                        ->count();

                    if ($hourlyCount > $from->hourlyUploadsLimit()) {
                        $bot->sendMessage([
                            'chat_id' => $chat->unique_id,
                            'text' => "Upload ignored and will not be processed.",
                        ]);

                        $bot->sendMessage([
                            'chat_id' => $chat->unique_id,
                            'text' => "Unfortunately, you have reached the maximum number of files per hour. \n\n"
                                . "Please, try again in an hour.",
                        ]);

                        return response()->json(['message' => 'OK']);
                    }

                    $dailyCount = $chat->files()
                        ->where('files.created_at', '>', now()->setTime(0, 0, 1))
                        ->count();

                    if ($dailyCount > $from->dailyUploadsLimit()) {
                        $bot->sendMessage([
                            'chat_id' => $chat->unique_id,
                            'text' => "Upload ignored and will not be processed.",
                        ]);

                        $bot->sendMessage([
                            'chat_id' => $chat->unique_id,
                            'text' => "Unfortunately, you have reached the maximum number of files per day. \n\n"
                                . "Please, try again tomorrow.",
                        ]);

                        return response()->json(['message' => 'OK']);
                    }
                }

                if ($message->type === 'document') {
                    $document = BotResolver::document($bot, $message, $chat);
                }

                if ($message->type === 'photo') {
                    $photo = BotResolver::photo($bot, $message, $chat);
                }

                // if ($message->type === 'video') {
                //     $video = BotResolver::video($bot, $message, $chat);
                // }

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
     *
     * @return void
     * @throws TelegramSDKException
     */
    protected function after(Message $message): void
    {
        $helper = new FlowHelper();
        $helper->handle($message);
    }
}
