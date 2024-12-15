<?php

namespace App\Queries\Models;

use App\Enums\PuppeteerStatus;
use App\Enums\ResultStatus;
use App\Models\Result;
use App\Queries\BaseQuery;
use App\Queries\Traits\WithStatus;
use BackedEnum;

/**
 * Class ResultQuery.
 *
 * @property Result $model
 *
 * @method ResultQuery select($columns = ['*'])
 * @method ResultQuery whereKey($id)
 * @method Result|null find($id, $columns = ['*'])
 * @method Result findOrFail($id, $columns = ['*'])
 * @method Result|null first($columns = ['*'])
 * @method Result firstOrFail($columns = ['*'])
 * @method Result firstOrNew(array $attributes = [], array $values = [])
 * @method Result make(array $attributes = [])
 * @method Result create(array $attributes = [])
 * @method Result updateOrCreate(array $attributes, array $values = [])
 *
 * @method ResultQuery withStatus(BackedEnum ...$enum)
 */
class ResultQuery extends BaseQuery
{
    use WithStatus;

    /**
     * Filter down to results that have been processed.
     *
     * @return static
     */
    public function processed(): static
    {
        $this->where('status', ResultStatus::Processed->value);

        return $this;
    }
}
