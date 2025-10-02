<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeTracking extends Model
{
    protected $fillable = [
        'client_identifier',
        'employee_id',
        'work_date',
        'clock_in',
        'clock_out',
        'hours_worked',
        'overtime_hours',
        'regular_hours',
        'status',
        'notes',
        'location'
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'hours_worked' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'regular_hours' => 'decimal:2'
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'employee_id', 'id');
    }
}
