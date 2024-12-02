<?php

namespace App\Queries\Models;

use App\Enums\PuppeteerStatus;
use App\Models\Result;
use App\Queries\BaseQuery;

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
 * @author Andrii Prykhodko <andriichello@gmail.com>
 * @package Speedgoat\Skeleton\Queries
 */
class ResultQuery extends BaseQuery
{
    /**
     * Filter down to results that have been successful.
     *
     * @return $this
     */
    public function successful(): static
    {
        $this->where('status', PuppeteerStatus::Success->value);

        return $this;
    }

    /**
     * Filter down to results that have failed.
     *
     * @return $this
     */
    public function failed(): static
    {
        $this->whereNot(function (ResultQuery $query) {
            $query->successful();
        });

        return $this;
    }
}
