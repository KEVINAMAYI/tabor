<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class EnrollmentStatusService
{
    private const TRANSITIONS = [
        'pending'            => ['approved', 'rejected', 'cancelled'],
        'approved'           => ['active', 'cancelled'],
        'active'             => ['deferred', 'course_completed', 'withdrawn', 'cancelled'],
        'deferred'           => ['active', 'withdrawn', 'cancelled'],
        'course_completed'   => ['pending_graduation', 'cancelled'],
        'pending_graduation' => ['graduated', 'cancelled'],
        'graduated'          => [],
        'rejected'           => [],
        'withdrawn'          => [],
        'cancelled'          => [],
    ];

    public function transition(Enrollment $enrollment, string $toStatus, ?User $by = null, string $notes = null): void
    {
        $fromStatus = $enrollment->status;

        $allowed = self::TRANSITIONS[$fromStatus] ?? [];

        if (!in_array($toStatus, $allowed, true)) {
            throw new LogicException(
                "Cannot transition enrollment #{$enrollment->id} from '{$fromStatus}' to '{$toStatus}'."
            );
        }

        DB::transaction(function () use ($enrollment, $toStatus, $notes) {
            $timestamps = $this->timestampsFor($toStatus);
            $enrollment->update(array_merge(['status' => $toStatus], $timestamps));
        });
    }

    public function approve(Enrollment $enrollment, ?User $by = null): void
    {
        $this->transition($enrollment, 'approved', $by);
    }

    public function reject(Enrollment $enrollment, string $reason = null, ?User $by = null): void
    {
        $this->transition($enrollment, 'rejected', $by);
        if ($reason) {
            $enrollment->update(['remarks' => $reason]);
        }
    }

    public function activate(Enrollment $enrollment, ?User $by = null): void
    {
        $this->transition($enrollment, 'active', $by);
    }

    public function markCourseCompleted(Enrollment $enrollment, ?User $by = null): void
    {
        $this->transition($enrollment, 'course_completed', $by);
    }

    public function markPendingGraduation(Enrollment $enrollment, ?User $by = null): void
    {
        $this->transition($enrollment, 'pending_graduation', $by);
    }

    public function graduate(Enrollment $enrollment, ?User $by = null): void
    {
        $this->transition($enrollment, 'graduated', $by);
    }

    public function withdraw(Enrollment $enrollment, string $reason = null, ?User $by = null): void
    {
        $this->transition($enrollment, 'withdrawn', $by);
        if ($reason) {
            $enrollment->update(['remarks' => $reason]);
        }
    }

    public function cancel(Enrollment $enrollment, string $reason = null, ?User $by = null): void
    {
        $this->transition($enrollment, 'cancelled', $by);
        if ($reason) {
            $enrollment->update(['remarks' => $reason]);
        }
    }

    public function canTransitionTo(Enrollment $enrollment, string $toStatus): bool
    {
        return in_array($toStatus, self::TRANSITIONS[$enrollment->status] ?? [], true);
    }

    public function allowedTransitions(Enrollment $enrollment): array
    {
        return self::TRANSITIONS[$enrollment->status] ?? [];
    }

    private function timestampsFor(string $status): array
    {
        return match ($status) {
            'course_completed'   => ['course_completed_at' => now()],
            'pending_graduation' => ['pending_graduation_at' => now()],
            'graduated'          => ['graduated_at' => now()],
            default              => [],
        };
    }
}
