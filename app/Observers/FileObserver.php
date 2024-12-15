<?php

namespace App\Observers;

use App\Jobs\NotifyAboutResult;
use App\Jobs\ProcessPhotoViaApi;
use App\Models\Credential;
use App\Models\File;
use App\Models\Result;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Class FileObserver.
 */
class FileObserver
{
    /**
     * Handle the File "saved" event.
     */
    public function saved(File $file): void
    {
        // if (!$file->isImage() || !empty($file->exception) || $file->results()->exists()) {
        //     return;
        // }

        // try {
        //     (new ProcessPhotoViaApi($file->id))->handle();
        // } catch (Throwable $e) {
        //     $file->exception = $e->getMessage();
        //     $file->save();
        // }
    }
}
