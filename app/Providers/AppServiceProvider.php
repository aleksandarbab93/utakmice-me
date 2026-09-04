<?php

namespace App\Providers;

use App\Services\SStats\SStatsClient;
use Illuminate\Support\Carbon;
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
        // Kickoff times are stored in UTC (so they compare correctly against
        // now()-based live-window queries); this converts to wall-clock time
        // for the Crna Gora / Srbija / region audience only at display time.
        Carbon::macro('local', function () {
            /** @var Carbon $this */
            return $this->copy()->timezone('Europe/Belgrade');
        });
    }
}
