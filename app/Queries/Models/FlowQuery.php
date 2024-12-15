<?php

namespace App\Queries\Models;

use App\Enums\FlowStatus;
use App\Models\Flow;
use App\Queries\BaseQuery;
use Illuminate\Database\Query\Builder;

/**
 * Class FlowQuery.
 *
 * @property Flow $model
 *
 * @method FlowQuery select($columns = ['*'])
 * @method FlowQuery whereKey($id)
 * @method Flow|null find($id, $columns = ['*'])
 * @method Flow findOrFail($id, $columns = ['*'])
 * @method Flow|null first($columns = ['*'])
 * @method Flow firstOrFail($columns = ['*'])
 * @method Flow firstOrNew(array $attributes = [], array $values = [])
 * @method Flow make(array $attributes = [])
 * @method Flow create(array $attributes = [])
 * @method Flow updateOrCreate(array $attributes, array $values = [])
 */
class FlowQuery extends BaseQuery
{
    /**
     * Filter down to active flows.
     *
     * @return $this
     */
    public function active(): static
    {
        $this->where(function (FlowQuery $query) {
            $query->whereNull('end_id')
                ->withStatus(FlowStatus::Initiated, FlowStatus::InProgress);
        });

        return $this;
    }

    /**
     * Filter down to inactive flows.
     *
     * @return $this
     */
    public function inactive(): static
    {
        $this->whereNot(function (FlowQuery $query) {
            $query->active();
        });

        return $this;
    }

    /**
     * Filter down to given statuses.
     *
     * @param string|FlowStatus ...$statuses
     *
     * @return $this
     */
    public function withStatus(string|FlowStatus ...$statuses): static
    {
        $values = [];

        foreach ($statuses as $status) {
            if ($status instanceof FlowStatus) {
                $status = $status->value;
            }

            $values = $status;
        }

        $this->whereIn('status', $values);

        return $this;
    }
}
