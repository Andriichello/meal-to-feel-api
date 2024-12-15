<?php

namespace App\Jobs;

use App\Enums\FlowName;
use App\Enums\FlowStatus;
use App\Enums\ResultStatus;
use App\Helpers\VisionApiHelper;
use App\Models\File;
use App\Models\Meal;
use App\Models\Message;
use App\Models\Result;
use Exception;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * Class ProcessPhotoViaApi.
 */
class ProcessPhotoViaApi implements ShouldQueue, ShouldBeUnique
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
    public int $timeout = 60;

    /**
     * ID of the photo to be processed.
     *
     * @var int
     */
    protected int $fileId;

    /**
     * @return string
     */
    public function uniqueId(): string
    {
        return md5('file-id=' . $this->fileId);
    }

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

        $result = new Result();

        $result->file_id = $file?->id;
        $result->language = 'en';
        $result->tried_at = now();

        if ($context instanceof Message) {
            $flow = $context->flows()
                ->where('command', FlowName::AddMeal->value)
                ->latest()
                ->first();

            if ($flow) {
                $meal = Meal::query()
                    ->where('flow_id', $flow->id)
                    ->first();
            }

            $result->meal_id = isset($meal) ? $meal->id : null;
            $result->credential_id = null;
            $result->message_id = $file->context_id;
            $result->language = $context?->chat?->user?->language ?? 'uk';

            try {
                $webp = (new MakeWebP($file))->handle();
            } catch (Throwable) {
                $webp = $file;
            }

            if ($webp->size) {
                $megabytes = $webp->size / 1024.0 / 1024.0;

                if ($megabytes > 2.0) {
                    $result->status = ResultStatus::FileIsTooBig;
                    $result->metadata = (object) ['size' => $webp->size];

                    $result->save();

                    return;
                }
            }

            try {
                $response = VisionApiHelper::estimate($webp->url, $result->language);
                $result->metadata = (object) ['response' => $response->toArray()];
            } catch (Throwable $e) {
                $result->status = ResultStatus::Exception;
                $result->metadata = (object) [
                    'exception' => $e->getMessage(),
                    ...((array) $result->metadata),
                ];

                return;
            }

            if (empty($response->choices)) {
                $result->status = ResultStatus::NoChoices;
                $result->save();
            }

            foreach ($response->choices as $choice) {
                $content = $choice->message->content;

                if (!$content || !str_contains($content, '```json')) {
                    continue;
                }

                $json = Str::of($content)
                    ->after('```json')
                    ->beforeLast('```')
                    ->value();

                try {
                    $payload = json_decode($json, true);
                    $result->payload = (object) $payload;

                    $error = data_get($payload, 'error');
                    $ingredients = data_get($payload, 'ingredients');

                    $result->status = (!empty($error) || empty($ingredients))
                        ? ResultStatus::Unrecognized : ResultStatus::Processed;
                    $result->save();

                    return;
                } catch (Throwable $e) {
                    $result->status = ResultStatus::Exception;
                    data_set($result, 'metadata.exception', $e->getMessage());

                    $result->save();

                    return;
                }
            }

            $result->status = ResultStatus::Nothing;
            $result->save();

            return;
        }

        $result->status = ResultStatus::Ignored;
        $result->save();
    }
}
