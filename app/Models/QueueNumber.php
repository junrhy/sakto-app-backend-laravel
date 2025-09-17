<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'queue_type_id',
        'queue_number',
        'customer_name',
        'customer_contact',
        'status',
        'priority',
        'called_at',
        'serving_at',
        'completed_at',
        'notes'
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'serving_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function queueType()
    {
        return $this->belongsTo(QueueType::class);
    }

    public function markAsCalled()
    {
        $this->update([
            'status' => 'called',
            'called_at' => now()
        ]);
    }

    public function markAsServing()
    {
        $this->update([
            'status' => 'serving',
            'serving_at' => now()
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    public function markAsCancelled()
    {
        $this->update([
            'status' => 'cancelled'
        ]);
    }

    public function getEstimatedWaitTime()
    {
        // Simple estimation based on average service time
        $waitingAhead = $this->queueType->queueNumbers()
            ->where('status', 'waiting')
            ->where('created_at', '<', $this->created_at)
            ->count();

        return $waitingAhead * 5; // 5 minutes per person estimate
    }
}
