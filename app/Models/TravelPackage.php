<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'title',
        'slug',
        'tagline',
        'description',
        'duration_days',
        'duration_label',
        'price',
        'inclusions',
        'package_type',
        'status',
        'is_featured',
        'media',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'price' => 'decimal:2',
        'inclusions' => 'array',
        'is_featured' => 'boolean',
        'media' => 'array',
    ];

    /**
     * Get the bookings associated with the travel package.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(TravelBooking::class);
    }
}

