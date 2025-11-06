<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\ParcelDeliveryPricingConfig;

class ParcelDeliveryPricingService
{
    private $config;

    public function __construct($clientIdentifier = null)
    {
        if ($clientIdentifier) {
            $this->config = ParcelDeliveryPricingConfig::getDefaultConfig($clientIdentifier);
            
            // Create default config if none exists
            if (!$this->config) {
                $this->config = ParcelDeliveryPricingConfig::createDefaultConfig($clientIdentifier);
            }
        }
    }

    /**
     * Set a specific pricing configuration
     */
    public function setConfig(ParcelDeliveryPricingConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Calculate pricing for a parcel delivery
     */
    public function calculatePricing(array $deliveryData): array
    {
        $deliveryType = $deliveryData['delivery_type'] ?? 'standard';
        $pickupDate = Carbon::parse($deliveryData['pickup_date']);
        $pickupTime = Carbon::parse($deliveryData['pickup_time']);
        $distance = $deliveryData['distance_km'] ?? 0;
        $weight = $deliveryData['package_weight'] ?? 0;
        $length = $deliveryData['package_length'] ?? 0;
        $width = $deliveryData['package_width'] ?? 0;
        $height = $deliveryData['package_height'] ?? 0;
        $packageValue = $deliveryData['package_value'] ?? 0;
        $isUrgent = $deliveryData['is_urgent'] ?? false;

        // Calculate base rate based on delivery type
        $baseRate = $this->config->getBaseRate($deliveryType);
        
        // Apply delivery type multiplier
        $deliveryTypeMultiplier = $this->config->getDeliveryTypeMultiplier($deliveryType);
        $baseRate = $baseRate * $deliveryTypeMultiplier;

        // Calculate distance rate
        $distanceRate = $this->calculateDistanceRate($distance);

        // Calculate weight rate
        $weightRate = $this->calculateWeightRate($weight);

        // Calculate size rate (volume-based)
        $sizeRate = $this->calculateSizeRate($length, $width, $height);

        // Calculate surcharges
        $fuelSurcharge = $baseRate * $this->config->getSurcharge('fuel');
        $peakHourSurcharge = $this->calculatePeakHourSurcharge($pickupTime, $baseRate);
        $weekendSurcharge = $this->calculateWeekendSurcharge($pickupDate, $baseRate);
        $holidaySurcharge = $this->calculateHolidaySurcharge($pickupDate, $baseRate);
        $urgentSurcharge = $isUrgent ? ($baseRate * $this->config->getSurcharge('urgent')) : 0;

        // Calculate insurance cost
        $insuranceCost = $this->calculateInsuranceCost($packageValue);

        // Calculate subtotal
        $subtotal = $baseRate + $distanceRate + $weightRate + $sizeRate;
        
        // Calculate total surcharges
        $totalSurcharges = $fuelSurcharge + $peakHourSurcharge + $weekendSurcharge + $holidaySurcharge + $urgentSurcharge;
        
        // Calculate total cost
        $totalCost = $subtotal + $totalSurcharges + $insuranceCost;
        
        // Apply minimum charge
        $minimumCharge = $this->config->minimum_charge;
        if ($totalCost < $minimumCharge) {
            $totalCost = $minimumCharge;
        }

        return [
            'base_rate' => round($baseRate, 2),
            'distance_rate' => round($distanceRate, 2),
            'weight_rate' => round($weightRate, 2),
            'size_rate' => round($sizeRate, 2),
            'delivery_type_multiplier' => round($deliveryTypeMultiplier, 2),
            'fuel_surcharge' => round($fuelSurcharge, 2),
            'peak_hour_surcharge' => round($peakHourSurcharge, 2),
            'weekend_surcharge' => round($weekendSurcharge, 2),
            'holiday_surcharge' => round($holidaySurcharge, 2),
            'urgent_surcharge' => round($urgentSurcharge, 2),
            'insurance_cost' => round($insuranceCost, 2),
            'estimated_cost' => round($totalCost, 2),
            'minimum_charge' => round($minimumCharge, 2),
            'pricing_breakdown' => [
                'delivery_type' => $deliveryType,
                'distance_km' => round($distance, 2),
                'package_weight_kg' => round($weight, 2),
                'package_volume_cm3' => round($length * $width * $height, 2),
                'package_value' => round($packageValue, 2),
                'is_urgent' => $isUrgent,
                'peak_hour' => $this->config->isPeakHour($pickupTime),
                'weekend_delivery' => $this->config->isWeekend($pickupDate),
                'holiday_delivery' => $this->config->isHoliday($pickupDate),
                'config_used' => $this->config->config_name,
                'config_version' => $this->config->version,
            ],
            'pricing_version' => $this->config->version
        ];
    }

    /**
     * Calculate distance rate
     */
    private function calculateDistanceRate(float $distance): float
    {
        return $distance * $this->config->distance_rate_per_km;
    }

    /**
     * Calculate weight rate
     */
    private function calculateWeightRate(float $weight): float
    {
        $ratePerKg = $this->config->getWeightRate($weight);
        return $weight * $ratePerKg;
    }

    /**
     * Calculate size rate based on volume
     */
    private function calculateSizeRate(float $length, float $width, float $height): float
    {
        if ($length <= 0 || $width <= 0 || $height <= 0) {
            return 0;
        }
        
        $volume = $length * $width * $height; // cubic cm
        return $volume * $this->config->size_rate_per_cubic_cm;
    }

    /**
     * Calculate peak hour surcharge
     */
    private function calculatePeakHourSurcharge(Carbon $pickupTime, float $baseRate): float
    {
        if ($this->config->isPeakHour($pickupTime)) {
            return $baseRate * $this->config->getSurcharge('peak_hour');
        }
        
        return 0;
    }

    /**
     * Calculate weekend surcharge
     */
    private function calculateWeekendSurcharge(Carbon $pickupDate, float $baseRate): float
    {
        if ($this->config->isWeekend($pickupDate)) {
            return $baseRate * $this->config->getSurcharge('weekend');
        }
        
        return 0;
    }

    /**
     * Calculate holiday surcharge
     */
    private function calculateHolidaySurcharge(Carbon $pickupDate, float $baseRate): float
    {
        if ($this->config->isHoliday($pickupDate)) {
            return $baseRate * $this->config->getSurcharge('holiday');
        }
        
        return 0;
    }

    /**
     * Calculate insurance cost
     */
    private function calculateInsuranceCost(float $packageValue): float
    {
        if ($packageValue <= 0) {
            return 0;
        }
        
        return $packageValue * $this->config->insurance_rate;
    }
}

