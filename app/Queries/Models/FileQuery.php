<?php

namespace App\Queries\Models;

use App\Models\File;
use App\Queries\BaseQuery;

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
 *
 * @author Andrii Prykhodko <andriichello@gmail.com>
 * @package Speedgoat\Skeleton\Queries
 */
class FileQuery extends BaseQuery
{
    //
}
