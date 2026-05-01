<?php

namespace App\Actions\Enrollment;

use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteEnrollmentAction
{
    public function execute(Enrollment $enrollment): void
    {
        DB::beginTransaction();

        try {
            $enrollment->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to delete enrollment', [
                'enrollment_id' => $enrollment->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}