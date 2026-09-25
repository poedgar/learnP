<?php

namespace App\Providers;

use App\Sandbox\HttpPhpSandbox;
use App\Sandbox\PhpSandbox;
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

        $this->app->singleton(PhpSandbox::class, fn () => new HttpPhpSandbox(
            config('services.runner.url'),
            config('services.runner.secret'),
            resource_path('php/check-harness.php'),
        ));
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
