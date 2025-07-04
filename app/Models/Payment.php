<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',     // FK to enrolments table
        'amount',
        'method',
        'reference',
        'is_paid',
        'paid_at',
    ];

    /* -----------------------------------------------------------------
     |  Casts & dates
     |------------------------------------------------------------------
     */
    protected $casts = [
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    /* -----------------------------------------------------------------
     |  Relationships
     |------------------------------------------------------------------
     */

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    // Convenience hops
    public function student()
    {
        return $this->hasOneThrough(
            Student::class,
            Enrollment::class,
            'id',          // PK on enrolments
            'id',          // PK on students
            'enrollment_id',
            'student_id'   // FK on enrolments
        );
    }

    public function course()
    {
        return $this->hasOneThrough(
            Course::class,
            Enrollment::class,
            'id',
            'id',
            'enrollment_id',
            'course_id'
        );
    }

    public function intake()
    {
        return $this->hasOneThrough(
            Intake::class,
            Enrollment::class,
            'id',
            'id',
            'enrollment_id',
            'intake_id'
        );
    }

    /* -----------------------------------------------------------------
     |  Query scopes
     |------------------------------------------------------------------
     */

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function scopeForIntake($query, $intakeId)
    {
        return $query->whereHas('enrollment', fn ($q) =>
        $q->where('intake_id', $intakeId)
        );
    }

    /* -----------------------------------------------------------------
     |  Helpers
     |------------------------------------------------------------------
     */

    public function markAsPaid(string $reference = null): void
    {
        $this->update([
            'is_paid' => true,
            'reference' => $reference ?? $this->reference,
            'paid_at' => now(),
        ]);
    }
}
