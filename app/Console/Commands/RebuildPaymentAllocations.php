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
        {--dry-run : Preview only}
        {--student_id= : Rebuild for one student only}';

    protected $description = 'Reset and rebuild payment allocations using current allocation priority rules';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN: No records will be changed.');
        }

        $studentId = $this->option('student_id');

        $paymentsQuery = Payment::query()
            ->when($studentId, fn($q) => $q->where('student_id', $studentId))
            ->orderBy('payment_date')
            ->orderBy('id');

        $paymentIds = (clone $paymentsQuery)->pluck('id');

        $feeItemsQuery = StudentFeeItem::query()
            ->when($studentId, fn($q) => $q->where('student_id', $studentId));

        $this->info('Payments found: ' . $paymentIds->count());
        $this->info('Fee items found: ' . (clone $feeItemsQuery)->count());

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($paymentIds, $feeItemsQuery, $paymentsQuery) {
            PaymentAllocation::query()
                ->whereIn('payment_id', $paymentIds)
                ->delete();

            $feeItemsQuery->update([
                'amount_paid' => 0,
                'balance' => DB::raw('amount'),
                'status' => DB::raw("
                    CASE
                        WHEN amount < 0 THEN 'paid'
                        WHEN amount = 0 THEN 'paid'
                        ELSE 'pending'
                    END
                "),
            ]);

            Payment::query()
                ->whereIn('id', $paymentIds)
                ->update([
                    'unallocated_balance' => DB::raw('amount'),
                    'status' => 'completed',
                ]);

            $paymentsQuery->chunkById(100, function ($payments) {
                foreach ($payments as $payment) {
                    app(PaymentPostingService::class)
                        ->allocateExistingPayment($payment->fresh());
                }
            });
        });

        $this->info('Payment allocations rebuilt successfully.');

        return self::SUCCESS;
    }
}
