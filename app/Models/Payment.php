<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /* -----------------------------------------------------------------
     |  Casts & dates
     |------------------------------------------------------------------
     */
    protected $casts = [
        'payment_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'unallocated_balance' => 'decimal:2',
        'is_sponsored' => 'boolean',
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
        return $this->belongsTo(Student::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function refunds()
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function totalRefunded(): float
    {
        return (float) $this->refunds()->where('status', 'processed')->sum('amount');
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
        return $query->whereHas(
            'enrollment',
            fn($q) =>
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
            'status' => 'completed',
            'reference' => $reference ?? $this->reference,
            'paid_at' => now(),
        ]);
    }
}
