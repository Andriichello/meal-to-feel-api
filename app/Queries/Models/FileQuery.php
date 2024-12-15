<?php

namespace App\Queries\Models;

use App\Models\File;
use App\Providers\MorphServiceProvider;
use App\Queries\BaseQuery;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as DbBuilder;
use Illuminate\Support\Arr;

/**
 * Class FileQuery.
 *
 * @property File $model
 *
 * @method FileQuery select($columns = ['*'])
 * @method FileQuery whereKey($id)
 * @method File|null find($id, $columns = ['*'])
 * @method File findOrFail($id, $columns = ['*'])
 * @method File|null first($columns = ['*'])
 * @method File firstOrFail($columns = ['*'])
 * @method File firstOrNew(array $attributes = [], array $values = [])
 * @method File make(array $attributes = [])
 * @method File create(array $attributes = [])
 * @method File updateOrCreate(array $attributes, array $values = [])
 */
class FileQuery extends BaseQuery
{
    /**
     * Get files where `context_type` is for the given class or morph.
     *
     * @param string $classOrMorph
     *
     * @return FileQuery
     */
    public function whereContext(string $classOrMorph): FileQuery
    {
        if (str_contains($classOrMorph, '\\') || class_exists($classOrMorph)) {
            $contextType = data_get(
                array_flip(Relation::$morphMap),
                $classOrMorph,
                MorphServiceProvider::slugOf($classOrMorph)
            );
        }

        $contextType = $contextType ?? MorphServiceProvider::slugOf($classOrMorph);

        $this->where('context_type', $contextType);

        return $this;
    }

    /**
     * Get files where `context_id` is one of given values.
     *
     * @param Closure|Builder|DbBuilder|array|int $id
     * @return FileQuery
     */
    public function whereContextId(Closure|Builder|DbBuilder|array|int $id): FileQuery
    {
        if ($id instanceof Closure) {
            $this->whereIn('context_id', $id);
        }

        if ($id instanceof Builder || $id instanceof DbBuilder) {
            $this->whereIn('context_id', $id);
        }

        if (is_array($id) || is_scalar($id)) {
            $this->whereIn('context_id', Arr::wrap($id));
        }

        return $this;
    }

    /**
     * Filter down to image files (based on `type`).
     *
     * @return static
     */
    public function images(): static
    {
        $this->where(function (FileQuery $q) {
            $q->whereLike('type', 'image/%')
                ->orWhereLike('type', 'photo%');
        });

        return $this;
    }
}
