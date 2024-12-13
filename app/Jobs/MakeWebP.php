<?php

namespace App\Jobs;

use App\Helpers\ConversionHelper;
use App\Helpers\StorageReader;
use App\Models\File;
use App\Repositories\FileRepository;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Class MakeWebP.
 */
class MakeWebP implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * The name of the connection the job should be sent to.
     *
     * @var string|null
     */
    public ?string $connection = 'database';

    /**
     * The max number of seconds the job can be performed.
     *
     * @var int
     */
    public int $timeout = 10;

    /**
     * @var File
     */
    protected File $media;

    /**
     * @var bool
     */
    protected bool $force;

    /**
     * Create a new job instance.
     *
     * @param File $media
     * @param bool $force
     */
    public function __construct(File $media, bool $force = false)
    {
        $this->media = $media;
        $this->force = $force;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception|FileNotFoundException
     */
    public function handle(): void
    {
        $media = $this->media;

        if (!$this->force && $media->webps()->exists()) {
            return;
        }

        $reader = new StorageReader($media->disk);
        $tempFile = $reader->asTempFile($media->full_path, $media->disk_name);
        $tempPath = pathOf($tempFile);

        try {
            $bytes = filesize($tempPath);
            $kilobytes = $bytes / 1024.0;

            if ($kilobytes < 25) {
                $quality = 100;
            } else if ($kilobytes < 50) {
                $quality = 75;
            } else if ($kilobytes < 100) {
                $quality = 50;
            } else if ($kilobytes < 200) {
                $quality = 30;
            } else if ($kilobytes < 500){
                $quality = 15;
            } else {
                $quality = 10;
            }
        } catch (Throwable) {
            $quality = 50;
        }

        $file = (new ConversionHelper())
            ->toWebP($tempPath, $quality);

        /** @var FileRepository $repo */
        $repo = app(FileRepository::class);
        $webp = $repo->createVariant($media, $file);

        $webp->quality = $quality;
        $webp->save();
    }
}
