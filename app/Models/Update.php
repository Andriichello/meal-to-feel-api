<?php

namespace App\Models;

use App\Queries\Models\UpdateQuery;
use Database\Factories\UpdateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Carbon;

/**
 * Class Update.
 *
 * @property int $id
 * @property int $unique_id
 * @property int $user_id
 * @property int $chat_id
 * @property int $message_id
 * @property string|null $type
 * @property string|null $status
 * @property object|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static UpdateQuery query()
 * @method static UpdateFactory factory(...$parameters)
 */
class Update extends Model
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
        'chat_id',
        'message_id',
        'type',
        'status',
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
     * @return UpdateQuery
     */
    public function newEloquentBuilder($query): UpdateQuery
    {
        return new UpdateQuery($query);
    }
}
