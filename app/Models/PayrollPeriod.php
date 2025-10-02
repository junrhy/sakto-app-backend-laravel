<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    protected $fillable = [
        'client_identifier',
        'period_name',
        'start_date',
        'end_date',
        'status',
        'total_amount',
        'employee_count',
        'created_by',
        'approved_by',
        'processed_at',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'decimal:2',
        'processed_at' => 'datetime'
    ];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'payroll_period_id');
    }
}
