<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\TransportationFleet;
use App\Models\TransportationPricingConfig;

class TransportationPricingService
{
    private $config;

    public function __construct($clientIdentifier = null)
    {
        if ($clientIdentifier) {
            $this->config = TransportationPricingConfig::getDefaultConfig($clientIdentifier);
            
            // Create default config if none exists
            if (!$this->config) {
                $this->config = TransportationPricingConfig::createDefaultConfig($clientIdentifier);
            }
        }
    }

    /**
     * Set a specific pricing configuration
     */
    public function setConfig(TransportationPricingConfig $config)
    {
        $this->config = $config;
    }

    public function calculatePricing(array $bookingData): array
    {
        $truck = TransportationFleet::find($bookingData['truck_id']);
        $pickupDate = Carbon::parse($bookingData['pickup_date']);
        $deliveryDate = Carbon::parse($bookingData['delivery_date']);
        $pickupTime = Carbon::parse($bookingData['pickup_time']);
        $deliveryTime = Carbon::parse($bookingData['delivery_time']);

        // Calculate duration
        $duration = $pickupDate->diffInDays($deliveryDate) + 1;

        // Determine truck type based on capacity
        $truckType = $this->getTruckType($truck->capacity);

        // Calculate base rate
        $baseRate = $this->config->getBaseRate($truckType) * $duration;

        // Calculate distance rate
        $distanceRate = $this->calculateDistanceRate($bookingData);

        // Calculate weight rate
        $weightRate = $this->calculateWeightRate($bookingData);

        // Calculate special handling rates
        $specialHandlingRate = $this->calculateSpecialHandlingRate($bookingData, $duration);

        // Calculate surcharges
        $fuelSurcharge = $baseRate * $this->config->getSurcharge('fuel');
        $peakHourSurcharge = $this->calculatePeakHourSurcharge($pickupTime, $deliveryTime, $baseRate);
        $weekendSurcharge = $this->calculateWeekendSurcharge($pickupDate, $deliveryDate, $baseRate);
        $holidaySurcharge = $this->calculateHolidaySurcharge($pickupDate, $deliveryDate, $baseRate);
        $driverOvertimeRate = $this->calculateOvertimeRate($pickupTime, $deliveryTime, $baseRate);

        // Calculate additional costs
        $insuranceCost = $this->calculateInsuranceCost($bookingData);
        $tollFees = $this->calculateTollFees($bookingData);
        $parkingFees = $this->calculateParkingFees($duration);

        // Calculate total
        $subtotal = $baseRate + $distanceRate + $weightRate + $specialHandlingRate;
        $surcharges = $fuelSurcharge + $peakHourSurcharge + $weekendSurcharge + $holidaySurcharge + $driverOvertimeRate;
        $additionalCosts = $insuranceCost + $tollFees + $parkingFees;
        $totalCost = $subtotal + $surcharges + $additionalCosts;

        return [
            'base_rate' => round($baseRate, 2),
            'distance_rate' => round($distanceRate, 2),
            'weight_rate' => round($weightRate, 2),
            'special_handling_rate' => round($specialHandlingRate, 2),
            'fuel_surcharge' => round($fuelSurcharge, 2),
            'peak_hour_surcharge' => round($peakHourSurcharge, 2),
            'weekend_surcharge' => round($weekendSurcharge, 2),
            'holiday_surcharge' => round($holidaySurcharge, 2),
            'driver_overtime_rate' => round($driverOvertimeRate, 2),
            'insurance_cost' => round($insuranceCost, 2),
            'toll_fees' => round($tollFees, 2),
            'parking_fees' => round($parkingFees, 2),
            'estimated_cost' => round($totalCost, 2),
            'pricing_breakdown' => [
                'truck_type' => $truckType,
                'duration_days' => $duration,
                'truck_capacity' => $truck->capacity,
                'distance_km' => $bookingData['distance_km'] ?? 0,
                'route_type' => $bookingData['route_type'] ?? 'local',
                'cargo_weight' => $bookingData['cargo_weight'],
                'cargo_unit' => $bookingData['cargo_unit'],
                'special_services' => $this->getSpecialServices($bookingData),
                'peak_hours' => $this->config->isPeakHour($pickupTime) || $this->config->isPeakHour($deliveryTime),
                'weekend_delivery' => $this->config->isWeekend($pickupDate) || $this->config->isWeekend($deliveryDate),
                'holiday_delivery' => $this->config->isHoliday($pickupDate) || $this->config->isHoliday($deliveryDate),
                'overtime_hours' => $this->config->isOvertime($pickupTime) || $this->config->isOvertime($deliveryTime),
                'config_used' => $this->config->config_name,
                'config_version' => $this->config->version,
            ],
            'pricing_version' => $this->config->version
        ];
    }

    private function getTruckType(int $capacity): string
    {
        if ($capacity <= 3) return 'small';
        if ($capacity <= 8) return 'medium';
        if ($capacity <= 15) return 'large';
        return 'heavy';
    }

    private function calculateDistanceRate(array $bookingData): float
    {
        $distance = $bookingData['distance_km'] ?? 0;
        $routeType = $bookingData['route_type'] ?? 'local';
        
        return $distance * $this->config->getDistanceRate($routeType);
    }

    private function calculateWeightRate(array $bookingData): float
    {
        $weight = $bookingData['cargo_weight'] ?? 0;
        $unit = $bookingData['cargo_unit'] ?? 'kg';

        // Convert to tons for calculation
        if ($unit === 'kg') $weight = $weight / 1000;
        elseif ($unit === 'tons') $weight = $weight;
        else $weight = $weight / 1000; // Default to kg conversion

        if ($weight <= 2) return 0;
        if ($weight <= 5) return $this->config->getWeightRate('medium') * $weight;
        if ($weight <= 10) return $this->config->getWeightRate('heavy') * $weight;
        return $this->config->getWeightRate('very_heavy') * $weight;
    }

    private function calculateSpecialHandlingRate(array $bookingData, int $duration): float
    {
        $rate = 0;

        if (!empty($bookingData['requires_refrigeration'])) {
            $rate += $this->config->getSpecialHandlingRate('refrigeration') * $duration;
        }

        if (!empty($bookingData['requires_special_equipment'])) {
            $rate += $this->config->getSpecialHandlingRate('special_equipment') * $duration;
        }

        if (!empty($bookingData['requires_escort'])) {
            $rate += $this->config->getSpecialHandlingRate('escort') * $duration;
        }

        if (!empty($bookingData['is_urgent_delivery'])) {
            $rate += $this->config->getSpecialHandlingRate('urgent');
        }

        return $rate;
    }

    private function calculatePeakHourSurcharge(Carbon $pickupTime, Carbon $deliveryTime, float $baseRate): float
    {
        $surcharge = 0;

        if ($this->config->isPeakHour($pickupTime)) {
            $surcharge += $baseRate * $this->config->getSurcharge('peak_hour');
        }

        if ($this->config->isPeakHour($deliveryTime)) {
            $surcharge += $baseRate * $this->config->getSurcharge('peak_hour');
        }

        return $surcharge;
    }

    private function calculateWeekendSurcharge(Carbon $pickupDate, Carbon $deliveryDate, float $baseRate): float
    {
        $surcharge = 0;

        if ($this->config->isWeekend($pickupDate)) {
            $surcharge += $baseRate * $this->config->getSurcharge('weekend');
        }

        if ($this->config->isWeekend($deliveryDate)) {
            $surcharge += $baseRate * $this->config->getSurcharge('weekend');
        }

        return $surcharge;
    }

    private function calculateHolidaySurcharge(Carbon $pickupDate, Carbon $deliveryDate, float $baseRate): float
    {
        $surcharge = 0;

        if ($this->config->isHoliday($pickupDate)) {
            $surcharge += $baseRate * $this->config->getSurcharge('holiday');
        }

        if ($this->config->isHoliday($deliveryDate)) {
            $surcharge += $baseRate * $this->config->getSurcharge('holiday');
        }

        return $surcharge;
    }

    private function calculateOvertimeRate(Carbon $pickupTime, Carbon $deliveryTime, float $baseRate): float
    {
        $surcharge = 0;

        if ($this->config->isOvertime($pickupTime)) {
            $surcharge += $baseRate * $this->config->getSurcharge('overtime');
        }

        if ($this->config->isOvertime($deliveryTime)) {
            $surcharge += $baseRate * $this->config->getSurcharge('overtime');
        }

        return $surcharge;
    }

    private function calculateInsuranceCost(array $bookingData): float
    {
        $cargoValue = $bookingData['cargo_value'] ?? 0;
        return $cargoValue * $this->config->getInsuranceRate();
    }

    private function calculateTollFees(array $bookingData): float
    {
        $distance = $bookingData['distance_km'] ?? 0;
        $routeType = $bookingData['route_type'] ?? 'local';

        return ($distance / 100) * $this->config->getTollRate($routeType);
    }

    private function calculateParkingFees(int $duration): float
    {
        return $duration * $this->config->getParkingFeePerDay();
    }



    private function getSpecialServices(array $bookingData): array
    {
        $services = [];

        if (!empty($bookingData['requires_refrigeration'])) $services[] = 'Refrigeration';
        if (!empty($bookingData['requires_special_equipment'])) $services[] = 'Special Equipment';
        if (!empty($bookingData['requires_escort'])) $services[] = 'Escort';
        if (!empty($bookingData['is_urgent_delivery'])) $services[] = 'Urgent Delivery';

        return $services;
    }
}
