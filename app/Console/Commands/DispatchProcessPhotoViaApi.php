<?php

namespace App\Console\Commands;


use App\Enums\ResultStatus;
use App\Jobs\ProcessPhotoViaApi;
use App\Models\File;
use App\Models\Message;
use App\Queries\Models\FileQuery;
use App\Queries\Models\ResultQuery;
use Illuminate\Console\Command;

/**
 * Class DispatchProcessPhotoViaApi.
 */
class DispatchProcessPhotoViaApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch:process-photo-via-api {--limit=3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches ProcessPhotoViaApi jobs'
        . ' if there are unprocessed photos.';

    /**
     * Returns files query.
     *
     * @return FileQuery
     */
    protected function query(): FileQuery
    {
        return File::query()
            ->whereContext(Message::class)
            ->whereNotNull('context_id')
            ->images()
            ->whereDoesntHave('results', function (ResultQuery $q) {
                $q->withStatus(...ResultStatus::cases());
            });
    }

    /**
     * Execute the console command.
     *
     * @returns void
     */
    public function handle(): void
    {
        $limit = (int) $this->option('limit');

        $counter = 0;

        $this->query()
            ->each(
                function (File $file) use (&$counter, $limit) {
                    $job = new ProcessPhotoViaApi($file->id);

                    dispatch($job);
                    $counter++;

                    // breaks out of querying (if false)
                    return $counter < $limit;
                },
                100
            );

        $this->info("Dispatched $counter job(s)...");
    }
}
