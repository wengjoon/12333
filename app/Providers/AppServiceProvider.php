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
        // Add production caching with longer expiration times
        if (app()->environment('production')) {
            // Increase cache duration for frequently accessed data
            config(['cache.ttl' => 86400]); // 24 hours
            
            // Force HTTPS in production
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
                \URL::forceScheme('https');
            }
        }
    }
}