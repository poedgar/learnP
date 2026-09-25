<?php

namespace App\Providers;

use App\Sandbox\SqlSandbox;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SqlSandbox::class, fn () => new SqlSandbox(database_path('datasets')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Each sandbox run spawns a PHP process, so keep bursts in check.
        RateLimiter::for('sandbox', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
    }
}
