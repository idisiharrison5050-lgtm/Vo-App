<?php

namespace Tests\Unit;

use App\Domain\Providers\InventoryNumberProvider;
use App\Domain\Providers\ProviderManager;
use App\Models\Provider;
use Tests\TestCase;

class ProviderManagerTest extends TestCase
{
    public function test_configured_provider_resolves_to_registered_driver(): void
    {
        config()->set('providers.drivers.inventory', ['driver' => 'inventory']);

        $manager = app(ProviderManager::class);
        $provider = new Provider([
            'slug' => 'inventory',
            'name' => 'Inventory',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(InventoryNumberProvider::class, $manager->driver($provider));
    }
}
