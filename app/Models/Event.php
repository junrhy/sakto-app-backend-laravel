<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'location', 
        'max_participants',
        'registration_deadline',
        'is_public',
        'is_paid_event',
        'event_price',
        'currency',
        'payment_instructions',
        'category',
        'image',
        'status',
        'client_identifier'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'is_public' => 'boolean',
        'is_paid_event' => 'boolean',
        'max_participants' => 'integer',
        'event_price' => 'decimal:2',
    ];

    protected $appends = [
        'current_participants'
    ];

    public function participants()
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function getFormattedPriceAttribute()
    {
        if (!$this->is_paid_event || !$this->event_price) {
            return 'Free';
        }
        
        return $this->currency . ' ' . number_format($this->event_price, 2);
    }

    public function getPaidParticipantsCountAttribute()
    {
        return $this->participants()->where('payment_status', 'paid')->count();
    }

    public function getPendingPaymentCountAttribute()
    {
        return $this->participants()->where('payment_status', 'pending')->count();
    }

    public function getCurrentParticipantsAttribute()
    {
        return $this->participants()->count();
    }
}
