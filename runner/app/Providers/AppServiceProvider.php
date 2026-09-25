<?php

namespace App\Providers;

use App\Runner\PhpRunner;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PhpRunner::class, fn () => new PhpRunner(sys_get_temp_dir(), config('runner.timeout')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
