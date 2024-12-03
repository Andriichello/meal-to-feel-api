<?php

namespace App\Flows;

use App\Enums\FlowName;
use App\Enums\FlowStatus;
use App\Enums\FlowStep;
use App\Models\Chat;
use App\Models\Meal;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;
use Throwable;

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

        $chat = $message->chat;
        $active = $chat->activeFlow;

        if ($message->type === 'text') {
            $step = match ($message->text) {
                'Save' => FlowStep::Save,
                'Cancel' => FlowStep::Cancel,
                'Set time' => FlowStep::SetTime,
                'Set date' => FlowStep::SetDate,
                'Add meal' => FlowStep::Initiation,
                default => null
            };

            if ($step) {
                $active->step = $step->value;
                $active->save();

                if ($active->step === FlowStep::Cancel->value) {
                    $active->status = FlowStatus::Finished;
                    $active->save();

                    $api->sendMessage([
                        'chat_id' => $chat->unique_id,
                        'text' => "Your meal entry was cancelled.",
                        'reply_markup' => $this->markup($chat),
                    ]);
                }

                if ($active->step === FlowStep::Save->value) {
                    if ($active?->images()->exists()) {
                        $meal = new Meal();

                        $meal->user_id = $active->user?->id;
                        $meal->chat_id = $active->chat?->id;
                        $meal->flow_id = $active->id;

                        $meal->date = $active->date ?? now()->format('Y-m-d');
                        $meal->time = $active->time ?? now()->format('H:i');

                        $meal->save();

                        $active->status = FlowStatus::Finished;
                        $active->save();

                        $date = ($active->date ?? $active->created_at->format('d.m.Y'));
                        $time = ($active->time ?? $active->created_at->format('H:i'));
                        $images = $active->images()->count();

                        $api->sendMessage([
                            'chat_id' => $chat->unique_id,
                            'text' => "Your meal was saved\n\n"
                                . "Date: " . $date . "\n"
                                . "Time: " . $time . "\n\n"
                                . "Photos: " . $images,
                            'reply_markup' => $this->markup($chat),
                        ]);
                    } else {
                        $api->sendMessage([
                            'chat_id' => $chat->unique_id,
                            'text' => "You may set the date (format: day.month.year, example: 25.12.2024).\n\n"
                                . "Date: " . ($active->date ?? $active->created_at->format('d.m.Y')),
                            'reply_markup' => $this->markup($chat),
                        ]);
                    }
                }

                if ($active->step === FlowStep::SetDate->value) {
                    $api->sendMessage([
                        'chat_id' => $chat->unique_id,
                        'text' => "You may set the date (format: day.month.year, example: 25.12.2024).\n\n"
                            . "Date: " . ($active->date ?? $active->created_at->format('d.m.Y')),
                        'reply_markup' => $this->markup($chat),
                    ]);
                }

                if ($active->step === FlowStep::SetTime->value) {
                    $api->sendMessage([
                        'chat_id' => $chat->unique_id,
                        'text' => "You may set the time (24-hour format, example: 16:35).\n\n"
                            . "Time: " . ($active->time ?? $active->created_at->format('H:i')),
                        'reply_markup' => $this->markup($chat),
                    ]);
                }
            }

            if ($step === null) {
                if ($message->text === 'Back') {
                    $active->step = FlowStep::Upload->value;
                    $active->save();

                    $chat->load('activeFlow');
                    $this->initiate($chat, $api);
                }

                if ($active->step === FlowStep::SetDate->value) {
                    try {
                        $date = Carbon::createFromFormat('d.m.Y', $message->text);
                    } catch (Throwable) {
                        $date = null;
                    }

                    if ($date) {
                        $active->date = $date->format('d.m.Y');
                        $active->step = FlowStep::Upload->value;
                        $active->save();

                        $api->sendMessage([
                            'chat_id' => $chat->unique_id,
                            'text' => "Date was successfully set.",
                            'reply_markup' => $this->markup($chat),
                        ]);
                        $this->initiate($chat, $api);
                    } else {
                        $api->sendMessage([
                            'chat_id' => $chat->unique_id,
                            'text' => "Sorry, I couldn't understand the given date.",
                            'reply_markup' => $this->markup($chat),
                        ]);
                    }
                }

                if ($active->step === FlowStep::SetTime->value) {
                    try {
                        $time = Carbon::createFromFormat('H:i', $message->text);
                    } catch (Throwable) {
                        $time = null;
                    }

                    if ($time) {
                        $active->time = $time->format('H:i');
                        $active->step = FlowStep::Upload->value;
                        $active->save();

                        $api->sendMessage([
                            'chat_id' => $chat->unique_id,
                            'text' => "Time was successfully set.",
                            'reply_markup' => $this->markup($chat),
                        ]);
                        $this->initiate($chat, $api);
                    } else {
                        $api->sendMessage([
                            'chat_id' => $chat->unique_id,
                            'text' => "Sorry, I couldn't understand the given time.",
                            'reply_markup' => $this->markup($chat),
                        ]);
                    }
                }
            }
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
        } else if (in_array($message->type, ['photo', 'document'])) {
            $api->sendMessage([
                'chat_id' => $message->chat->unique_id,
                'text' => 'Image received.',
                'reply_markup' => $this->markup($chat),
            ]);
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

        $markup = Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true);

        if (in_array($active->step, [FlowStep::SetDate->value, FlowStep::SetTime->value])) {
            return $markup
                ->row([
                    Keyboard::button('Back'),
                ]);
        }

        if (in_array($active->step, [FlowStep::Cancel->value, FlowStep::Save->value])) {
            $markup->row([
                Keyboard::button('Add meal'),
            ]);

            return $markup;
        }

        if ($active?->images()->exists()) {
            $markup->row([
                Keyboard::button('Save'),
            ]);
        }

        $markup
            ->row([
                Keyboard::button('Cancel'),
            ])
            ->row([
                Keyboard::button('Set time'),
            ])
            ->row([
                Keyboard::button('Set date'),
            ]);

        return $markup;
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
        $active = $chat->activeFlow;

        $text = $active->step
            ? "You may continue uploading photos right away.\n\n"
            : "New meal flow started.\n\n"
            . "You may start uploading photos right away.\n\n";

        $date = ($active->date ?? $active->created_at->format('d.m.Y'));
        $time = ($active->time ?? $active->created_at->format('H:i'));
        $images = $active->images()->count();

        $text .= "Date: " . $date . "\n"
            . "Time: " . $time . "\n\n"
            . "Photos: " . $images;

        $api->sendMessage([
            'chat_id' => $chat->unique_id,
            'text' => $text,
            'reply_markup' => $this->markup($chat),
        ]);
    }
}
