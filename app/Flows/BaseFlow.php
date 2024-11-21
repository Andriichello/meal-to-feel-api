<?php

namespace App\Flows;

use App\Enums\FlowName;
use App\Enums\FlowStatus;
use App\Enums\FlowStep;
use App\Models\Chat;
use App\Models\Message;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * Class BaseFlow.
 */
abstract class BaseFlow
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
        $active = $message->chat->activeFlow;

        if ($active?->status === FlowStatus::New->value) {
            if ($active->beg_id === $message->unique_id) {
                $this->initiate($message->chat, $api);

                $active->step = FlowStep::Initiation->value;
                $active->status = FlowStatus::Initiated->value;

                $active->save();
            }
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
        if (empty($this->initiation)) {
            return;
        }

        $api->sendMessage([
            'chat_id' => $chat->unique_id,
            'text' => $this->initiation,
        ]);
    }
}
