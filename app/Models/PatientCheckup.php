<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientCheckup extends Model
{
    protected $fillable = ['patient_id', 'checkup_date', 'diagnosis', 'treatment', 'notes'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
