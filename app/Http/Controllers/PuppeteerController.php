<?php

namespace App\Http\Controllers;

use App\Enums\PuppeteerStatus;
use App\Http\Requests\Puppeteer\CallbackRequest;
use App\Models\Credential;
use App\Models\File;
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
        (new ConsoleOutput())->writeln("response: " . json_encode($request->all(), JSON_PRETTY_PRINT));

        $status = PuppeteerStatus::tryFrom($request->get('status'));

        $triedAt = $request->get('tried_at');
        $triedAt = $triedAt ? Carbon::parse($triedAt) : now();

        $tryAgainAt = $request->get('try_again_at');

        if ($tryAgainAt) {
            // Parse the "try again at" time (assume it's only the time, e.g., "1:15 PM")
            $tryAgainAt = Carbon::createFromFormat('g:i A', $tryAgainAt, $triedAt->getTimezone())
                ->setDate($triedAt->year, $triedAt->month, $triedAt->day);

            // If "try again at" time is earlier than "tried at" time, it's for the next day
            if ($tryAgainAt->lessThanOrEqualTo($triedAt)) {
                $tryAgainAt->addDay();
            }
        }

        $credential = Credential::query()
            ->where('email', $request->get('username'))
            ->first();

        $file = File::query()
            ->find($request->get('file_id'));

        $result = new Result();

        $result->credential_id = $credential?->id;
        $result->message_id = $file?->context_type === MorphServiceProvider::slugOf(Message::class)
            ? $file->context_id : null;
        $result->file_id = $file?->id;

        $result->language = $request->get('language') ?? 'en';
        $result->status = $status;
        $result->tried_at = $triedAt;
        $result->try_again_at = $tryAgainAt;

        $payload = $request->get('payload');

        if ($payload) {
            $result->payload = (object) $payload;
        }

        $result->metadata = (object) $request->all();

        $result->save();

        return response()->json(['message' => 'OK']);
    }
}
