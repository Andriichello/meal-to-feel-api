<?php

namespace App\Commands;

use Telegram\Bot\Commands\Command;

/**
 * Class Cancel.
 */
class Cancel extends Command
{
    /**
     * The name of the Telegram command.
     */
    protected string $name = 'cancel';

    /**
     * The Telegram command description.
     */
    protected string $description = 'Cancels any current action';

    /**
     * Handle `cancel` command.
     *
     * @return void
     */
    public function handle(): void
    {
        // cancel current action

        $text = 'Action was cancelled.';

        $this->replyWithMessage(compact('text'));
    }
}
