<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
        // Контракт возвращает массивы без обёртки {"data": ...}
        JsonResource::$wrap = null;

        // Rate limiting для публичных эндпоинтов гостя.
        RateLimiter::for('slots', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('bookings', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
