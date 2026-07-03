<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Public, unauthenticated endpoints on shared report links — keyed by IP + slug so
        // one abusive visitor can't also throttle everyone else viewing a different report.

        // Guessing a share's password: tight, since this is a brute-force vector.
        RateLimiter::for('share-unlock', function ($request) {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->route('slug'));
        });

        RateLimiter::for('guest-comments', function ($request) {
            return Limit::perMinute(10)->by($request->ip().'|'.$request->route('slug'));
        });

        RateLimiter::for('agreement-sign', function ($request) {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->route('slug'));
        });
    }
}
