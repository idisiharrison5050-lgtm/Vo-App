<?php

namespace Tests\Unit;

use App\Domain\Marketplace\PurchaseNumberAction;
use App\Domain\Wallet\WalletLedger;
use App\Models\Country;
use App\Models\NumberAssignment;
use App\Models\NumberOffer;
use App\Models\Order;
use App\Models\PhoneNumber;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\WalletAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseNumberActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_debits_wallet_and_creates_persistent_assignment(): void
    {
        $user = User::factory()->create();
        $country = Country::create([
            'iso2' => 'NG',
            'iso3' => 'NGA',
            'name' => 'Nigeria',
            'dialing_code' => '+234',
            'is_active' => true,
        ]);
        $service = Service::create([
            'slug' => 'sms',
            'name' => 'SMS',
            'description' => 'Inbound SMS',
            'is_active' => true,
        ]);
        $provider = Provider::create([
            'slug' => 'inventory',
            'name' => 'Inventory Provider',
            'status' => 'active',
            'capabilities' => ['sms'],
        ]);
        $offer = NumberOffer::create([
            'country_id' => $country->id,
            'service_id' => $service->id,
            'provider_id' => $provider->id,
            'term_type' => 'annual',
            'price_minor' => 12000,
            'setup_fee_minor' => 500,
            'currency' => 'USD',
            'billing_interval_days' => 365,
            'auto_renew_supported' => true,
            'is_active' => true,
        ]);
        $phone = PhoneNumber::create([
            'country_id' => $country->id,
            'provider_id' => $provider->id,
            'number' => '+2348012345678',
            'normalized_number' => '+2348012345678',
            'type' => 'mobile',
            'status' => 'available',
            'capabilities' => ['sms'],
        ]);

        app(WalletLedger::class)->credit($user->id, 20000, 'USD', 'funding-purchase-test', 'funding');

        $order = app(PurchaseNumberAction::class)->execute(
            $user->id,
            $offer->id,
            '550e8400-e29b-41d4-a716-446655440000',
            true,
        );

        $this->assertSame('completed', $order->status);
        $this->assertSame(12500, $order->total_minor);
        $this->assertSame('assigned', $phone->fresh()->status);
        $this->assertSame(7500, WalletAccount::where('user_id', $user->id)->value('available_minor'));
        $this->assertDatabaseHas('number_assignments', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'term_type' => 'annual',
            'status' => 'active',
            'auto_renew' => true,
        ]);
    }

    public function test_same_idempotency_key_does_not_create_a_second_purchase(): void
    {
        $user = User::factory()->create();
        $country = Country::create([
            'iso2' => 'US',
            'iso3' => 'USA',
            'name' => 'United States',
            'dialing_code' => '+1',
            'is_active' => true,
        ]);
        $provider = Provider::create([
            'slug' => 'inventory-us',
            'name' => 'Inventory US',
            'status' => 'active',
            'capabilities' => ['sms'],
        ]);
        $offer = NumberOffer::create([
            'country_id' => $country->id,
            'service_id' => null,
            'provider_id' => $provider->id,
            'term_type' => 'monthly',
            'price_minor' => 5000,
            'setup_fee_minor' => 0,
            'currency' => 'USD',
            'billing_interval_days' => 30,
            'auto_renew_supported' => true,
            'is_active' => true,
        ]);
        PhoneNumber::create([
            'country_id' => $country->id,
            'provider_id' => $provider->id,
            'number' => '+14155550188',
            'normalized_number' => '+14155550188',
            'type' => 'mobile',
            'status' => 'available',
            'capabilities' => ['sms'],
        ]);

        app(WalletLedger::class)->credit($user->id, 10000, 'USD', 'funding-idempotency-test', 'funding');

        $key = '550e8400-e29b-41d4-a716-446655440001';
        $first = app(PurchaseNumberAction::class)->execute($user->id, $offer->id, $key);
        $second = app(PurchaseNumberAction::class)->execute($user->id, $offer->id, $key);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Order::where('user_id', $user->id)->count());
        $this->assertSame(5000, WalletAccount::where('user_id', $user->id)->value('available_minor'));
        $this->assertSame(1, NumberAssignment::where('user_id', $user->id)->count());
    }
}
