<?php

namespace App\Providers;

use App\Models\TripTicket;
use App\Models\VehicleMaintenance;
use App\Observers\TripTicketObserver;
use App\Observers\VehicleMaintenanceObserver;
use Illuminate\Support\Facades\URL;
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
        if (str_contains(config('app.url'), 'ngrok')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        VehicleMaintenance::observe(VehicleMaintenanceObserver::class);
        TripTicket::observe(TripTicketObserver::class);
    }
}