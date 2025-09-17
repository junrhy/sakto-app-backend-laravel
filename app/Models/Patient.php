<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PatientBill;
use App\Models\PatientDentalChart;
use App\Models\PatientPayment;
use App\Models\PatientCheckup;
use App\Models\Appointment;

class Patient extends Model
{
    protected $fillable = ['arn', 'name', 'birthdate', 'phone', 'email', 'client_identifier'];

    public function bills()
    {
        return $this->hasMany(PatientBill::class);
    }

    public function payments()
    {
        return $this->hasMany(PatientPayment::class);
    }

    public function dentalChart()
    {
        return $this->hasMany(PatientDentalChart::class);
    }

    public function checkups()
    {
        return $this->hasMany(PatientCheckup::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
