<?php

namespace App\Queries\Models;

use App\Models\Credential;
use App\Queries\BaseQuery;

/**
 * Class CredentialQuery.
 *
 * @property Credential $model
 *
 * @method CredentialQuery select($columns = ['*'])
 * @method CredentialQuery whereKey($id)
 * @method Credential|null find($id, $columns = ['*'])
 * @method Credential findOrFail($id, $columns = ['*'])
 * @method Credential|null first($columns = ['*'])
 * @method Credential firstOrFail($columns = ['*'])
 * @method Credential firstOrNew(array $attributes = [], array $values = [])
 * @method Credential make(array $attributes = [])
 * @method Credential create(array $attributes = [])
 * @method Credential updateOrCreate(array $attributes, array $values = [])
 *
 * @author Andrii Prykhodko <andriichello@gmail.com>
 * @package Speedgoat\Skeleton\Queries
 */
class CredentialQuery extends BaseQuery
{
    /**
     * Filter down to credentials that are available for usage.
     *
     * @return $this
     */
    public function available(): static
    {
        $this->where(function (CredentialQuery $query) {
            $query->whereNull('try_again_at')
                ->orWhere('try_again_at', '<=', now());
        });

        return $this;
    }

    /**
     * Order credentials by `last_used_at` column.
     *
     * @return $this
     */
    public function orderByLastUsedAt(string $direction = 'asc'): static
    {
        $this->orderBy('last_used_at', $direction);

        return $this;
    }
}
