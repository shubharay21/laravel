<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\LBInterface;
use App\Services\LBService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LBInterface::class, LBService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
