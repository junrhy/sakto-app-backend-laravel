<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportationPricingConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'config_name',
        'config_type',
        'base_rates',
        'distance_rates',
        'weight_rates',
        'special_handling_rates',
        'surcharges',
        'peak_hours',
        'holidays',
        'additional_costs',
        'overtime_hours',
        'currency',
        'currency_symbol',
        'decimal_places',
        'is_active',
        'version',
        'description',
    ];

    protected $casts = [
        'base_rates' => 'array',
        'distance_rates' => 'array',
        'weight_rates' => 'array',
        'special_handling_rates' => 'array',
        'surcharges' => 'array',
        'peak_hours' => 'array',
        'holidays' => 'array',
        'additional_costs' => 'array',
        'overtime_hours' => 'array',
        'is_active' => 'boolean',
        'decimal_places' => 'integer',
    ];

    /**
     * Scope for active configurations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific client
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope for specific config type
     */
    public function scopeOfType($query, $configType)
    {
        return $query->where('config_type', $configType);
    }

    /**
     * Get default pricing configuration for a client
     */
    public static function getDefaultConfig($clientIdentifier)
    {
        return static::active()
            ->forClient($clientIdentifier)
            ->ofType('default')
            ->first();
    }

    /**
     * Get all active configurations for a client
     */
    public static function getClientConfigs($clientIdentifier)
    {
        return static::active()
            ->forClient($clientIdentifier)
            ->get()
            ->keyBy('config_type');
    }

    /**
     * Create default pricing configuration for a client
     */
    public static function createDefaultConfig($clientIdentifier)
    {
        return static::create([
            'client_identifier' => $clientIdentifier,
            'config_name' => 'Default Pricing',
            'config_type' => 'default',
            'base_rates' => [
                'small' => 3000,    // Small trucks (1-3 tons)
                'medium' => 5000,   // Medium trucks (4-8 tons)
                'large' => 8000,    // Large trucks (9-15 tons)
                'heavy' => 12000,   // Heavy trucks (16+ tons)
            ],
            'distance_rates' => [
                'local' => 50,      // Within city
                'provincial' => 75, // Provincial routes
                'intercity' => 100, // Inter-city routes
            ],
            'weight_rates' => [
                'light' => 0,       // 0-2 tons
                'medium' => 500,    // 3-5 tons
                'heavy' => 1000,    // 6-10 tons
                'very_heavy' => 2000, // 11+ tons
            ],
            'special_handling_rates' => [
                'refrigeration' => 2000,    // Per day
                'special_equipment' => 1500, // Per day
                'escort' => 3000,           // Per day
                'urgent' => 5000,           // One-time fee
            ],
            'surcharges' => [
                'fuel' => 0.15,     // 15% fuel surcharge
                'peak_hour' => 0.20, // 20% peak hour surcharge
                'weekend' => 0.25,  // 25% weekend surcharge
                'holiday' => 0.50,  // 50% holiday surcharge
                'overtime' => 0.30, // 30% overtime surcharge
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
                'insurance_rate' => 0.02, // 2% insurance rate
                'toll_rates' => [
                    'local' => 0,      // No tolls for local routes
                    'provincial' => 50, // Average toll per 100km
                    'intercity' => 100, // Average toll per 100km
                ],
                'parking_fee_per_day' => 200, // ₱200 per day for parking
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
            'description' => 'Default pricing configuration for transportation services',
        ]);
    }

    /**
     * Get base rate for truck type
     */
    public function getBaseRate($truckType)
    {
        return $this->base_rates[$truckType] ?? 0;
    }

    /**
     * Get distance rate for route type
     */
    public function getDistanceRate($routeType)
    {
        return $this->distance_rates[$routeType] ?? 0;
    }

    /**
     * Get weight rate for weight category
     */
    public function getWeightRate($weightCategory)
    {
        return $this->weight_rates[$weightCategory] ?? 0;
    }

    /**
     * Get special handling rate for service type
     */
    public function getSpecialHandlingRate($serviceType)
    {
        return $this->special_handling_rates[$serviceType] ?? 0;
    }

    /**
     * Get surcharge percentage for surcharge type
     */
    public function getSurcharge($surchargeType)
    {
        return $this->surcharges[$surchargeType] ?? 0;
    }

    /**
     * Check if time is within peak hours
     */
    public function isPeakHour($time)
    {
        $timeStr = $time->format('H:i');
        
        foreach ($this->peak_hours as [$start, $end]) {
            if ($timeStr >= $start && $timeStr <= $end) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if date is a holiday
     */
    public function isHoliday($date)
    {
        return in_array($date->format('Y-m-d'), $this->holidays);
    }

    /**
     * Check if time is overtime
     */
    public function isOvertime($time)
    {
        $hour = $time->hour;
        $startHour = (int) substr($this->overtime_hours['start'], 0, 2);
        $endHour = (int) substr($this->overtime_hours['end'], 0, 2);
        
        return $hour >= $startHour || $hour <= $endHour;
    }

    /**
     * Get insurance rate
     */
    public function getInsuranceRate()
    {
        return $this->additional_costs['insurance_rate'] ?? 0.02;
    }

    /**
     * Get toll rate for route type
     */
    public function getTollRate($routeType)
    {
        return $this->additional_costs['toll_rates'][$routeType] ?? 0;
    }

    /**
     * Get parking fee per day
     */
    public function getParkingFeePerDay()
    {
        return $this->additional_costs['parking_fee_per_day'] ?? 200;
    }

    /**
     * Format currency amount
     */
    public function formatCurrency($amount)
    {
        return $this->currency_symbol . number_format($amount, $this->decimal_places);
    }
}
