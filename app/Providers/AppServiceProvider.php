<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TmdbService;
use App\Services\CachedApiService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register our cached service as the implementation of TmdbService
        $this->app->singleton(TmdbService::class, function ($app) {
            return new CachedApiService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}