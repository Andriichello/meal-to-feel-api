<?php

namespace App\Jobs;

use App\Enums\PuppeteerStatus;
use App\Models\File;
use App\Models\Meal;
use App\Models\Message;
use App\Models\Result;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

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
    public int $timeout = 60;

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
            $flow = $file->context->flows()
                ->latest()
                ->first();

            if ($flow) {
                $meal = Meal::query()
                    ->where('flow_id', $flow->id)
                    ->first();
            }

            $result = new Result();

            $result->credential_id = null;
            $result->message_id = $file->context_id;
            $result->file_id = $file?->id;
            $result->meal_id = isset($meal) ? $meal->id : null;

            $result->language = $context?->chat?->user?->language ?? 'uk';
            $result->tried_at = now();

            try {
                $webp = (new MakeWebP($file))->handle();
            } catch (Throwable) {
                $webp = $file;
            }

            if ($webp->size) {
                $megabytes = $webp->size / 1024.0 / 1024.0;

                if ($megabytes > 2.0) {
                    $result->status = PuppeteerStatus::FileIsTooBig;
                    $result->metadata = (object) ['size' => $webp->size];

                    $result->save();

                    return;
                }
            }

            $json = '{"meal": "Name the meal","description":"Describe if meal is healthy or not.", "error": "Describe the error (might be no food on photo) or null here.","ingredients":[{"name":"Ingredient","serving_size":"1 medium sized","weight":130.5,"calories":62,"carbohydrates":15.4,"fiber":3.1,"sugar":12.2,"protein":1.2,"fat":0.2}],"total":{"weight":130.5,"calories":62,"carbohydrates":15.4,"fiber":3.1,"sugar":12.2,"protein":1.2,"fat":0.2}}';

            $text = "Here is a photo of the dish. Please estimate calories, nutrients."
                . " Please respond in JSON format (weight in grams): {$json}."
                . " Please respond in language with code: {$result->language}."
                . " If there is food always estimate it and return JSON (even if there are no ingredients), don't ask for details.";

            $response = OpenAI::chat()
                ->create([
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => $text],
                                ['type' => 'image_url', 'image_url' => ['url' => $file->url]],
                            ],
                        ],
                    ],
                    'max_tokens' => 15000,
                ]);

            $result->metadata = (object) ['response' => $response->toArray()];


            if (empty($response->choices)) {
                $result->status = PuppeteerStatus::NoChoices;
                $result->save();
            }

            foreach ($response->choices as $choice) {
                $content = $choice->message->content;

                if (!$content || !str_contains($content, '```json')) {
                    continue;
                }

                $json = \Illuminate\Support\Str::of($content)
                    ->after('```json')
                    ->beforeLast('```')
                    ->value();

                try {
                    $payload = json_decode($json, true);
                    $result->payload = (object) $payload;

                    $error = data_get($payload, 'error');
                    $ingredients = data_get($payload, 'ingredients');

                    $result->status = (!empty($error) || empty($ingredients))
                        ? PuppeteerStatus::Unrecognized : PuppeteerStatus::Success;
                    $result->save();

                    return;
                } catch (Throwable) {
                    $result->status = PuppeteerStatus::Exception;
                    $result->save();

                    return;
                }
            }

            $result->status = PuppeteerStatus::Nothing;
            $result->save();
        }
    }
}
