<?php

namespace App\Flows;

use App\Enums\FlowName;
use App\Enums\FlowStep;
use App\Models\Chat;
use App\Models\File;
use App\Models\Message;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

/**
 * Class AddMeal.
 */
class AddMeal extends BaseFlow
{
    /**
     * Current flow's name.
     *
     * @var FlowName
     */
    public static FlowName $name = FlowName::AddMeal;

    /**
     * Texts that can be used to start current flow.
     *
     * @var string[]
     */
    public static array $start = [
        'Add meal',
    ];

    /**
     * Handles the given message as a part of flow.
     *
     * @param Message $message
     * @param Api $api
     *
     * @return void
     * @throws TelegramSDKException
     */
    public function handle(Message $message, Api $api): void
    {
        parent::handle($message, $api);

        if ($message->type === 'text') {
            $step = match ($message->text) {
                'Save' => FlowStep::Save->value,
                'Cancel' => FlowStep::Cancel->value,
                'Set time' => FlowStep::SetTime->value,
                'Set date' => FlowStep::SetDate->value,
                default => null
            };

            $message->chat->activeFlow->step = $step;
            $message->chat->activeFlow->save();
        }

        if ($message->type === 'video') {
            $isUnsupported = true;
        }

        if ($message->type === 'document') {
            $mimeType = data_get($message->documentVariant(), 'mime_type');

            if ($mimeType && !Str::startsWith($mimeType, 'image/')) {
                $isUnsupported = true;
            }
        }

        if (isset($isUnsupported) && $isUnsupported) {
            $api->sendMessage([
                'chat_id' => $message->chat->unique_id,
                'text' => 'Only images are supported.',
            ]);
        }
    }

    /**
     * Initiates the given current flow
     * (may send an intro message, etc.).
     *
     * @param Chat $chat
     * @param Api $api
     *
     * @return void
     * @throws TelegramSDKException
     */
    public function initiate(Chat $chat, Api $api): void
    {
        $text = "New meal flow started.\n\n"
            . "You may start uploading photos right away.";

        $markup = Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button('Save'),
            ])
            ->row([
                Keyboard::button('Cancel'),
            ])
            ->row([
                Keyboard::button('Set time'),
            ])
            ->row([
                Keyboard::button('Set date'),
            ]);

        $api->sendMessage([
            'chat_id' => $chat->unique_id,
            'text' => $text,
            'reply_markup' => $markup,
        ]);
    }
}
