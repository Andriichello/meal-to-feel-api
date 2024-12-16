<?php

namespace App\Jobs;

use App\Enums\MealStatus;
use App\Models\File;
use App\Models\Meal;
use App\Models\Result;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class ProcessPhoto.
 */
class ProcessMeal implements ShouldQueue
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
     * ID of the photo to be processed.
     *
     * @var int
     */
    protected int $mealId;

    /**
     * Determines if results should be sent to user.
     *
     * @var int
     */
    protected int $notify;

    /**
     * ProcessMeal's constructor
     *
     * @param int $mealId
     * @param bool $notify
     */
    public function __construct(int $mealId, bool $notify = true)
    {
        $this->mealId = $mealId;
        $this->notify = $notify;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $meal = Meal::query()
            ->findOrFail($this->mealId);

        $images = $meal->flow->images()->get();

        /** @var File $image */
        foreach ($images as $image) {
            if ($image->results()->doesntExist()) {
                dispatch(new ProcessPhotoViaApi($image->id));

                $dispatched = true;
            }
        }

        if (isset($dispatched)) {
            dispatch(new ProcessMeal($this->mealId, $this->notify));
            return;
        }

        $results = [];

        /** @var File $image */
        foreach ($images as $image) {
            /** @var Result|null $result */
            $result = $image->results()->latest()->first();

            if ($result) {
                $results[] = $result;
            }
        }

        $metadata = (array) ($meal->metadata ?? []);
        data_set($metadata, 'summary', $this->summary($results));

        $meal->metadata = (object) $metadata;
        $meal->status = MealStatus::Processed;
        $meal->save();

        if ($this->notify) {
            foreach ($results as $result) {
                (new NotifyAboutResult($result))->handle();
            }

            (new NotifyAboutSummary($meal))->handle();
        }
    }

    /**
     * Compose a summary of given results.
     *
     * @param Result[] $results
     *
     * @return array
     */
    public function summary(array $results): array
    {
        $summary = [
            'ingredients' => [],
            'total' => [
                'weight' => 0,
                'fat' => 0,
                'fiber' => 0,
                'sugar' => 0,
                'protein' => 0,
                'calories' => 0,
                'carbohydrates' => 0,
            ],
        ];

        foreach ($results as $result) {
            $payload = json_decode(json_encode($result->payload), true);

            if (empty($payload)) {
                continue;
            }

            // Skip invalid responses
            if (!isset($payload['ingredients']) || !isset($payload['total'])) {
                continue;
            }

            // Sum up total
            foreach ($payload['total'] as $key => $value) {
                if (array_key_exists($key, $summary['total'])) {
                    $summary['total'][$key] += $value;
                }
            }

            // Process ingredients
            foreach ($payload['ingredients'] as $ingredient) {
                $name = $ingredient['name_en'];

                if (!isset($summary['ingredients'][$name])) {
                    // Initialize new ingredient entry
                    $summary['ingredients'][$name] = [
                        'name' => $ingredient['name'],
                        'name_en' => $name,
                        'weight' => 0,
                        'fat' => 0,
                        'fiber' => 0,
                        'sugar' => 0,
                        'protein' => 0,
                        'calories' => 0,
                        'carbohydrates' => 0,
                    ];
                }

                // Sum up ingredient data
                foreach (['weight', 'fat', 'fiber', 'sugar', 'protein', 'calories', 'carbohydrates'] as $key) {
                    if (isset($ingredient[$key])) {
                        $summary['ingredients'][$name][$key] += $ingredient[$key];
                    }
                }
            }
        }

        // Convert ingredients to a list for easier processing later
        $summary['ingredients'] = array_values($summary['ingredients']);

        return $summary;
    }
}
