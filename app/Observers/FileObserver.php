<?php

namespace App\Observers;

use App\Jobs\NotifyAboutResult;
use App\Jobs\ProcessPhotoViaApi;
use App\Models\Credential;
use App\Models\File;
use App\Models\Message;
use App\Models\Result;
use App\Providers\MorphServiceProvider;
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
//        if (!$file->isImage()) {
//            return;
//        }
//
//        if (!empty($file->exception)) {
//            return;
//        }
//
//        if ($file->context_type !== MorphServiceProvider::slugOf(Message::class)) {
//            return;
//        }
//
//        if ($file->results()->exists()) {
//            return;
//        }
//
//        try {
//            dispatch(new ProcessPhotoViaApi($file->id));
//        } catch (Throwable $e) {
//            $file->exception = $e->getMessage();
//            $file->save();
//        }
    }
}
