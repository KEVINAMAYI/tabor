<?php

namespace App\Actions\Enrollment;

use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class MarkCourseCompletedAction
{
    public function execute(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {

            if ($enrollment->status === 'graduated') {
                throw new \RuntimeException(
                    'Graduated enrollment cannot be modified.'
                );
            }

            $enrollment->update([
                'status' => 'course_completed',
                'course_completed_at' => $enrollment->course_completed_at ?? now(),
            ]);

            return $enrollment->fresh();
        });
    }
}
