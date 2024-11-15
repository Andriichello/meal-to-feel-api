<?php

namespace App\Models;

use App\Queries\Models\ChatQuery;
use Database\Factories\ChatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Carbon;

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'object',
        ];
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
