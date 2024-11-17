<?php

namespace App\Models;

use App\Queries\Models\FlowQuery;
use Database\Factories\FlowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Carbon;

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
