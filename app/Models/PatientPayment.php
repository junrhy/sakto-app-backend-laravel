<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientPayment extends Model
{
    protected $fillable = ['patient_id', 'payment_date', 'payment_amount', 'payment_method', 'payment_notes'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
