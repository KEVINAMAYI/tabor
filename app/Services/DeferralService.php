<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentDeferral;
use App\Models\EnrollmentProgression;
use App\Models\StudentFeeItem;
use App\Models\Trimester;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class DeferralService
{
    public function __construct(
        private FeeGenerationService $feeService
    ) {}

    /**
     * Create a pending deferral request for an enrollment.
     * The progression being deferred is the current active one (if any).
     */
    public function requestDeferral(
        Enrollment $enrollment,
        Trimester $resumeTrimester,
        string $reason
    ): EnrollmentDeferral {
        if (!in_array($enrollment->status, ['active', 'approved'], true)) {
            throw new LogicException(
                "Only active or approved enrollments can be deferred. Current status: {$enrollment->status}"
            );
        }

        if ($resumeTrimester->start_date->isPast()) {
            throw new LogicException('Resume trimester must be in the future.');
        }

        if ($enrollment->deferrals()->whereIn('status', ['pending', 'approved'])->exists()) {
            throw new LogicException('This enrollment already has an open deferral request.');
        }

        $activeProgression = $enrollment->progressions()
            ->where('status', 'active')
            ->first();

        return EnrollmentDeferral::create([
            'enrollment_id'              => $enrollment->id,
            'enrollment_progression_id'  => $activeProgression?->id,
            'resume_trimester_id'        => $resumeTrimester->id,
            'reason'                     => $reason,
            'status'                     => 'pending',
            'requested_at'               => now(),
        ]);
    }

    /**
     * Approve a deferral request.
     * - Marks the active progression as deferred
     * - Reverses any charges already generated for the deferred trimester
     * - Sets the enrollment status to 'deferred'
     */
    public function approveDeferral(EnrollmentDeferral $deferral, User $admin): void
    {
        if (!$deferral->isPending()) {
            throw new LogicException("Deferral #{$deferral->id} is not in pending status.");
        }

        DB::transaction(function () use ($deferral, $admin) {
            $deferral->update([
                'status'      => 'approved',
                'approved_at' => now(),
                'approved_by' => $admin->id,
            ]);

            // Mark the progression as deferred
            if ($deferral->enrollment_progression_id) {
                EnrollmentProgression::whereKey($deferral->enrollment_progression_id)
                    ->update([
                        'status' => 'deferred',
                        'notes'  => "Deferred. Reason: {$deferral->reason}. Resumes in trimester #{$deferral->resume_trimester_id}.",
                    ]);

                // Cancel any charges generated for this progression that are still pending (not yet paid)
                StudentFeeItem::query()
                    ->where('enrollment_progression_id', $deferral->enrollment_progression_id)
                    ->whereIn('status', ['pending'])
                    ->where('amount_paid', 0)
                    ->update(['status' => 'cancelled', 'balance' => 0]);
            }

            // Set enrollment status to deferred
            $deferral->enrollment->update(['status' => 'deferred']);
        });
    }

    /**
     * Reject a deferral request.
     */
    public function rejectDeferral(EnrollmentDeferral $deferral, User $admin, string $reason = null): void
    {
        if (!$deferral->isPending()) {
            throw new LogicException("Deferral #{$deferral->id} is not in pending status.");
        }

        $deferral->update([
            'status'      => 'rejected',
            'rejected_at' => now(),
            'approved_by' => $admin->id, // reusing field to track who acted
        ]);
    }

    /**
     * Cancel a pending or approved deferral (before resumption).
     * Restores the enrollment to active and re-activates the progression.
     */
    public function cancelDeferral(EnrollmentDeferral $deferral, User $admin): void
    {
        if (!in_array($deferral->status, ['pending', 'approved'], true)) {
            throw new LogicException("Cannot cancel a deferral that is '{$deferral->status}'.");
        }

        DB::transaction(function () use ($deferral) {
            if ($deferral->enrollment_progression_id) {
                EnrollmentProgression::whereKey($deferral->enrollment_progression_id)
                    ->where('status', 'deferred')
                    ->update(['status' => 'active', 'notes' => 'Deferral cancelled — reinstated to active.']);

                // Restore cancelled charges
                StudentFeeItem::query()
                    ->where('enrollment_progression_id', $deferral->enrollment_progression_id)
                    ->where('status', 'cancelled')
                    ->where('amount_paid', 0)
                    ->update(['status' => 'pending', 'balance' => DB::raw('amount')]);
            }

            $deferral->update(['status' => 'cancelled']);
            $deferral->enrollment->update(['status' => 'active']);
        });
    }

    /**
     * Resume an enrollment from deferral when the resume trimester arrives.
     * Called automatically by ProcessDeferrals command.
     */
    public function resumeFromDeferral(EnrollmentDeferral $deferral): void
    {
        if (!$deferral->isApproved()) {
            throw new LogicException("Deferral #{$deferral->id} must be approved before resuming.");
        }

        if ($deferral->resumed_at) {
            return; // already resumed, idempotent
        }

        $enrollment = $deferral->enrollment;
        $resumeTrimester = $deferral->resumeTrimester;

        if (!$resumeTrimester) {
            throw new LogicException("Deferral #{$deferral->id} has no resume trimester assigned.");
        }

        DB::transaction(function () use ($deferral, $enrollment, $resumeTrimester) {
            // Determine what the next sequence number should be
            $lastSequence = EnrollmentProgression::query()
                ->where('enrollment_id', $enrollment->id)
                ->max('trimester_sequence') ?? 0;

            $nextSequence = (int) $lastSequence + 1;

            // Check we haven't exceeded the course duration
            $enrollment->loadMissing('course');
            if ($nextSequence > (int) $enrollment->course->number_of_trimesters) {
                // All trimesters done — mark course complete instead
                $enrollment->update(['status' => 'course_completed', 'course_completed_at' => now()]);
                $deferral->update(['resumed_at' => now()]);
                return;
            }

            $progression = EnrollmentProgression::firstOrCreate(
                [
                    'enrollment_id'      => $enrollment->id,
                    'trimester_sequence' => $nextSequence,
                ],
                [
                    'student_id'   => $enrollment->student_id,
                    'trimester_id' => $resumeTrimester->id,
                    'status'       => 'active',
                    'started_at'   => $resumeTrimester->start_date,
                    'notes'        => "Resumed from deferral #{$deferral->id}.",
                ]
            );

            // Generate charges for the resumed trimester
            $this->feeService->generateChargesForProgression($progression);

            $enrollment->update(['status' => 'active']);
            $deferral->update(['resumed_at' => now()]);
        });
    }
}
