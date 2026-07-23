<?php

namespace App\Actions\Enrollment;

use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class CompleteEnrollmentAction
{
    public function execute(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $enrollment->fresh();
        });
    }
}
