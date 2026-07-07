<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Redactions\DepecheModels;
use App\Observers\DepecheModelsObserver;

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
        DepecheModels::observe(DepecheModelsObserver::class);
    }
}
