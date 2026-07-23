<?php

namespace App\Actions\Enrollment;

use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class DeleteEnrollmentAction
{
    public function execute(Enrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment) {

            if ($enrollment->payments()->exists()) {
                throw new \RuntimeException(
                    'Cannot delete enrollment with payments.'
                );
            }

            $enrollment->studentFeeItems()->delete();

            $enrollment->progressions()->delete();

            $enrollment->delete();
        });
    }
}
