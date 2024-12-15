<?php

namespace App\Helpers;

use App\Enums\FlowName;
use App\Enums\FlowStatus;
use App\Flows\BaseFlow;
use App\Models\Flow;
use App\Models\Message;
use Exception;
use Illuminate\Support\Arr;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

/**
 * Class FlowHelper.
 */
class FlowHelper
{
    /**
     * Name of the Telegram bot to be used.
     *
     * @var string
     */
    protected string $bot;

    /**
     * Name of the Telegram bot to be used.
     *
     * @var Api
     */
    protected Api $api;

    /**
     * FlowHelper construct.
     *
     * @param Api|null $api
     *
     * @throws Exception
     */
    public function __construct(?Api $api = null)
    {
        if ($api === null) {
            $bot = array_key_first(BotHelper::botConfigs());

            if ($bot) {
                $api = Telegram::bot($bot);
            }
        }

        $bot = $bot ?? BotHelper::tokenToName($api->getAccessToken());

        if (empty($bot) || empty($api)) {
            throw new Exception('Failed to resolve which bot to use.');
        }

        $this->bot = $bot;
        $this->api = $api;
    }

    /**
     * Resolves all flow classes for the given bot.
     *
     * @return class-string<BaseFlow>[]
     */
    public function flows(): array
    {
        return data_get(BotHelper::botConfig($this->bot), 'flows', []);
    }

    /**
     * Resolves flow for the given message.
     *
     * @param Message $message
     *
     * @return array{
     *     active: ?array{model: Flow, class: ?class-string<BaseFlow>},
     *     start: ?class-string<BaseFlow>,
     * }
     */
    public function resolve(Message $message): array
    {
        $flows = $this->flows();

        $start = array_filter(
            $flows,
            /** @var class-string<BaseFlow> $flow */
            fn (string $flow) => $flow::startsWith($message)
        );

        if ($message->chat->activeFlow) {
            $name = FlowName::tryFrom($message->chat->activeFlow->command);
            $active = Arr::first(
                $flows,
                /** @var class-string<BaseFlow> $flow */
                fn(string $flow) => $flow::$name === $name
            );

            $details = [
                'model' => $message->chat->activeFlow,
                'class' => $active,
            ];
        }

        return ['active' => $details ?? null, 'start' => Arr::first($start) ?? null];
    }

    /**
     * Handle message as part of the flow.
     *
     * @param Message $message
     *
     * @return void
     * @throws TelegramSDKException
     */
    public function handle(Message $message): void
    {
        $resolved = $this->resolve($message);

        if ($resolved['start']) {
            // cancel the active flow
            if ($resolved['active']) {
                $active = $resolved['active']['model'];

                $active->status = FlowStatus::Cancelled->value;
                $active->end_id = $message->unique_id;

                $active->save();
            }

            // initiate the new flow
            $active = new Flow();

            $active->chat_id = $message->chat_id;
            $active->user_id = $message->chat->user_id;
            $active->beg_id = $message->unique_id;
            $active->command = $resolved['start']::$name->value;
            $active->status = FlowStatus::New->value;

            $active->save();

            $message->chat->load('activeFlow');

            /** @var BaseFlow $flow */
            $flow = app($resolved['start']);
            $flow->handle($message, $this->api);

            return;
        }

        if ($resolved['active'] && $resolved['active']['class']) {
            /** @var BaseFlow $flow */
            $flow = app($resolved['active']['class']);
            $flow->handle($message, $this->api);
        }
    }
}
