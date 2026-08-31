<?php

namespace App\Console\Commands;

use App\Models\JournalEntry;
use App\Models\StudentFeeItem;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Console\Command;

/**
 * Go-live cutover command for Phase 1 of the General Ledger build.
 *
 * Rather than replaying every historical Payment/StudentFeeItem into the GL
 * (which would risk duplicating StudentLedgerService's fragile cross-
 * progression business logic — see the Phase 1 plan doc), this posts ONE
 * opening-balance journal entry for the sum of all outstanding
 * StudentFeeItem balances as of the cutover date:
 *   DR Student Debtors / CR Opening Balance Equity
 *
 * Real GL posting (via StudentFeeItemObserver / PaymentPostingService /
 * RefundService / CreditService) begins from the cutover date forward.
 *
 * Must be run exactly once — guarded against accidental re-runs.
 */
class PostOpeningBalances extends Command
{
    protected $signature = 'accounting:post-opening-balance
        {--dry-run : Compute and print the total without posting}
        {--as-of= : Cutover date (Y-m-d). Defaults to config(accounting.opening_balance.cutover_date)}';

    protected $description = 'Post the one-time opening balance journal entry for existing StudentFeeItem balances as of the GL go-live cutover date';

    public function handle(JournalPostingService $journalPostingService): int
    {
        $asOf = $this->option('as-of') ?: config('accounting.opening_balance.cutover_date');
        $dryRun = (bool) $this->option('dry-run');

        $description = "Opening balance as of {$asOf}";

        if (JournalEntry::where('description', $description)->whereNull('source_type')->exists()) {
            $this->error("An opening balance entry for {$asOf} already exists. This command is meant to run exactly once — nothing was posted.");
            return self::FAILURE;
        }

        $total = round(
            (float) StudentFeeItem::query()
                ->whereNotIn('status', ['waived', 'cancelled'])
                ->where('charge_date', '<=', $asOf)
                ->sum('balance'),
            2
        );

        $this->table(
            ['Metric', 'Value'],
            [
                ['Cutover date', $asOf],
                ['Outstanding StudentFeeItem balance (sum)', number_format($total, 2)],
                ['Student Debtors account', config('accounting.student_debtors_account_code')],
                ['Opening Balance Equity account', config('accounting.opening_balance.equity_account_code')],
            ]
        );

        if ($total <= 0) {
            $this->warn('Nothing to post — total outstanding balance is zero or negative.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('DRY-RUN — nothing was posted. Re-run without --dry-run to post this entry.');
            return self::SUCCESS;
        }

        if (!$this->confirm("Post an opening balance journal entry for {$total} as of {$asOf}?", false)) {
            $this->line('Aborted.');
            return self::SUCCESS;
        }

        $entry = $journalPostingService->post([
            'entry_date' => $asOf,
            'description' => $description,
            'created_by' => null,
            'lines' => [
                ['account_code' => config('accounting.student_debtors_account_code'), 'debit' => $total],
                ['account_code' => config('accounting.opening_balance.equity_account_code'), 'credit' => $total],
            ],
        ]);

        $this->info("Posted opening balance journal entry #{$entry->id}.");

        return self::SUCCESS;
    }
}
