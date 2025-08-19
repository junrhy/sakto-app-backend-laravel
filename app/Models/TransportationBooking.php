<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportationBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'truck_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_company',
        'pickup_location',
        'delivery_location',
        'pickup_date',
        'pickup_time',
        'delivery_date',
        'delivery_time',
        'cargo_description',
        'cargo_weight',
        'cargo_unit',
        'distance_km',
        'route_type',
        'special_requirements',
        'estimated_cost',
        'base_rate',
        'distance_rate',
        'weight_rate',
        'special_handling_rate',
        'fuel_surcharge',
        'peak_hour_surcharge',
        'weekend_surcharge',
        'holiday_surcharge',
        'driver_overtime_rate',
        'requires_refrigeration',
        'requires_special_equipment',
        'requires_escort',
        'is_urgent_delivery',
        'pickup_hour',
        'delivery_hour',
        'insurance_cost',
        'toll_fees',
        'parking_fees',
        'status',
        'notes',
        'booking_reference',
        'pricing_breakdown',
        'pricing_version',
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'delivery_date' => 'date',
        'pickup_time' => 'datetime:H:i',
        'delivery_time' => 'datetime:H:i',
        'cargo_weight' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'base_rate' => 'decimal:2',
        'distance_rate' => 'decimal:2',
        'weight_rate' => 'decimal:2',
        'special_handling_rate' => 'decimal:2',
        'fuel_surcharge' => 'decimal:2',
        'peak_hour_surcharge' => 'decimal:2',
        'weekend_surcharge' => 'decimal:2',
        'holiday_surcharge' => 'decimal:2',
        'driver_overtime_rate' => 'decimal:2',
        'requires_refrigeration' => 'boolean',
        'requires_special_equipment' => 'boolean',
        'requires_escort' => 'boolean',
        'is_urgent_delivery' => 'boolean',
        'pickup_hour' => 'datetime:H:i',
        'delivery_hour' => 'datetime:H:i',
        'insurance_cost' => 'decimal:2',
        'toll_fees' => 'decimal:2',
        'parking_fees' => 'decimal:2',
        'pricing_breakdown' => 'array',
    ];

    /**
     * Get the truck that owns the booking.
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(TransportationFleet::class, 'truck_id');
    }

    /**
     * Scope a query to only include bookings for a specific client.
     */
    public function scopeForClient($query, $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope a query to only include bookings with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending bookings.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    /**
     * Scope a query to only include confirmed bookings.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'Confirmed');
    }

    /**
     * Scope a query to only include completed bookings.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    /**
     * Scope a query to only include cancelled bookings.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'Cancelled');
    }

    /**
     * Generate a unique booking reference.
     */
    public static function generateBookingReference(): string
    {
        do {
            $reference = 'BK' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (self::where('booking_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Get the formatted pickup datetime.
     */
    public function getPickupDateTimeAttribute(): string
    {
        return $this->pickup_date->format('Y-m-d') . ' ' . $this->pickup_time;
    }

    /**
     * Get the formatted delivery datetime.
     */
    public function getDeliveryDateTimeAttribute(): string
    {
        return $this->delivery_date->format('Y-m-d') . ' ' . $this->delivery_time;
    }

    /**
     * Get the formatted cargo weight with unit.
     */
    public function getFormattedCargoWeightAttribute(): string
    {
        return number_format($this->cargo_weight, 2) . ' ' . $this->cargo_unit;
    }

    /**
     * Get the formatted estimated cost.
     */
    public function getFormattedEstimatedCostAttribute(): string
    {
        return $this->estimated_cost ? '₱' . number_format($this->estimated_cost, 2) : 'TBD';
    }
}
