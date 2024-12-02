<?php

namespace App\Models;

use App\Queries\Models\CredentialQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Carbon;

/**
 * Class Credential.
 *
 * @property int $id
 * @property string $username
 * @property string $password
 * @property Carbon|null $last_used_at
 * @property Carbon|null $try_again_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static CredentialQuery query()
 */
class Credential extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'last_used_at',
        'try_again_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'last_used_at' => 'datetime',
        'try_again_at' => 'datetime',
    ];

    /**
     * The loadable relationships for the model.
     *
     * @var array
     */
    protected array $relationships = [
        'results',
    ];

    /**
     * Associated results relation query.
     *
     * @return HasMany
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'credential_id', 'id');
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param DatabaseBuilder $query
     *
     * @return CredentialQuery
     */
    public function newEloquentBuilder($query): CredentialQuery
    {
        return new CredentialQuery($query);
    }
}
