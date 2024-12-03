<?php

namespace App\Jobs;

use App\Helpers\StorageReader;
use App\Models\Credential;
use App\Models\File;
use App\Models\Flow;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class ProcessPhoto.
 */
class ProcessPhoto implements ShouldQueue
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
    public int $timeout = 180;

    /**
     * ID of the photo to be processed.
     *
     * @var int
     */
    protected int $fileId;

    /**
     * ID of the credentials to be used.
     *
     * @var int
     */
    protected int $credentialId;

    /**
     * ProcessPhoto's constructor
     *
     * @param int $fileId
     * @param int $credentialId
     */
    public function __construct(int $fileId, int $credentialId)
    {
        $this->fileId = $fileId;
        $this->credentialId = $credentialId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $file = File::query()
            ->findOrFail($this->fileId);

        $credential = Credential::query()
            ->findOrFail($this->credentialId);

        /** @var Model $message */
        $context = $file->context;

        if ($context instanceof Message) {
            $reader = new StorageReader('uploads');
            $tempFile = $reader->asTempFile($file->full_path, $file->disk_name);

            if (is_resource($tempFile)) {
                $tempPath = stream_get_meta_data($tempFile)['uri'];

                $command = [
                    'node',
                    resource_path('/puppeteer/work.js'),
                    '--username', $credential->email,
                    '--password', $credential->password,
                    '--language', 'uk', // user's language here
                    '--file-id', $this->fileId,
                    '--file-path', $tempPath,
                    '--debugger-port', 9222,
                    '--port', 8000,
                    '--host', 'localhost',
                ];

                // Command to execute the Node.js script
                $process = new Process($command, timeout: $this->timeout - 5);

                try {
                    $process->mustRun();
                    Log::info("Node script output: " . $process->getOutput());
                } catch (ProcessFailedException $e) {
                    Log::error("Node script failed: " . $e->getMessage());
                }

                fclose($tempFile);
            }
        }
    }
}
