<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryHistory extends Model
{
    protected $fillable = [
        'client_identifier',
        'employee_id',
        'previous_salary',
        'new_salary',
        'salary_change',
        'percentage_change',
        'change_reason',
        'approved_by',
        'effective_date',
        'notes'
    ];

    protected $casts = [
        'previous_salary' => 'decimal:2',
        'new_salary' => 'decimal:2',
        'salary_change' => 'decimal:2',
        'percentage_change' => 'decimal:2',
        'effective_date' => 'date'
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'employee_id', 'id');
    }
}
