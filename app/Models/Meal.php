<?php

namespace App\Models;

use App\Queries\Models\MealQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class Meal.
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $chat_id
 * @property int|null $flow_id
 * @property string $date
 * @property string $time
 * @property object|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User|null $user
 * @property Chat|null $chat
 * @property Flow|null $flow
 * @property Result[]|Collection $results
 *
 * @method static MealQuery query()
 */
class Meal extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'chat_id',
        'flow_id',
        'date',
        'time',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'datetime',
        'time' => 'datetime',
        'metadata' => 'object',
    ];

    /**
     * The loadable relationships for the model.
     *
     * @var array
     */
    protected array $relationships = [
        'user',
        'chat',
        'flow',
        'results',
    ];

    /**
     * Associated user relation query.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Associated chat relation query.
     *
     * @return BelongsTo
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id', 'id');
    }

    /**
     * Associated flow relation query.
     *
     * @return BelongsTo
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class, 'flow_id', 'id');
    }

    /**
     * Associated results relation query.
     *
     * @return HasMany
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'meal_id', 'id');
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param DatabaseBuilder $query
     *
     * @return MealQuery
     */
    public function newEloquentBuilder($query): MealQuery
    {
        return new MealQuery($query);
    }
}
