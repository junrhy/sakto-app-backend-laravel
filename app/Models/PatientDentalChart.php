<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientDentalChart extends Model
{
    protected $fillable = ['patient_id', 'tooth_id', 'status', 'notes'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
