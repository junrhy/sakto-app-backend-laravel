<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'client_identifier',
        'patient_id',
        'is_priority_patient',
        'priority_level',
        'vip_tier',
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
        'fee' => 'decimal:2',
        'is_priority_patient' => 'boolean',
        'priority_level' => 'integer'
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

    /**
     * Scope to filter by priority patients.
     */
    public function scopePriorityPatients($query)
    {
        return $query->where('is_priority_patient', true);
    }

    /**
     * Scope to order by priority (VIP first, then by date/time).
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority_level', 'desc')
                    ->orderBy('appointment_date', 'asc')
                    ->orderBy('appointment_time', 'asc');
    }

    /**
     * Set VIP priority based on patient data.
     */
    public function setPriorityFromPatient(\App\Models\Patient $patient): void
    {
        $this->is_priority_patient = $patient->isVip() && $patient->hasPriorityScheduling();
        
        if ($this->is_priority_patient) {
            // Set priority level based on VIP tier
            $this->priority_level = match($patient->vip_tier) {
                'diamond' => 3,
                'platinum' => 2,
                'gold' => 1,
                default => 0
            };
            $this->vip_tier = $patient->vip_tier;
        } else {
            $this->priority_level = 0;
            $this->vip_tier = null;
        }
    }

    /**
     * Check if this appointment is for a VIP patient.
     */
    public function isVipAppointment(): bool
    {
        return $this->is_priority_patient;
    }

    /**
     * Get priority level display name.
     */
    public function getPriorityDisplayAttribute(): string
    {
        if (!$this->is_priority_patient) {
            return 'Standard';
        }

        return match($this->priority_level) {
            3 => 'Diamond VIP',
            2 => 'Platinum VIP', 
            1 => 'Gold VIP',
            default => 'VIP'
        };
    }

    /**
     * Get VIP tier configuration for display.
     */
    public function getVipTierConfigAttribute(): ?array
    {
        if (!$this->is_priority_patient || !$this->vip_tier) {
            return null;
        }

        $configs = [
            'gold' => [
                'name' => 'Gold VIP',
                'icon' => '🥇',
                'color' => 'yellow',
                'class' => 'bg-yellow-500 text-white'
            ],
            'platinum' => [
                'name' => 'Platinum VIP',
                'icon' => '💎',
                'color' => 'blue',
                'class' => 'bg-blue-500 text-white'
            ],
            'diamond' => [
                'name' => 'Diamond VIP',
                'icon' => '👑',
                'color' => 'purple',
                'class' => 'bg-purple-500 text-white'
            ]
        ];

        return $configs[$this->vip_tier] ?? null;
    }
}
