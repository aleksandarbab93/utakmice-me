<?php

namespace App\Providers;

use App\Services\SStats\SStatsClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SStatsClient::class, fn () => new SStatsClient(
            baseUrl: config('services.sstats.base_url'),
            apiKey: config('services.sstats.key'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
