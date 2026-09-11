<?php

namespace App\Providers;

use App\Domain\Payments\PaymentManager;
use App\Domain\Providers\InventoryNumberProvider;
use App\Domain\Providers\ProviderManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProviderManager::class, function ($app) {
            $manager = new ProviderManager();
            $manager->register('inventory', $app->make(InventoryNumberProvider::class));

            return $manager;
        });

        $this->app->singleton(PaymentManager::class, function () {
            return new PaymentManager();
        });
    }

    public function boot(): void
    {
        //
    }
}
