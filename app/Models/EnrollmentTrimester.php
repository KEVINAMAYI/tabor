<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentTrimester extends Model
{
    protected $guarded = ['id'];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getBalanceAttribute()
    {
        return $this->fee_amount - $this->amount_paid;
    }
}
