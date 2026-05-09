<?php

namespace App\Actions\Enrollment;

use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class MarkGraduatedAction
{
    public function execute(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {

            if ($enrollment->status !== 'pending_graduation') {
                throw new \RuntimeException(
                    'Only pending graduation enrollments can be graduated.'
                );
            }

            $hasBalance = $enrollment->studentFeeItems()
                ->where('balance', '>', 0)
                ->exists();

            if ($hasBalance) {
                throw new \RuntimeException(
                    'Student has unpaid balances.'
                );
            }

            $enrollment->update([
                'status' => 'graduated',
                'graduated_at' => $enrollment->graduated_at ?? now(),
            ]);

            return $enrollment->fresh();
        });
    }
}
