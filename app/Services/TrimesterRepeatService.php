<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\Trimester;
use Illuminate\Support\Facades\DB;
use LogicException;

class TrimesterRepeatService
{
    public function __construct(
        private FeeGenerationService $feeService
    ) {}

    /**
     * Mark a progression as repeated and create a new one for the next available trimester.
     *
     * Repeating a trimester means:
     * - The student did not pass (academic failure or insufficient attendance)
     * - The same trimester_sequence is re-done in the next calendar trimester
     * - Student-once fees are NOT re-charged (already paid on first enrollment)
     * - All trimester fees for the repeated progression are re-generated
     */
    public function repeatTrimester(
        EnrollmentProgression $progression,
        Trimester $repeatInTrimester,
        string $reason
    ): EnrollmentProgression {
        if (!in_array($progression->status, ['active', 'completed'], true)) {
            throw new LogicException(
                "Only active or completed progressions can be marked for repeat. Current status: {$progression->status}"
            );
        }

        $enrollment = $progression->enrollment;

        // Ensure the target trimester is not already used by this enrollment
        $conflict = EnrollmentProgression::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('trimester_id', $repeatInTrimester->id)
            ->exists();

        if ($conflict) {
            throw new LogicException(
                "Enrollment #{$enrollment->id} already has a progression in trimester #{$repeatInTrimester->id}."
            );
        }

        return DB::transaction(function () use ($progression, $enrollment, $repeatInTrimester, $reason) {
            // Mark the old progression as repeated
            $progression->update([
                'status' => 'repeated',
                'notes'  => "Marked as repeated. Reason: {$reason}.",
            ]);

            // Create the new progression with the SAME sequence number in the new trimester
            $newProgression = EnrollmentProgression::create([
                'student_id'         => $enrollment->student_id,
                'enrollment_id'      => $enrollment->id,
                'trimester_id'       => $repeatInTrimester->id,
                'trimester_sequence' => $progression->trimester_sequence,
                'status'             => 'active',
                'started_at'         => $repeatInTrimester->start_date,
                'notes'              => "Repeat of trimester sequence {$progression->trimester_sequence}. Reason: {$reason}.",
            ]);

            // Generate trimester fees for the repeat (student-once fees are skipped inside generateChargesForProgression)
            $this->feeService->generateChargesForProgression($newProgression);

            return $newProgression;
        });
    }

    /**
     * Find the next available trimester to repeat in (after the current one).
     */
    public function nextAvailableTrimesterForRepeat(EnrollmentProgression $progression): ?Trimester
    {
        $usedTrimesterIds = EnrollmentProgression::query()
            ->where('enrollment_id', $progression->enrollment_id)
            ->pluck('trimester_id');

        return Trimester::query()
            ->whereNotIn('id', $usedTrimesterIds)
            ->whereDate('start_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->first();
    }
}
