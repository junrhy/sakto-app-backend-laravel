<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payroll extends Model
{
    protected $fillable = ['employee_id', 'name', 'email', 'position', 'salary', 'status', 'client_identifier'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payroll) {
            if (empty($payroll->employee_id)) {
                $payroll->employee_id = static::generateEmployeeId($payroll->client_identifier);
            }
        });
    }

    protected static function generateEmployeeId($clientIdentifier)
    {
        // Get the highest employee_id for this client
        $lastEmployee = static::where('client_identifier', $clientIdentifier)
            ->orderBy('employee_id', 'desc')
            ->first();

        if ($lastEmployee && is_numeric($lastEmployee->employee_id)) {
            // If the last employee_id is numeric, increment it
            $nextId = (int)$lastEmployee->employee_id + 1;
        } else {
            // Start with EMP001 for this client
            $nextId = 1;
        }

        // Format as EMP001, EMP002, etc.
        return 'EMP' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    }
}
