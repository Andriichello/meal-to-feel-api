<?php

namespace App\Models;

use App\Queries\Models\FileQuery;
use App\Queries\Models\FlowQuery;
use Database\Factories\FlowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Carbon;
use stdClass;

/**
 * Class Flow.
 *
 * @property int $id
 * @property int $chat_id
 * @property int|null $user_id
 * @property int $beg_id
 * @property int|null $end_id
 * @property string $command
 * @property string $status
 * @property string|null $step
 * @property object|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property string|null $date
 * @property string|null $time
 *
 * @property Chat $chat
 * @property User|null $user
 * @property Message $beg
 * @property Message|null $end
 *
 * @method static FlowQuery query()
 * @method static FlowFactory factory(...$parameters)
 */
class Flow extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chat_id',
        'user_id',
        'beg_id',
        'end_id',
        'command',
        'status',
        'step',
        'metadata',
        'date',
        'time',
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
        'chat',
        'user',
        'beg',
        'end',
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
     * Associated user relation query.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'unique_id');
    }

    /**
     * Associated beginning message relation query.
     *
     * @return BelongsTo
     */
    public function beg(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'beg_id', 'unique_id');
    }

    /**
     * Associated end message relation query.
     *
     * @return BelongsTo
     */
    public function end(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'end_id', 'unique_id');
    }

    /**
     * Associated files query.
     *
     * @return FileQuery
     */
    public function files(): FileQuery
    {
        $query = File::query();

        $query->whereContext(Message::class)
            ->where('messages.chat_id', $this->chat_id)
            ->join('messages', 'messages.id', '=', 'files.context_id');

        $query->where(function (FileQuery $q) {
            $q->where('messages.unique_id', '>=', $this->beg_id);

            if ($this->end_id) {
                $q->where('messages.unique_id', '<=', $this->end_id);
            }
        });

        return $query;
    }

    /**
     * Associated images query.
     *
     * @return FileQuery
     */
    public function images(): FileQuery
    {
        return $this->files()
            ->where(function (FileQuery $q) {
                $q->whereLike('messages.type', 'image/%')
                    ->orWhereLike('messages.type', 'photo%');
            });
    }

    /**
     * Get `date` attribute from `metadata`.
     *
     * @return string|null
     */
    public function getDateAttribute(): ?string
    {
        return data_get($this->metadata, 'date');
    }

    /**
     * Set `date` attribute on `metadata`.
     *
     * @param Carbon|string|null $date
     *
     * @return void
     */
    public function setDateAttribute(Carbon|string|null $date): void
    {
        if ($date instanceof Carbon) {
            $date = $date->format('d.m.Y');
        }

        $metadata = $this->metadata ?? new stdClass();
        data_set($metadata, 'date', $date);

        $this->attributes['metadata'] = json_encode($metadata);
    }

    /**
     * Get `time` attribute from `metadata`.
     *
     * @return string|null
     */
    public function getTimeAttribute(): ?string
    {
        return data_get($this->metadata, 'time');
    }

    /**
     * Set `time` attribute on `metadata`.
     *
     * @param Carbon|string|null $time
     *
     * @return void
     */
    public function setTimeAttribute(Carbon|string|null $time): void
    {
        if ($time instanceof Carbon) {
            $time = $time->format('H:i');
        }

        $metadata = $this->metadata ?? new stdClass();
        data_set($metadata, 'time', $time);

        $this->attributes['metadata'] = json_encode($metadata);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param DatabaseBuilder $query
     *
     * @return FlowQuery
     */
    public function newEloquentBuilder($query): FlowQuery
    {
        return new FlowQuery($query);
    }
}
