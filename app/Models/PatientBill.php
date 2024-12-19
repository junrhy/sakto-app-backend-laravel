<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientBill extends Model
{
    protected $fillable = ['patient_id', 'bill_number', 'bill_date', 'bill_amount', 'bill_status', 'bill_details'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
