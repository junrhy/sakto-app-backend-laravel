<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'client_identifier',
        'patient_id',
        'patient_name',
        'patient_phone',
        'patient_email',
        'appointment_date',
        'appointment_time',
        'appointment_type',
        'notes',
        'status',
        'doctor_name',
        'fee',
        'payment_status',
        'cancellation_reason',
        'cancelled_at'
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'fee' => 'decimal:2'
    ];

    /**
     * Get the patient that owns the appointment.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Scope to filter by client identifier.
     */
    public function scopeByClient($query, string $clientIdentifier)
    {
        return $query->where('client_identifier', $clientIdentifier);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by appointment date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('appointment_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get upcoming appointments.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now())
                    ->whereIn('status', ['scheduled', 'confirmed']);
    }

    /**
     * Scope to get today's appointments.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', today());
    }
}
