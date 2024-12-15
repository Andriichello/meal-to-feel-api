<?php

namespace App\Providers;

use App\Models\File;
use App\Models\Meal;
use App\Models\Result;
use App\Observers\FileObserver;
use App\Observers\MealObserver;
use App\Observers\ResultObserver;
use Illuminate\Support\ServiceProvider;

/**
 * Class AppServiceProvider.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        File::observe(FileObserver::class);
        Meal::observe(MealObserver::class);
        Result::observe(ResultObserver::class);
    }
}
