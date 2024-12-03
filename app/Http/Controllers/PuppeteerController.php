<?php

namespace App\Http\Controllers;

use App\Enums\PuppeteerStatus;
use App\Http\Requests\Puppeteer\CallbackRequest;
use App\Models\Credential;
use App\Models\File;
use App\Models\Meal;
use App\Models\Message;
use App\Models\Result;
use App\Providers\MorphServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;

/**
 * Class PuppeteerController.
 */
class PuppeteerController extends Controller
{
    /**
     * Processes the result callback from Puppeteer.
     *
     * @param CallbackRequest $request
     *
     * @return JsonResponse
     */
    public function callback(CallbackRequest $request): JsonResponse
    {
        (new ConsoleOutput())->writeln('callback: ' . json_encode($request->all(), JSON_PRETTY_PRINT));

        $status = PuppeteerStatus::tryFrom($request->get('status'));

        $triedAt = $request->get('tried_at');
        $triedAt = $triedAt ? Carbon::parse($triedAt) : now();

        $tryAfter = $request->get('try_after');

        if ($tryAfter) {
            // Parse the "try again at" time (assume it's only the time, e.g., "1:15 PM")
            $tryAfter = Carbon::createFromFormat('g:i A', $tryAfter, $triedAt->getTimezone())
                ->setDate($triedAt->year, $triedAt->month, $triedAt->day);

            // If "try again at" time is earlier than "tried at" time, it's for the next day
            if ($tryAfter->lessThanOrEqualTo($triedAt)) {
                $tryAfter->addDay();
            }

            $timezone = $request->get('timezone');

            if ($timezone) {
                try {
                    $offset = Carbon::now()
                        ->tz($timezone)
                        ->getOffset();

                    $tryAfter->subSeconds($offset);
                } catch (Throwable) {
                    //
                }
            }
        }

        $credential = Credential::query()
            ->where('email', $request->get('username'))
            ->first();

        $file = File::query()
            ->find($request->get('file_id'));

        if ($file->context instanceof Message) {
            $flow = $file->context->flows()
                ->latest()
                ->first();

            if ($flow) {
                $meal = Meal::query()
                    ->where('flow_id', $flow->id)
                    ->first();
            }
        }

        $result = new Result();

        $result->credential_id = $credential?->id;
        $result->message_id = $file?->context_type === MorphServiceProvider::slugOf(Message::class)
            ? $file->context_id : null;
        $result->file_id = $file?->id;
        $result->meal_id = isset($meal) ? $meal->id : null;

        $result->language = $request->get('language') ?? 'en';
        $result->status = $status;
        $result->tried_at = $triedAt;
        $result->try_again_at = $tryAfter;

        $payload = $request->get('payload');

        if ($payload) {
            $result->payload = (object) $payload;
        }

        $result->metadata = (object) $request->all();

        $result->save();

        return response()->json(['message' => 'OK']);
    }
}
