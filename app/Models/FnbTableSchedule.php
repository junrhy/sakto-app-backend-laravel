<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnbTableSchedule extends Model
{
    protected $fillable = [
        'client_identifier',
        'table_id',
        'schedule_date',
        'timeslots',
        'status',
        'joined_with',
        'notes',
    ];

    protected $casts = [
        'timeslots' => 'array',
        'schedule_date' => 'date',
    ];

    /**
     * Get the table that owns this schedule
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(FnbTable::class, 'table_id');
    }
}
