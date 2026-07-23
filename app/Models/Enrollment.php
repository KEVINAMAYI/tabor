<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    /**
     * Mass‑assignable columns.
     */
    protected $guarded = ['id'];

    protected $casts = [
        'include_registration_fee' => 'boolean',
        'include_student_id_fee' => 'boolean',
        'include_stationery_fee' => 'boolean',
        'include_caution_fee' => 'boolean',
        'admission_date' => 'date',
        'completed_at' => 'datetime',
        'course_completed_at' => 'datetime',
        'pending_graduation_at' => 'datetime',
        'graduated_at' => 'datetime',
    ];

    /* -----------------------------------------------------------------
     |  Direct relationships
     |------------------------------------------------------------------
     */

    public function trimesters()
    {
        return $this->hasMany(EnrollmentTrimester::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function intake()  // to delete after replacing code with the below two trimester relationships
    {
        return $this->belongsTo(Intake::class);
    }

    public function intakeTrimester()
    {
        return $this->belongsTo(Trimester::class, 'intake_trimester_id');
    }

    public function assignedStartTrimester()
    {
        return $this->belongsTo(Trimester::class, 'assigned_start_trimester_id');
    }

    public function prerequisiteEnrollment()
    {
        return $this->belongsTo(Enrollment::class, 'prerequisite_enrollment_id');
    }

    public function feeItems()
    {
        return $this->hasMany(StudentFeeItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /* -----------------------------------------------------------------
     |  Attendance & submissions
     |------------------------------------------------------------------
     */

    // Sessions the student attended (attendance pivot)
    public function sessions()
    {
        return $this->belongsToMany(
            ModuleSession::class,
            'attendance',             // pivot table
            'enrollment_id',
            'module_session_id'
        )
            ->withPivot('status')         // present / absent / late
            ->withTimestamps();
    }

    // Assessment submissions for this enrolment
    public function submissions()
    {
        return $this->hasMany(AssessmentSubmission::class);
    }

    /* -----------------------------------------------------------------
     |  Convenience helpers
     |------------------------------------------------------------------
     */

    /**
     * Is the enrolment fully paid?
     */
    public function isSettled(): bool
    {
        // assuming `price` lives on the course
        return $this->payments()->sum('amount') >= $this->course->price;
    }

    public function progressions()
    {
        return $this->hasMany(EnrollmentProgression::class);
    }

    public function currentProgression()
    {
        return $this->hasOne(EnrollmentProgression::class)
            ->where('status', 'active');
    }

    public function studentFeeItems()
    {
        return $this->hasMany(StudentFeeItem::class);
    }

    public function deferrals()
    {
        return $this->hasMany(EnrollmentDeferral::class);
    }

    public function activeDeferral()
    {
        return $this->hasOne(EnrollmentDeferral::class)
            ->whereIn('status', ['pending', 'approved'])
            ->latest();
    }

    public function isDeferred(): bool
    {
        return $this->status === 'deferred';
    }

    /* -----------------------------------------------------------------
     |  Academic calendar resolution (LMS)
     |------------------------------------------------------------------
     |  Enrollments were created by three generations of code: the current
     |  one (EnrollmentProgression / assigned_start_trimester_id), a legacy
     |  one (intake_trimester_id only), and the original one (intake_id
     |  only, no trimester fields at all). These helpers resolve "which
     |  Trimester(s) is this enrollment in" regardless of which generation
     |  created the row, so LMS content (intake_modules.trimester_id) can
     |  be matched consistently.
     */

    public function resolvedTrimesterIds(): \Illuminate\Support\Collection
    {
        $ids = $this->progressions()->pluck('trimester_id')->filter()->unique()->values();

        if ($ids->isEmpty() && $this->intake_trimester_id) {
            $ids = collect([$this->intake_trimester_id]);
        }

        if ($ids->isEmpty() && $this->assigned_start_trimester_id) {
            $ids = collect([$this->assigned_start_trimester_id]);
        }

        if ($ids->isEmpty() && $this->intake_id && $this->intake) {
            $ids = Trimester::where('start_date', '<=', $this->intake->starts_at)
                ->where('end_date', '>=', $this->intake->starts_at)
                ->pluck('id');
        }

        return $ids;
    }

    public function currentTrimesterLabel(): ?string
    {
        $trimester = $this->currentProgression?->trimester
            ?? $this->intakeTrimester
            ?? $this->assignedStartTrimester;

        if ($trimester) {
            return trim($trimester->name . ' ' . ($trimester->academicYear->name ?? ''));
        }

        return $this->intake?->name;
    }

    public function scopeForTrimester($query, int $trimesterId)
    {
        return $query->forAnyTrimester([$trimesterId]);
    }

    public function scopeForAnyTrimester($query, $trimesterIds)
    {
        $trimesterIds = collect($trimesterIds)->filter()->values();

        if ($trimesterIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        $legacyIntakeIds = Trimester::whereIn('id', $trimesterIds)->get()
            ->flatMap(fn($t) => Intake::whereDate('starts_at', '>=', $t->start_date)
                ->whereDate('starts_at', '<=', $t->end_date)
                ->pluck('id'))
            ->unique()
            ->values();

        return $query->where(function ($q) use ($trimesterIds, $legacyIntakeIds) {
            $q->whereHas('progressions', fn($p) => $p->whereIn('trimester_id', $trimesterIds))
                ->orWhereIn('intake_trimester_id', $trimesterIds)
                ->orWhereIn('assigned_start_trimester_id', $trimesterIds);

            if ($legacyIntakeIds->isNotEmpty()) {
                $q->orWhereIn('intake_id', $legacyIntakeIds);
            }
        });
    }
}
