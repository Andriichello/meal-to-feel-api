<?php

namespace App\Queries\Models;

use App\Models\Meal;
use App\Queries\BaseQuery;

/**
 * Class MealQuery.
 *
 * @property Meal $model
 *
 * @method MealQuery select($columns = ['*'])
 * @method MealQuery whereKey($id)
 * @method Meal|null find($id, $columns = ['*'])
 * @method Meal findOrFail($id, $columns = ['*'])
 * @method Meal|null first($columns = ['*'])
 * @method Meal firstOrFail($columns = ['*'])
 * @method Meal firstOrNew(array $attributes = [], array $values = [])
 * @method Meal make(array $attributes = [])
 * @method Meal create(array $attributes = [])
 * @method Meal updateOrCreate(array $attributes, array $values = [])
 *
 * @author Andrii Prykhodko <andriichello@gmail.com>
 * @package Speedgoat\Skeleton\Queries
 */
class MealQuery extends BaseQuery
{
    //
}
