<?php

namespace App\Queries\Models;

use App\Models\User;
use App\Queries\BaseQuery;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class UserQuery.
 *
 * @property User $model
 *
 * @method UserQuery select($columns = ['*'])
 * @method UserQuery whereKey($id)
 * @method User|null find($id, $columns = ['*'])
 * @method User findOrFail($id, $columns = ['*'])
 * @method User|null first($columns = ['*'])
 * @method User firstOrFail($columns = ['*'])
 * @method User firstOrNew(array $attributes = [], array $values = [])
 * @method User make(array $attributes = [])
 * @method User create(array $attributes = [])
 * @method User updateOrCreate(array $attributes, array $values = [])
 */
class UserQuery extends BaseQuery
{
    /**
     * Filters down to users that have
     * Sanctum properties set.
     *
     * @return UserQuery
     */
    public function withSanctum(): UserQuery
    {
        $this->where(function (Builder $query) {
            $query->whereNotNull('email')
                ->whereNotNull('password');
        });

        return $this;
    }

    /**
     * Filters down to users that have
     * Telegram properties set.
     *
     * @return UserQuery
     */
    public function withTelegram(): UserQuery
    {
        $this->whereNotNull('unique_id');

        return $this;
    }
}
