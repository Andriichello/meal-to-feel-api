<?php

namespace App\Observers;

use App\Jobs\NotifyAboutResult;
use App\Models\Credential;
use App\Models\Result;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Class ResultObserver.
 */
class ResultObserver
{
    /**
     * Handle the Result "saved" event.
     */
    public function saved(Result $result): void
    {
        $credential = $result->credential;

        if ($credential) {
            $this->fillLastUsedAt($credential, $result->tried_at);
            $this->fillTryAgainAt($credential, $result->try_again_at);

            $credential->save();
        }

        try {
            if (empty($result->notified_at)) {
                (new NotifyAboutResult($result))->handle();

                $result->notified_at = now();
                $result->save();
            }
        } catch (Throwable) {
            //
        }
    }

    /**
     * Fills given credential's `last_used_at` timestamp
     * (if given timestamp is newer than the current one).
     *
     * @param Credential $credential
     * @param Carbon|null $triedAt
     */
    protected function fillLastUsedAt(Credential $credential, ?Carbon $triedAt): void
    {
        if ($triedAt && !$credential->last_used_at?->isAfter($triedAt)) {
            $credential->last_used_at = $triedAt;
        }
    }

    /**
     * Fills given credential's `try_again_at` timestamp
     * (if given timestamp is newer than the current one).
     *
     * @param Credential $credential
     * @param Carbon|null $tryAgainAt
     */
    protected function fillTryAgainAt(Credential $credential, ?Carbon $tryAgainAt): void
    {
        if ($tryAgainAt && !$credential->try_again_at?->isAfter($tryAgainAt)) {
            $credential->try_again_at = $tryAgainAt;
        }
    }
}
