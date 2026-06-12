<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use App\Services\InvoiceService;
use Illuminate\Support\ServiceProvider;
use Razorpay\Api\Api;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Razorpay API
        $this->app->singleton(Api::class, function ($app) {
            return new Api(
                config('services.razorpay.key_id'),
                config('services.razorpay.key_secret')
            );
        });

        // Register InvoiceService as singleton
        $this->app->singleton(InvoiceService::class, fn ($app) => new InvoiceService());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Order Observer — auto-generates invoice on 'delivered' status
        Order::observe(OrderObserver::class);
    }
}
