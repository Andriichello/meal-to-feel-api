<?php

namespace App\Models;

use App\Queries\Models\ChatQuery;
use Database\Factories\ChatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class Chat.
 *
 * @property int $id
 * @property int $unique_id
 * @property int $user_id
 * @property string|null $username
 * @property string $type
 * @property object|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Message[]|Collection $messages
 *
 * @method static ChatQuery query()
 * @method static ChatFactory factory(...$parameters)
 */
class Chat extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unique_id',
        'user_id',
        'username',
        'type',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'object',
    ];

    /**
     * The loadable relationships for the model.
     *
     * @var array
     */
    protected array $relationships = [
        'messages',
    ];

    /**
     * Associated messages relation query.
     *
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'chat_id', 'unique_id');
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param DatabaseBuilder $query
     *
     * @return ChatQuery
     */
    public function newEloquentBuilder($query): ChatQuery
    {
        return new ChatQuery($query);
    }
}
