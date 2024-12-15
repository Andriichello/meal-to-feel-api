<?php

namespace App\Flows;

use App\Enums\FlowName;
use App\Enums\FlowStatus;
use App\Enums\FlowStep;
use App\Enums\Role;
use App\Models\Chat;
use App\Models\Meal;
use App\Models\Message;
use App\Models\User;
use App\Queries\Models\UserQuery;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;
use Throwable;

/**
 * Class SetTrainer.
 */
class SetTrainer extends BaseFlow
{
    /**
     * Current flow's name.
     *
     * @var FlowName
     */
    public static FlowName $name = FlowName::SetTrainer;

    /**
     * Commands that can be used to start current flow.
     *
     * @var string[]
     */
    public static array $command = [
        '/set_trainer',
    ];

    /**
     * Texts that can be used to start current flow.
     *
     * @var string[]
     */
    public static array $start = [
        'Set trainer',
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

        $chat = $message->chat;
        $active = $chat->activeFlow;

        if ($message->type === 'text') {
            $step = match ($message->text) {
                'Cancel' => FlowStep::Cancel,
                'Set trainer' => FlowStep::Initiation,
                default => null
            };

            if ($step) {
                $active->step = $step->value;
                $active->save();

                if ($active->step === FlowStep::Cancel->value) {
                    $active->status = FlowStatus::Cancelled;
                    $active->save();

                    $api->sendMessage([
                        'chat_id' => $chat->unique_id,
                        'text' => "Setting trainer was cancelled.",
                        'reply_markup' => $this->markup($chat),
                    ]);
                }
            } else {
                $username = $message->text;

                $trainer = User::query()
                    ->withRole(Role::Trainer)
                    ->where(function (UserQuery $query) use ($username) {
                        $query->where('username', $username)
                            ->orWhere('unique_id', $username);
                    })
                    ->first();

                if ($trainer) {
                    $chat->user->trainer_id = $trainer->id;
                    $chat->user->save();

                    $active->step = FlowStep::Save->value;
                    $active->status = FlowStatus::Finished->value;
                    $active->end_id = $message->unique_id;
                    $active->save();

                    $api->sendMessage([
                        'chat_id' => $chat->unique_id,
                        'text' => "From now on your trainer is: " . $username,
                        'reply_markup' => $this->markup($chat),
                    ]);
                } else {
                    $api->sendMessage([
                        'chat_id' => $chat->unique_id,
                        'text' => "Failed to find trainer with username: " . $username,
                        'reply_markup' => $this->markup($chat),
                    ]);
                }
            }
        }
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

        if ($active->step === FlowStep::Initiation->value) {
            $markup = Keyboard::make()
                ->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true)
                ->row([
                    Keyboard::button('Cancel'),
                ]);

            return $chat->user->trainer ? $markup : null;
        }

        if ($active->status === FlowStatus::Finished->value) {
            $markup = Keyboard::make()
                ->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true)
                ->row([
                    Keyboard::button('Add meal'),
                ]);

            return $chat->user->trainer ? $markup : null;
        }

        return null;
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
        $text = $chat->user->trainer
            ? "Your current trainer is: {$chat->user->trainer->username}."
            : "Currently you don't have a trainer set. "
                . "Trainer is required to use this bot.";

        $text .= "\n\n";
        $text .= "Please enter username of the trainer you want.";

        $api->sendMessage([
            'chat_id' => $chat->unique_id,
            'text' => $text,
            'reply_markup' => $this->markup($chat),
        ]);
    }
}
