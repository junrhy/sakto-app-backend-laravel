<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TransportationPricingConfig;

class TransportationPricingConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get unique client identifiers from transportation_fleets table
        $clientIdentifiers = \DB::table('transportation_fleets')
            ->distinct()
            ->pluck('client_identifier')
            ->filter()
            ->values();

        foreach ($clientIdentifiers as $clientIdentifier) {
            // Create default pricing configuration for each client
            TransportationPricingConfig::createDefaultConfig($clientIdentifier);
            
            $this->command->info("Created default pricing config for client: {$clientIdentifier}");
        }

        // Create a sample premium pricing configuration for demonstration
        if ($clientIdentifiers->count() > 0) {
            $sampleClient = $clientIdentifiers->first();
            
            TransportationPricingConfig::create([
                'client_identifier' => $sampleClient,
                'config_name' => 'Premium Pricing',
                'config_type' => 'premium',
                'base_rates' => [
                    'small' => 4000,    // Higher rates for premium service
                    'medium' => 6500,
                    'large' => 10000,
                    'heavy' => 15000,
                ],
                'distance_rates' => [
                    'local' => 60,
                    'provincial' => 90,
                    'intercity' => 120,
                ],
                'weight_rates' => [
                    'light' => 0,
                    'medium' => 600,
                    'heavy' => 1200,
                    'very_heavy' => 2500,
                ],
                'special_handling_rates' => [
                    'refrigeration' => 2500,
                    'special_equipment' => 2000,
                    'escort' => 4000,
                    'urgent' => 7000,
                ],
                'surcharges' => [
                    'fuel' => 0.12,     // Lower fuel surcharge for premium
                    'peak_hour' => 0.15,
                    'weekend' => 0.20,
                    'holiday' => 0.40,
                    'overtime' => 0.25,
                ],
                'peak_hours' => [
                    ['06:00', '09:00'],
                    ['17:00', '20:00']
                ],
                'holidays' => [
                    '2024-01-01', '2024-01-02', '2024-04-09', '2024-04-10',
                    '2024-05-01', '2024-06-12', '2024-08-26', '2024-11-30',
                    '2024-12-25', '2024-12-30', '2025-01-01', '2025-01-02',
                    '2025-04-09', '2025-04-10', '2025-05-01', '2025-06-12',
                    '2025-08-26', '2025-11-30', '2025-12-25', '2025-12-30'
                ],
                'additional_costs' => [
                    'insurance_rate' => 0.015, // Lower insurance rate for premium
                    'toll_rates' => [
                        'local' => 0,
                        'provincial' => 40,
                        'intercity' => 80,
                    ],
                    'parking_fee_per_day' => 150,
                ],
                'overtime_hours' => [
                    'start' => '22:00',
                    'end' => '06:00'
                ],
                'currency' => 'PHP',
                'currency_symbol' => '₱',
                'decimal_places' => 2,
                'is_active' => true,
                'version' => 'v1.0',
                'description' => 'Premium pricing configuration with enhanced service rates',
            ]);
            
            $this->command->info("Created premium pricing config for client: {$sampleClient}");
        }
    }
}
