<?php

namespace App\Observers;

use App\Models\StudentFeeItem;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Support\Facades\Log;
use LogicException;

/**
 * Books the charge side of the General Ledger: every new StudentFeeItem is a
 * receivable (DR Student Debtors) against revenue (CR the fee category's
 * mapped account). This single hook covers every fee-generation service
 * (FeeGenerationService, CompletionFeeGenerationService,
 * GraduationProcessingFeeGenerationService, StudentOnceFeeCourseSyncService)
 * without editing any of them individually.
 *
 * Known Phase 1 limitation: amendments to an existing item's amount (via
 * FeeItemAdjustmentService) do not fire `created` and so do not auto-post a
 * correcting entry — those need a manual journal entry until a follow-up
 * wires `updated`-event deltas specifically.
 */
class StudentFeeItemObserver
{
    public function __construct(private JournalPostingService $journalPostingService) {}

    public function created(StudentFeeItem $item): void
    {
        $amount = (float) $item->amount;

        if ($amount <= 0 || $item->status === 'cancelled') {
            return;
        }

        $revenueAccountCode = optional(
            $item->feeDefinition?->category?->revenueAccount
        )->account_code ?? config('accounting.default_revenue_account_code');

        try {
            $this->journalPostingService->post([
                'entry_date' => $item->charge_date ?? now()->toDateString(),
                'description' => "Charge — {$item->description}",
                'source_type' => StudentFeeItem::class,
                'source_id' => $item->id,
                'lines' => [
                    ['account_code' => config('accounting.student_debtors_account_code'), 'debit' => $amount],
                    ['account_code' => $revenueAccountCode, 'credit' => $amount],
                ],
            ]);
        } catch (LogicException $e) {
            // Never block charge creation on a GL posting failure (e.g. no open
            // accounting period yet) — log loudly so Finance can post the
            // correcting/missing entry manually via the Journal Entries UI.
            Log::warning("GL posting failed for StudentFeeItem #{$item->id}: {$e->getMessage()}");
        }
    }
}
