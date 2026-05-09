<?php

namespace App\Actions\Enrollment;

use App\Models\Enrollment;
use App\Services\GraduationProcessingFeeGenerationService;
use Illuminate\Support\Facades\DB;

class MoveToPendingGraduationAction
{
    public function execute(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {

            if (! in_array(
                $enrollment->status,
                ['course_completed', 'pending_graduation'],
                true
            )) {
                throw new \RuntimeException(
                    'Only completed enrollments can move to pending graduation.'
                );
            }

            $enrollment->update([
                'status' => 'pending_graduation',
                'pending_graduation_at' => $enrollment->pending_graduation_at ?? now(),
            ]);

            app(GraduationProcessingFeeGenerationService::class)
                ->generate($enrollment);

            return $enrollment->fresh();
        });
    }
}
