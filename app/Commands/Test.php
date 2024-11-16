<?php

namespace App\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;

/**
 * Class Skip.
 */
class Test extends Command
{
    /**
     * The name of the Telegram command.
     */
    protected string $name = 'test';

    /**
     * The Telegram command description.
     */
    protected string $description = 'Test command.';

    /**
     * Handle `test` command.
     *
     * @return void
     */
    public function handle(): void
    {
        $text = 'Test command...';
        $markup = Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button('1'),
                Keyboard::button('2'),
                Keyboard::button('3'),
            ])
            ->row([
                Keyboard::button('4'),
                Keyboard::button('5'),
                Keyboard::button('6'),
            ])
            ->row([
                Keyboard::button('7'),
                Keyboard::button('8'),
                Keyboard::button('9'),
            ])
            ->row([
                Keyboard::button('0'),
            ]);


        $this->replyWithMessage([
            'text' => $text,
            'reply_markup' => $markup,
        ]);
    }
}
