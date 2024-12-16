<?php

namespace App\Jobs;

use App\Helpers\MessageComposer;
use App\Models\Meal;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Laravel\Facades\Telegram;

/**
 * Class NotifyAboutSummary.
 */
class NotifyAboutSummary implements ShouldQueue
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
     * @var Meal
     */
    protected Meal $meal;

    /**
     * Create a new job instance.
     *
     * @param Meal $meal
     */
    public function __construct(Meal $meal)
    {
        $this->meal = $meal;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $message = MessageComposer::meal($this->meal);

        if (!empty($message)) {
            Telegram::bot()
                ->sendMessage([
                    'chat_id' => $message['chat_id'],
                    'text' => $message['text'],
                    'parse_mode' => $message['parse_mode'],
                ]);

            $this->meal->notified_at = now();
            $this->meal->save();
        }
    }
}
