<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Payments\MockPaymentProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MockPaymentProvider::class, function () {
            return new MockPaymentProvider(
                config('services.mock_payment.mode', 'success')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
