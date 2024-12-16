<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Queries\Models\UserQuery;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class User.
 *
 * Common:
 * @property int $id
 * @property int|null $trainer_id
 * @property Role $role
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
 * @property User|null $trainer
 * @property Chat[]|Collection $chats
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
        'trainer_id',
        'role',
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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'role' => Role::class,
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_bot' => 'boolean',
        'is_premium' => 'boolean',
        'metadata' => 'object',
    ];

    /**
     * The loadable relationships for the model.
     *
     * @var array
     */
    protected array $relationships = [
        'trainer',
        'chats',
    ];

    /**
     * Associated trainer relation query.
     *
     * @return BelongsTo
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id', 'id');
    }

    /**
     * Associated chats relation query.
     *
     * @return HasMany
     */
    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'user_id', 'unique_id');
    }

    /**
     * Returns true if user has email and password set.
     *
     * @return bool
     */
    public function isSanctum(): bool
    {
        return !empty($this->email) && !empty($this->password);
    }

    /**
     * Returns true if user has Telegram's user id (`unique_id`) set.
     *
     * @return bool
     */
    public function isTelegram(): bool
    {
        return !empty($this->unique_id);
    }

    /**
     * Returns true if user has given role set.
     *
     * @param Role|null $role
     *
     * @return bool
     */
    public function isOfRole(?Role $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Get hourly uploads limit for current user.
     *
     * @return int
     */
    public function hourlyUploadsLimit(): int
    {
        if ($this->isOfRole(Role::Admin)) {
            return 300;
        }

        if ($this->isOfRole(Role::Trainer)) {
            return 100;
        }

        return 30;
    }

    /**
     * Get daily uploads limit for current user.
     *
     * @return int
     */
    public function dailyUploadsLimit(): int
    {
        if ($this->isOfRole(Role::Admin)) {
            return 900;
        }

        if ($this->isOfRole(Role::Trainer)) {
            return 300;
        }

        return 100;
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
