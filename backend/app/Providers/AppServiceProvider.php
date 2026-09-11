<?php

namespace App\Providers;

use App\Domain\Providers\InventoryNumberProvider;
use App\Domain\Providers\ProviderManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProviderManager::class, function ($app) {
            $manager = new ProviderManager();
            $manager->register('inventory', $app->make(InventoryNumberProvider::class));

            return $manager;
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
