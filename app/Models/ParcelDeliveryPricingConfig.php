<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParcelDeliveryPricingConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'config_name',
        'base_rates',
        'distance_rate_per_km',
        'weight_rates',
        'size_rate_per_cubic_cm',
        'delivery_type_multipliers',
        'surcharges',
        'peak_hours',
        'holidays',
        'insurance_rate',
        'minimum_charge',
        'currency',
        'currency_symbol',
        'decimal_places',
        'is_active',
        'version',
        'description',
    ];

    protected $casts = [
        'base_rates' => 'array',
        'weight_rates' => 'array',
        'delivery_type_multipliers' => 'array',
        'surcharges' => 'array',
        'peak_hours' => 'array',
        'holidays' => 'array',
        'distance_rate_per_km' => 'decimal:2',
        'size_rate_per_cubic_cm' => 'decimal:4',
        'insurance_rate' => 'decimal:4',
        'minimum_charge' => 'decimal:2',
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
     * Get default pricing configuration for a client
     */
    public static function getDefaultConfig($clientIdentifier)
    {
        return static::active()
            ->forClient($clientIdentifier)
            ->first();
    }

    /**
     * Create default pricing configuration for a client
     */
    public static function createDefaultConfig($clientIdentifier)
    {
        return static::create([
            'client_identifier' => $clientIdentifier,
            'config_name' => 'Default Pricing',
            'base_rates' => [
                'express' => 150.00,
                'standard' => 100.00,
                'economy' => 80.00,
            ],
            'distance_rate_per_km' => 5.00,
            'weight_rates' => [
                '0-5' => 2.00,
                '5-10' => 3.00,
                '10-20' => 4.00,
                '20+' => 5.00,
            ],
            'size_rate_per_cubic_cm' => 0.01,
            'delivery_type_multipliers' => [
                'express' => 1.5,
                'standard' => 1.0,
                'economy' => 0.8,
            ],
            'surcharges' => [
                'fuel' => 0.10,
                'peak_hour' => 0.15,
                'weekend' => 0.20,
                'holiday' => 0.30,
                'urgent' => 0.25,
            ],
            'peak_hours' => [
                ['07:00', '09:00'],
                ['17:00', '20:00']
            ],
            'holidays' => [
                '2024-01-01', '2024-01-02', '2024-04-09', '2024-04-10',
                '2024-05-01', '2024-06-12', '2024-08-26', '2024-11-30',
                '2024-12-25', '2024-12-30', '2025-01-01', '2025-01-02',
                '2025-04-09', '2025-04-10', '2025-05-01', '2025-06-12',
                '2025-08-26', '2025-11-30', '2025-12-25', '2025-12-30'
            ],
            'insurance_rate' => 0.01,
            'minimum_charge' => 50.00,
            'currency' => 'PHP',
            'currency_symbol' => '₱',
            'decimal_places' => 2,
            'is_active' => true,
            'version' => 'v1.0',
        ]);
    }

    /**
     * Get base rate for delivery type
     */
    public function getBaseRate($deliveryType): float
    {
        $rates = $this->base_rates ?? [];
        return $rates[$deliveryType] ?? 100.00;
    }

    /**
     * Get delivery type multiplier
     */
    public function getDeliveryTypeMultiplier($deliveryType): float
    {
        $multipliers = $this->delivery_type_multipliers ?? [];
        return $multipliers[$deliveryType] ?? 1.0;
    }

    /**
     * Get weight rate for weight tier
     */
    public function getWeightRate($weight): float
    {
        $rates = $this->weight_rates ?? [];
        
        if ($weight <= 5) {
            return $rates['0-5'] ?? 2.00;
        } elseif ($weight <= 10) {
            return $rates['5-10'] ?? 3.00;
        } elseif ($weight <= 20) {
            return $rates['10-20'] ?? 4.00;
        } else {
            return $rates['20+'] ?? 5.00;
        }
    }

    /**
     * Get surcharge percentage
     */
    public function getSurcharge($type): float
    {
        $surcharges = $this->surcharges ?? [];
        return $surcharges[$type] ?? 0;
    }

    /**
     * Check if time is within peak hours
     */
    public function isPeakHour($time): bool
    {
        $peakHours = $this->peak_hours ?? [];
        $timeStr = $time instanceof \DateTime ? $time->format('H:i') : $time;
        
        foreach ($peakHours as $period) {
            if (count($period) === 2) {
                [$start, $end] = $period;
                if ($timeStr >= $start && $timeStr <= $end) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if date is a holiday
     */
    public function isHoliday($date): bool
    {
        $holidays = $this->holidays ?? [];
        $dateStr = $date instanceof \DateTime ? $date->format('Y-m-d') : $date;
        
        return in_array($dateStr, $holidays);
    }

    /**
     * Check if date is weekend
     */
    public function isWeekend($date): bool
    {
        $dateObj = $date instanceof \DateTime ? $date : new \DateTime($date);
        $dayOfWeek = (int) $dateObj->format('w');
        
        return $dayOfWeek === 0 || $dayOfWeek === 6; // Sunday or Saturday
    }
}

