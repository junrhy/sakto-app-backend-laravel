<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueType extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_identifier',
        'name',
        'description',
        'prefix',
        'current_number',
        'is_active',
        'settings'
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean'
    ];

    public function queueNumbers()
    {
        return $this->hasMany(QueueNumber::class);
    }

    public function getNextQueueNumber()
    {
        $this->increment('current_number');
        return $this->prefix . str_pad($this->current_number, 3, '0', STR_PAD_LEFT);
    }

    public function getActiveQueueNumbers()
    {
        return $this->queueNumbers()
            ->whereIn('status', ['waiting', 'called', 'serving'])
            ->orderBy('created_at')
            ->get();
    }

    public function getWaitingCount()
    {
        return $this->queueNumbers()->where('status', 'waiting')->count();
    }
}
