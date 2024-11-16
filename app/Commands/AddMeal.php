<?php

namespace App\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;

/**
 * Class AddMeal.
 */
class AddMeal extends Command
{
    /**
     * The name of the Telegram command.
     */
    protected string $name = 'add_meal';

    /**
     * The Telegram command description.
     */
    protected string $description = 'Add new meal entry';

    /**
     * Handle `add_meal` command.
     *
     * @return void
     */
    public function handle(): void
    {
        // create new meal entry...

        $text = "You’ve started a new meal entry!\n\n"
            . "Please specify the time of the meal\n\n"
            . 'You can also /skip or /cancel.';

        $markup = Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button('Upload photos'),
            ])
            ->row([
                Keyboard::inlineButton([
                    'text' => 'Set date',
                    'callback_data' => 'set_date',
                ]),
            ])
            ->row([
                Keyboard::button('Set time'),
            ]);

        $this->replyWithMessage([
            'text' => $text,
            'reply_markup' => $markup,
        ]);
    }
}
