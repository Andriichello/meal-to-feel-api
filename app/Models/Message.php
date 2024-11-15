<?php

namespace App\Models;

use App\Queries\Models\MessageQuery;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Carbon;

/**
 * Class Message.
 *
 * @property int $id
 * @property int $unique_id
 * @property int $chat_id
 * @property string|null $type
 * @property string|null $text
 * @property object|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static MessageQuery query()
 * @method static MessageFactory factory(...$parameters)
 */
class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unique_id',
        'chat_id',
        'type',
        'text',
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
     * @return MessageQuery
     */
    public function newEloquentBuilder($query): MessageQuery
    {
        return new MessageQuery($query);
    }
}
