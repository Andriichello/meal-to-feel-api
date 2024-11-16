<?php

namespace App\Models\Traits;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Trait HasFiles.
 *
 * @mixin Model
 *
 * @property File[]|Collection $files
 */
trait HasFiles
{
    /**
     * Files related to the model.
     *
     * @return MorphMany
     */
    public function files(): MorphMany
    {
        return $this->morphMany(
            File::class,
            'files',
            'context_type',
            'context_id',
            'id'
        );
    }
}
