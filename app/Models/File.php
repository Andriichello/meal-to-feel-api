<?php

namespace App\Models;

use App\Providers\MorphServiceProvider;
use App\Queries\Models\FileQuery;
use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use stdClass;

/**
 * Class File.
 *
 * @property int $id
 * @property int|null $context_id
 * @property string|null $context_type
 * @property string|null $file_id
 * @property string|null $unique_id
 * @property string $disk
 * @property string $disk_path
 * @property string $disk_name
 * @property string|null $path
 * @property string|null $type
 * @property string|null $extension
 * @property int|null $size
 * @property object|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property string $url
 * @property string $folder
 * @property string $full_path
 * @property int|null $quality
 * @property string|null $exception
 *
 * @property Model|null $context
 * @property Result[]|Collection $results
 * @property File[]|Collection $variants
 * @property File[]|Collection $webps
 *
 * @method static FileQuery query()
 * @method static FileFactory factory(...$parameters)
 */
class File extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'context_id',
        'context_type',
        'file_id',
        'unique_id',
        'disk',
        'disk_path',
        'disk_name',
        'path',
        'type',
        'extension',
        'size',
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
        'context',
        'results',
        'variants',
        'webps',
    ];

    /**
     * Get the context associated with the model.
     *
     * @return MorphTo
     */
    public function context(): MorphTo
    {
        return $this->morphTo(
            'context',
            'context_type',
            'context_id',
            'id'
        );
    }

    /**
     * Associated results relation query.
     *
     * @return HasMany
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'file_id', 'id');
    }

    /**
     * Get the variants associated with the model.
     *
     * @return HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(File::class, 'context_id', 'id')
            ->where('context_type', MorphServiceProvider::slugOf(static::class));
    }

    /**
     * Get the WebPs associated with the model.
     *
     * @return HasMany
     */
    public function webps(): HasMany
    {
        return $this->variants()
            ->where(function (FileQuery $query) {
                $query->where('type', 'image/webp')
                    ->orWhere('extension', 'webp');
            });
    }

    /**
     * Url for accessing the file.
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            // @phpstan-ignore-next-line
            get: fn() => Storage::disk($this->disk)
                ->temporaryUrl($this->full_path, now()->addHour()),
        );
    }

    /**
     * Path to the folder, in which the file resides.
     */
    protected function folder(): Attribute
    {
        return Attribute::make(
            get: fn() => Str::of($this->disk_path ?? '')
                ->beforeLast($this->disk_name)
                ->value(),
        );
    }

    /**
     * Full path to the file.
     */
    protected function fullPath(): Attribute
    {
        return Attribute::make(
            get: fn() => Str::of($this->disk_path ?? '')
                ->beforeLast($this->disk_name)
                ->finish('/')
                ->append($this->disk_name)
                ->value(),
        );
    }

    /**
     * Get `quality` attribute from `metadata`.
     *
     * @return int|null
     */
    public function getQualityAttribute(): ?int
    {
        return data_get($this->metadata, 'quality');
    }

    /**
     * Set `quality` attribute on `metadata`.
     *
     * @param int|null $quality
     *
     * @return void
     */
    public function setQualityAttribute(int|null $quality): void
    {
        $metadata = $this->metadata ?? new stdClass();
        data_set($metadata, 'quality', $quality);

        $this->attributes['metadata'] = json_encode($metadata);
    }

    /**
     * Get `exception` attribute from `metadata`.
     *
     * @return string|null
     */
    public function getExceptionAttribute(): ?string
    {
        return data_get($this->metadata, 'exception');
    }

    /**
     * Set `exception` attribute on `metadata`.
     *
     * @param string|null $exception
     *
     * @return void
     */
    public function setExceptionAttribute(string|null $exception): void
    {
        $metadata = $this->metadata ?? new stdClass();
        data_set($metadata, 'exception', $exception);

        $this->attributes['metadata'] = json_encode($metadata);
    }

    /**
     * Determines if current file is an image.
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return Str::of($this->type ?? '')
            ->startsWith(['image/', 'photo']);
    }

    /**
     * Get slug for the given model or class. The same slug
     * is used for generating folder for the file.
     *
     * @param Model|string $context
     *
     * @return string
     */
    public static function slugFor(Model|string $context): string
    {
        return MorphServiceProvider::slugOf($context);
    }

    /**
     * Get folder prefix for the given model or class.
     *
     * @param Model|string $context
     *
     * @return string
     */
    public static function prefixFor(Model|string $context): string
    {
        return Str::of(static::slugFor($context))
            ->start('/')
            ->finish('/')
            ->value();
    }

    /**
     * Get folder for the given model.
     *
     * @param Model $context
     *
     * @return string
     */
    public static function folderFor(Model $context): string
    {
        return Str::of(static::prefixFor($context))
            ->append($context->getKey())
            ->finish('/')
            ->value();
    }

    /**
     * Get hash for file under given path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function hash(string $path): string
    {
        return md5(md5_file($path) . microtime());
    }

    /**
     * Instantiate a new MorphTo relationship.
     *
     * @param Builder $query
     * @param Model $parent
     * @param string $foreignKey
     * @param string $ownerKey
     * @param string $type
     * @param string $relation
     *
     * @return MorphTo
     */
    protected function newMorphTo(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation): MorphTo
    {
        if ($relation === 'context' && is_string($this->context_type) && strlen($this->context_type)) {
            $contextClass = is_subclass_of($this->context_type, Model::class)
                ? $this->context_type : Relation::getMorphedModel($this->context_type);

            if ($contextClass) {
                $contextModel = new $contextClass();

                if ($contextModel instanceof Model) {
                    $ownerKey = $contextModel->getKeyName();
                }
            }
        }

        return new MorphTo($query, $parent, $foreignKey, $ownerKey, $type, $relation);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param DatabaseBuilder $query
     *
     * @return FileQuery
     */
    public function newEloquentBuilder($query): FileQuery
    {
        return new FileQuery($query);
    }
}
