<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'notes',
        'checked_in',
        'checked_in_at',
        'payment_status',
        'amount_paid',
        'payment_date',
        'payment_method',
        'transaction_id',
        'payment_notes'
    ];

    protected $casts = [
        'checked_in' => 'boolean',
        'checked_in_at' => 'datetime',
        'payment_date' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function getFormattedPaymentStatusAttribute()
    {
        return ucfirst($this->payment_status);
    }

    public function getFormattedAmountPaidAttribute()
    {
        if (!$this->amount_paid) {
            return '-';
        }
        
        $currency = $this->event->currency ?? 'PHP';
        return $currency . ' ' . number_format($this->amount_paid, 2);
    }
}
