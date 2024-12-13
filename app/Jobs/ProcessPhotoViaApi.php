<?php

namespace App\Jobs;

use App\Helpers\StorageReader;
use App\Models\Credential;
use App\Models\File;
use App\Models\Flow;
use App\Models\Message;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class ProcessPhotoViaApi.
 */
class ProcessPhotoViaApi implements ShouldQueue
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
     * ProcessPhotoViaApi's constructor
     *
     * @param int $fileId
     */
    public function __construct(int $fileId)
    {
        $this->fileId = $fileId;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $file = File::query()
            ->findOrFail($this->fileId);

        /** @var Model $message */
        $context = $file->context;

        if ($context instanceof Message) {
            // todo: process via API
            $json = '{"meal": "Name the meal","description":"Describe if meal is healthy or not.", "error": "Describe the error (might be no food on photo).","ingredients":[{"name":"Ingredient","serving_size":"1 medium sized","weight":130.5,"calories":62,"carbohydrates":15.4,"fiber":3.1,"sugar":12.2,"protein":1.2,"fat":0.2}],"total":{"weight":130.5,"calories":62,"carbohydrates":15.4,"fiber":3.1,"sugar":12.2,"protein":1.2,"fat":0.2}}';
            $language = $context?->chat?->user?->language ?? 'uk';

            $text = "Here is a photo of the dish. Please estimate calories, nutrients."
                . " Please respond in JSON format (weight in grams): {$json}."
                . " Please respond in language with code: {$language}."
                . " If there is food always estimate it and return JSON (even if there are no ingredients), don't ask for details.";

            OpenAI::chat()
                ->create([
                    'model' => 'gpt-4-vision-preview',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => $text],
//                                ['type' => 'image_url', 'image_url' => ['url' => $file->url]],
                            ],
                        ],
                    ],
                    'max_tokens' => 1000,
                ]);
        }
    }
}
