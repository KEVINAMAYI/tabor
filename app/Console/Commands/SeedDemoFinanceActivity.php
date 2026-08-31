<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\StudentFeeItem;
use App\Models\User;
use App\Services\CreditService;
use App\Services\PaymentPostingService;
use App\Services\RefundService;
use Illuminate\Console\Command;

/**
 * Generates a handful of clearly-labelled demo transactions against real,
 * existing students/fee items (not fabricated academic data) so the
 * Journal Entries / Trial Balance pages built in Phase 1 have varied,
 * realistic activity to show beyond a single opening-balance entry.
 *
 * Every record this creates is tagged with a "DEMO-" reference / "[DEMO]"
 * note so it's trivially identifiable and reversible. Guarded to run
 * exactly once.
 */
class SeedDemoFinanceActivity extends Command
{
    protected $signature = 'accounting:seed-demo-activity';

    protected $description = 'Post a few demo payments/waiver/refund against real existing students to populate the GL for review';

    public function handle(
        PaymentPostingService $paymentService,
        CreditService $creditService,
        RefundService $refundService
    ): int {
        if (Payment::where('reference', 'like', 'DEMO-%')->exists()) {
            $this->error('Demo finance activity already exists (a Payment with reference "DEMO-%" was found). This command is meant to run once — nothing was created.');
            return self::FAILURE;
        }

        $admin = User::where('email', 'super@demo.com')->first();

        $studentIds = StudentFeeItem::query()
            ->whereNotIn('status', ['waived', 'cancelled', 'paid'])
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->limit(20)
            ->pluck('student_id')
            ->unique()
            ->take(3)
            ->values();

        if ($studentIds->count() < 3) {
            $this->error('Fewer than 3 students with outstanding fee balances were found — nothing to demo against.');
            return self::FAILURE;
        }

        $methods = ['mpesa', 'cash', 'bank'];
        $payments = [];

        foreach ($studentIds as $i => $studentId) {
            $outstanding = (float) StudentFeeItem::where('student_id', $studentId)
                ->whereNotIn('status', ['waived', 'cancelled', 'paid'])
                ->sum('balance');

            $amount = min(round($outstanding * 0.4, -2), 20000);
            $amount = max($amount, 500);

            $payment = $paymentService->post([
                'student_id' => $studentId,
                'payment_date' => now()->toDateString(),
                'amount' => $amount,
                'method' => $methods[$i],
                'reference' => 'DEMO-PAY-' . ($i + 1),
                'notes' => '[DEMO] Phase 2 GL verification data',
                'created_by' => $admin?->id,
            ]);

            $payments[] = $payment;

            $this->info("Posted demo payment #{$payment->id}: KES {$amount} ({$methods[$i]}) for student #{$studentId}.");
        }

        // Waive part of one outstanding fee item.
        $waivedItem = StudentFeeItem::query()
            ->whereNotIn('status', ['waived', 'cancelled', 'paid'])
            ->where('balance', '>', 0)
            ->where('student_id', $studentIds[0])
            ->orderByDesc('balance')
            ->first();

        if ($waivedItem && $admin) {
            $waiveAmount = min(round((float) $waivedItem->balance * 0.2, -1), 5000);

            if ($waiveAmount > 0) {
                $creditService->waiveFee($waivedItem, '[DEMO] Financial hardship waiver', $admin, $waiveAmount);
                $this->info("Waived KES {$waiveAmount} on StudentFeeItem #{$waivedItem->id}.");
            }
        }

        // Refund part of the first demo payment.
        $refundAmount = min(round((float) $payments[0]->amount * 0.25, -1), 2000);

        if ($refundAmount > 0) {
            $refund = $refundService->initiateRefund($payments[0], $refundAmount, '[DEMO] Overpayment refund');
            $refundService->processRefund($refund, $payments[0]->method, 'DEMO-REFUND-1', $admin);
            $this->info("Processed demo refund #{$refund->id}: KES {$refundAmount} against payment #{$payments[0]->id}.");
        }

        $this->newLine();
        $this->info('Demo finance activity created. Review it under Accounting > Journal Entries and Trial Balance.');

        return self::SUCCESS;
    }
}
