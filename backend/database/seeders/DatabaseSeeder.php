<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $countries = [
            ['iso2' => 'US', 'iso3' => 'USA', 'name' => 'United States', 'dialing_code' => '+1'],
            ['iso2' => 'CA', 'iso3' => 'CAN', 'name' => 'Canada', 'dialing_code' => '+1'],
            ['iso2' => 'GB', 'iso3' => 'GBR', 'name' => 'United Kingdom', 'dialing_code' => '+44'],
            ['iso2' => 'NG', 'iso3' => 'NGA', 'name' => 'Nigeria', 'dialing_code' => '+234'],
            ['iso2' => 'DE', 'iso3' => 'DEU', 'name' => 'Germany', 'dialing_code' => '+49'],
            ['iso2' => 'FR', 'iso3' => 'FRA', 'name' => 'France', 'dialing_code' => '+33'],
            ['iso2' => 'AU', 'iso3' => 'AUS', 'name' => 'Australia', 'dialing_code' => '+61'],
            ['iso2' => 'NL', 'iso3' => 'NLD', 'name' => 'Netherlands', 'dialing_code' => '+31'],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(['iso2' => $country['iso2']], $country);
        }

        $services = [
            ['slug' => 'sms', 'name' => 'SMS', 'description' => 'Inbound SMS for supported numbers.'],
            ['slug' => 'voice', 'name' => 'Voice', 'description' => 'Calling capability on supported numbers.'],
            ['slug' => 'business-messaging', 'name' => 'Business messaging', 'description' => 'Numbers intended for legitimate business communications.'],
            ['slug' => 'account-verification', 'name' => 'Account verification', 'description' => 'Verification workflows where the provider and destination service permit their use.'],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['slug' => $service['slug']], $service);
        }

        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
