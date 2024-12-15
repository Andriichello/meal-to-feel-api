<?php

namespace App\Flows;

use App\Enums\FlowName;
use App\Enums\FlowStatus;
use App\Enums\FlowStep;
use App\Enums\Role;
use App\Models\Chat;
use App\Models\Flow;
use App\Models\Message;
use App\Models\User;
use App\Queries\Models\UserQuery;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

/**
 * Class Start.
 */
class Start extends BaseFlow
{
    /**
     * Current flow's name.
     *
     * @var FlowName
     */
    public static FlowName $name = FlowName::Start;

    /**
     * Commands that can be used to start current flow.
     *
     * @var string[]
     */
    public static array $command = [
        '/start',
    ];

    /**
     * Texts that can be used to start current flow.
     *
     * @var string[]
     */
    public static array $start = [];

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

        $chat = $message->chat;
        $active = $chat->activeFlow;

        $active->end_id = $message->unique_id;
        $active->status = $active->command === FlowName::Start->value
            ? FlowStatus::Finished->value : FlowStatus::Cancelled->value;
        $active->save();

        if ($chat->user->isOfRole(Role::User)) {
            if (!$chat->user->trainer) {
                // initiate the new flow
                $active = new Flow();

                $active->chat_id = $message->chat_id;
                $active->user_id = $message->chat->user_id;
                $active->beg_id = $message->unique_id;
                $active->command = FlowName::SetTrainer;
                $active->status = FlowStatus::New->value;

                $active->save();

                $message->chat->load('activeFlow');

                /** @var Start $flow */
                $flow = app(Start::class);
                $flow->handle($message, $api);

                return;
            }
        }

        $api->sendMessage([
            'chat_id' => $message->chat_id,
            'text' => 'You may start uploading photos of your food right away or send /add_meal first.',
        ]);
    }

    /**
     * Returns keyboard markup.
     *
     * @param Chat $chat
     *
     * @return Keyboard|null
     */
    public function markup(Chat $chat): ?Keyboard
    {
        $active = $chat->activeFlow;

        $markup = Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true);

        $markup
            ->row([
                Keyboard::button('Add meal'),
            ]);

        return $markup;
    }
}
