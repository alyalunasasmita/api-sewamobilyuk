<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter; 
use Illuminate\Cache\RateLimiting\Limit; 
use Illuminate\Http\Request; 

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
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip(), '|' , strlower($request->email))
                ->response(function () {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Terlalu banyak percobaan login, coba lagi nanti'
                    ], 429);
                });
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Terlalu banyak permintaan, coba lagi nanti'
                    ], 429);
                });
        });

        RateLimiter::for('password', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip() ,'|',strlower($request->email)) 
                ->response(function () {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Terlalu banyak permintaan, coba lagi nanti'
                    ], 429);
                });
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip() ,'|',strlower($request->email)) 
                ->response(function () {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Terlalu banyak permintaan, coba lagi nanti'
                    ], 429);
                });
        });

        RateLimiter::for('showcar', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->ip() ,'|',strlower($request->email)) 
                ->response(function () {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Terlalu banyak permintaan, coba lagi nanti'
                    ], 429);
                });
        });
    }
}
