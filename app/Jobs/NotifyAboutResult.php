<?php

namespace App\Jobs;

use App\Helpers\MessageComposer;
use App\Models\Result;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Laravel\Facades\Telegram;

/**
 * Class NotifyAboutResult.
 */
class NotifyAboutResult implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * The name of the connection the job should be sent to.
     *
     * @var string|null
     */
    public ?string $connection = 'database';

    /**
     * The max number of seconds the job can be performed.
     *
     * @var int
     */
    public int $timeout = 10;

    /**
     * @var Result
     */
    protected Result $result;

    /**
     * Create a new job instance.
     *
     * @param Result $result
     */
    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $result = $this->result;
        $message = MessageComposer::result($result);

        if (empty($message['photo'])) {
            Telegram::bot()
                ->sendMessage([
                    'chat_id' => $message['chat_id'],
                    'text' => $message['text'],
                    'parse_mode' => $message['parse_mode'],
                ]);
        } else {
            Telegram::bot()
                ->sendPhoto([
                    'chat_id' => $message['chat_id'],
                    'photo' => $message['photo'],
                    'caption' => $message['text'],
                    'parse_mode' => $message['parse_mode'],
                ]);
        }

        $result->notified_at = now();
        $result->save();
    }
}
