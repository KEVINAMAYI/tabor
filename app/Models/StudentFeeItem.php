<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFeeItem extends Model
{
    protected $fillable = [
        'student_id',
        'enrollment_id',
        'trimester_id',
        'fee_definition_id',
        'course_fee_plan_id',
        'description',
        'amount',
        'amount_paid',
        'balance',
        'charge_date',
        'due_date',
        'status',
    ];

    protected $casts = [
        'charge_date' => 'date',
        'due_date' => 'date',
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

    public function feeDefinition()
    {
        return $this->belongsTo(FeeDefinition::class);
    }

    public function courseFeePlan()
    {
        return $this->belongsTo(CourseFeePlan::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
