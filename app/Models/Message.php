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
 * @property Carbon|null $sent_at
 * @property Carbon|null $edited_at
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
        'sent_at',
        'edited_at',
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
            'sent_at' => 'datetime',
            'edited_at' => 'datetime',
        ];
    }

    /**
     * Returns photo variants available to download.
     *
     * @return null|array<array{
     *      width: int,
     *      height: int,
     *      file_id: string,
     *      file_size: int,
     *      file_unique_id: string,
     * }>
     */
    public function photoVariants(): ?array
    {
        $photo = data_get($this->metadata, 'photo');

        if (empty($photo)) {
            return null;
        }

        usort($photo, function (array|object $one, array|object $two) {
            return data_get($one, 'file_size') <=> data_get($two, 'file_size');
        });

        return $photo;
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
