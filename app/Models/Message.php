<?php

namespace App\Models;

use App\Models\Traits\HasFiles;
use App\Queries\Models\FlowQuery;
use App\Queries\Models\MessageQuery;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property Chat $chat
 *
 * @method static MessageQuery query()
 * @method static MessageFactory factory(...$parameters)
 */
class Message extends Model
{
    use HasFactory;
    use HasFiles;

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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'object',
        'sent_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    /**
     * The loadable relationships for the model.
     *
     * @var array
     */
    protected array $relationships = [
        'chat',
        'files',
    ];

    /**
     * Associated chat relation query.
     *
     * @return BelongsTo
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id', 'unique_id');
    }

    /**
     * Associated flows query.
     *
     * @return FlowQuery
     */
    public function flows(): FlowQuery
    {
        return Flow::query()
            ->where('chat_id', $this->chat_id)
            ->where('user_id', $this->chat->user_id)
            ->where('beg_id', '<=', $this->unique_id)
            ->where(function (FlowQuery $query) {
                $query->whereNull('end_id')
                    ->orWhere('end_id', '<=', $this->unique_id);
            });
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
     * Returns video variant available to download.
     *
     * @return null|array<array{
     *      file_name: string,
     *      file_id: string,
     *      file_size: int,
     *      file_unique_id: string,
     *      mime_type: string,
     *      thumb: array{
     *          file_id: string,
     *          file_size: int,
     *          file_unique_id: string,
     *     },
     *     thumbnail: array{
     *           file_id: string,
     *           file_size: int,
     *           file_unique_id: string,
     *      },
     * }>
     */
    public function videoVariant(): ?array
    {
        $video = data_get($this->metadata, 'video');

        return empty($video) ? null : (array) $video;
    }

    /**
     * Returns document variant available to download.
     *
     * @return null|array<array{
     *      file_name: string,
     *      file_id: string,
     *      file_size: int,
     *      file_unique_id: string,
     *      mime_type: string,
     *      thumb: array{
     *          file_id: string,
     *          file_size: int,
     *          file_unique_id: string,
     *     },
     *     thumbnail: array{
     *           file_id: string,
     *           file_size: int,
     *           file_unique_id: string,
     *      },
     * }>
     */
    public function documentVariant(): ?array
    {
        $document = data_get($this->metadata, 'document');

        return empty($document) ? null : (array) $document;
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
