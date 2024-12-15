<?php

namespace App\Models;

use App\Casts\OneOfEnums;
use App\Enums\PuppeteerStatus;
use App\Enums\ResultStatus;
use App\Queries\Models\ResultQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Carbon;
use stdClass;

/**
 * Class Result.
 *
 * @property int $id
 * @property int|null $credential_id
 * @property int|null $message_id
 * @property int|null $file_id
 * @property int|null $meal_id
 * @property string $language
 * @property ResultStatus|PuppeteerStatus $status
 * @property object|null $payload
 * @property object|null $metadata
 * @property Carbon $tried_at
 * @property Carbon|null $try_again_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Carbon|null $notified_at
 *
 * @property Credential|null $credential
 * @property Message|null $message
 * @property File|null $file
 * @property Meal|null $meal
 *
 * @method static ResultQuery query()
 */
class Result extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'credential_id',
        'message_id',
        'file_id',
        'meal_id',
        'language',
        'status',
        'payload',
        'metadata',
        'tried_at',
        'try_again_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status' => OneOfEnums::class . ':'
            . ResultStatus::class . ','. PuppeteerStatus::class,
        'payload' => 'object',
        'metadata' => 'object',
        'tried_at' => 'datetime',
        'try_again_at' => 'datetime',
    ];

    /**
     * The loadable relationships for the model.
     *
     * @var array
     */
    protected array $relationships = [
        'credential',
        'message',
        'file',
        'meal',
    ];

    /**
     * Associated credential relation query.
     *
     * @return BelongsTo
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class, 'credential_id', 'id');
    }

    /**
     * Associated message relation query.
     *
     * @return BelongsTo
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id', 'id');
    }

    /**
     * Associated file relation query.
     *
     * @return BelongsTo
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    /**
     * Associated meal relation query.
     *
     * @return BelongsTo
     */
    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class, 'meal_id', 'id');
    }

    /**
     * Get `notified_at` attribute from `metadata`.
     *
     * @return Carbon|null
     */
    public function getNotifiedAtAttribute(): ?Carbon
    {
        $value = data_get($this->metadata, 'notified_at');

        return empty($value) ? null : Carbon::parse($value);
    }

    /**
     * Set `notified_at` attribute on `metadata`.
     *
     * @param Carbon|null $notifiedAt
     *
     * @return void
     */
    public function setNotifiedAtAttribute(Carbon|null $notifiedAt): void
    {
        $metadata = $this->metadata ?? new stdClass();
        data_set($metadata, 'notified_at', $notifiedAt);

        $this->attributes['metadata'] = json_encode($metadata);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param DatabaseBuilder $query
     *
     * @return ResultQuery
     */
    public function newEloquentBuilder($query): ResultQuery
    {
        return new ResultQuery($query);
    }
}
