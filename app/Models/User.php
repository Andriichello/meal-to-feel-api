<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Queries\Models\UserQuery;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Class User.
 *
 * Common:
 * @property int $id
 * @property string $name
 * @property string|null $language
 * @property object|null $metadata
 *
 * Sanctum:
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $email_verified_at
 *
 * Telegram:
 * @property int|null $unique_id
 * @property string|null $username
 * @property boolean|null $is_bot
 * @property boolean|null $is_premium
 *
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static UserQuery query()
 * @method static UserFactory factory(...$parameters)
 */
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        /** Common */
        'name',
        'language',

        /** Sanctum */
        'email',
        'password',

        /** Telegram */
        'unique_id',
        'username',
        'is_bot',
        'is_premium',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_bot' => 'boolean',
            'is_premium' => 'boolean',
            'metadata' => 'object',
        ];
    }

    /**
     * Returns true user has email and password set.
     *
     * @return bool
     */
    public function isSanctum(): bool
    {
        return !empty($this->email) && !empty($this->password);
    }

    /**
     * Returns true user has Telegram's user id (`unique_id`) set.
     *
     * @return bool
     */
    public function isTelegram(): bool
    {
        return !empty($this->unique_id);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param DatabaseBuilder $query
     *
     * @return UserQuery
     */
    public function newEloquentBuilder($query): UserQuery
    {
        return new UserQuery($query);
    }
}
