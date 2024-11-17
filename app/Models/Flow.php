<?php

namespace App\Models;

use App\Queries\Models\FlowQuery;
use Database\Factories\FlowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
     * @return FlowQuery
     */
    public function newEloquentBuilder($query): FlowQuery
    {
        return new FlowQuery($query);
    }
}
