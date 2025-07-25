<?php

namespace App\Models;

use App\Enums\FlowStatus;
use App\Queries\Models\ChatQuery;
use App\Queries\Models\FileQuery;
use App\Queries\Models\FlowQuery;
use App\Queries\Models\ResultQuery;
use Database\Factories\ChatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
 * @property User $user
 * @property Flow[]|null $flows
 * @property Flow|null $activeFlow
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
        'user',
        'flows',
        'activeFlow',
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
     * Associated user relation query.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'unique_id');
    }

    /**
     * Associated flows relation query.
     *
     * @return HasMany
     */
    public function flows(): HasMany
    {
        return $this->hasMany(Flow::class, 'chat_id', 'unique_id');
    }

    /**
     * Associated (latest) active flow relation query.
     *
     * @return HasOne
     */
    public function activeFlow(): HasOne
    {
        return $this->hasOne(Flow::class, 'chat_id', 'unique_id')
            ->whereNull('end_id')
            ->latestOfMany();
    }

    /**
     * Associated files query.
     *
     * @return FileQuery
     */
    public function files(): FileQuery
    {
        return File::query()
            ->whereContext(Message::class)
            ->join('messages', 'messages.id', '=', 'files.context_id')
            ->where('messages.chat_id', $this->unique_id)
            ->orderByDesc('id')
            ->select('files.*');
    }

    /**
     * Associated images query.
     *
     * @return FileQuery
     */
    public function images(): FileQuery
    {
        return $this->files()
            ->images();
    }

    /**
     * Associated results query.
     *
     * @return ResultQuery
     */
    public function results(): ResultQuery
    {
        return Result::query()
            ->join('messages', 'messages.id', '=', 'results.message_id')
            ->where('messages.chat_id', $this->unique_id)
            ->orderByDesc('id')
            ->select('results.*');
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
