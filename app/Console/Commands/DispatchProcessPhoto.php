<?php

namespace App\Console\Commands;


use App\Enums\PuppeteerStatus;
use App\Enums\ResultStatus;
use App\Jobs\ProcessPhoto;
use App\Models\Credential;
use App\Models\File;
use App\Models\Message;
use App\Queries\Models\FileQuery;
use App\Queries\Models\ResultQuery;
use Illuminate\Console\Command;

/**
 * Class DispatchProcessPhoto.
 */
class DispatchProcessPhoto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch:process-photo {--limit=3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches ProcessPhoto jobs'
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
                $q->withStatus(...PuppeteerStatus::cases());
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

        $credential = Credential::query()
            ->orderBy(['try_again_at', 'last_used_at'])
            ->firstOrFail();

        $this->query()
            ->orderByDesc('updated_at')
            ->each(
                function (File $file) use (&$counter, $limit, $credential) {
                    $job = new ProcessPhoto($file->id, $credential->id);

                    dispatch($job)->delay(10);
                    $counter++;

                    // breaks out of querying (if false)
                    return $counter < $limit;
                },
                100
            );

        $this->info("Dispatched $counter job(s)...");
    }
}
