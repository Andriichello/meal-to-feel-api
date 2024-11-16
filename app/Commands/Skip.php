<?php

namespace App\Commands;

use Telegram\Bot\Commands\Command;

/**
 * Class Skip.
 */
class Skip extends Command
{
    /**
     * The name of the Telegram command.
     */
    protected string $name = 'skip';

    /**
     * The Telegram command description.
     */
    protected string $description = 'Skips current action (if it\'s optional)';

    /**
     * Handle `skip` command.
     *
     * @return void
     */
    public function handle(): void
    {
        // skip current action

        $text = 'Action was skipped.';

        $this->replyWithMessage(compact('text'));
    }
}
