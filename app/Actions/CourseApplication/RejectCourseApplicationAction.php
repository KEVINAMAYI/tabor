<?php

namespace App\Actions\CourseApplication;

use App\Models\CourseApplication;

class RejectCourseApplicationAction
{
    public function execute(CourseApplication $application, ?string $reason = null): void
    {
        if ($application->status !== 'pending') {
            throw new \RuntimeException('Only pending applications can be rejected.');
        }

        $application->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }
}
