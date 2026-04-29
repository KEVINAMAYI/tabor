<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentProgression extends Model
{

protected $guarded = ['id'];
    protected $casts = [
        'started_at' => 'date',
        'completed_at' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function trimester()
    {
        return $this->belongsTo(Trimester::class);
    }

    public function feeItems()
    {
        return $this->hasMany(StudentFeeItem::class, 'enrollment_progression_id');
    }
}
