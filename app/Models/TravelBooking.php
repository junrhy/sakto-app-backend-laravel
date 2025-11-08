<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'travel_package_id',
        'booking_reference',
        'customer_name',
        'customer_email',
        'customer_contact_number',
        'travel_date',
        'travelers_count',
        'total_price',
        'status',
        'payment_status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'travel_date' => 'date',
        'travelers_count' => 'integer',
        'total_price' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the travel package associated with the booking.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(TravelPackage::class, 'travel_package_id');
    }
}

