<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\StudentFeeItem;
use App\Services\PaymentPostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildPaymentAllocations extends Command
{
    protected $signature = 'finance:rebuild-payment-allocations
        {--enrollment_id= : Rebuild for one enrollment only}
        {--student_id= : Rebuild for one student only}
        {--dry-run : Preview only}';

    protected $description = 'Reset and rebuild payment allocations using current allocation priority rules';

    public function handle(): int
    {
        $enrollmentId = $this->option('enrollment_id')
            ? (int) $this->option('enrollment_id')
            : null;

        $studentId = $this->option('student_id')
            ? (int) $this->option('student_id')
            : null;

        $feeItemsQuery = StudentFeeItem::query()
            ->when($enrollmentId, fn($q) => $q->where('enrollment_id', $enrollmentId))
            ->when($studentId, fn($q) => $q->where('student_id', $studentId));

        $feeItemIds = (clone $feeItemsQuery)->pluck('id');

        $paymentIdsFromAllocations = PaymentAllocation::query()
            ->whereIn('student_fee_item_id', $feeItemIds)
            ->pluck('payment_id');

        $paymentIdsFromPayments = Payment::query()
            ->when($enrollmentId, fn($q) => $q->where('enrollment_id', $enrollmentId))
            ->when($studentId, fn($q) => $q->where('student_id', $studentId))
            ->pluck('id');

        $paymentIds = $paymentIdsFromPayments
            ->merge($paymentIdsFromAllocations)
            ->unique()
            ->values();

        $this->info('Fee items found: ' . $feeItemIds->count());
        $this->info('Payments affected: ' . $paymentIds->count());

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN: No records changed.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($feeItemIds, $paymentIds) {
            PaymentAllocation::query()
                ->whereIn('student_fee_item_id', $feeItemIds)
                ->delete();

            StudentFeeItem::query()
                ->whereIn('id', $feeItemIds)
                ->update([
                    'amount_paid' => 0,
                    'balance' => DB::raw('amount'),
                    'status' => DB::raw("
                        CASE
                            WHEN amount <= 0 THEN 'paid'
                            ELSE 'pending'
                        END
                    "),
                ]);

            Payment::query()
                ->whereIn('id', $paymentIds)
                ->chunkById(100, function ($payments) {
                    foreach ($payments as $payment) {
                        $allocated = (float) $payment->allocations()->sum('amount_allocated');

                        $payment->update([
                            'unallocated_balance' => max(0, (float) $payment->amount - $allocated),
                            'status' => 'completed',
                        ]);

                        app(PaymentPostingService::class)
                            ->allocateExistingPayment($payment->fresh());
                    }
                });
        });

        $this->info('Payment allocations rebuilt successfully.');

        return self::SUCCESS;
    }
}
