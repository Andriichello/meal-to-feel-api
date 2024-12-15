<?php

namespace App\Observers;

use App\Models\Meal;

/**
 * Class MealObserver.
 */
class MealObserver
{
    /**
     * Handle the Meal "saved" event.
     */
    public function saved(Meal $meal): void
    {
//        if ($meal->status === MealStatus::Pending) {
//
//        }
    }
}
