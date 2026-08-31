<?php

namespace App\Services;

use App\Models\PettyCashCustodian;
use App\Models\PettyCashExpense;
use App\Models\PettyCashFloatTransaction;
use App\Models\User;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;

/**
 * Phase 2 of the Finance module: Petty Cash & Imprest.
 *
 * Custodians (Registrar, Procurement Officer, etc.) hold a cash float and
 * submit vote-head-coded expenses for Finance approval. Every issued float
 * and every approved expense posts a GL entry via JournalPostingService,
 * exactly as Phase 1's plan intended — this module never writes GL rows
 * directly.
 *
 * GL accounting:
 * - Issuing/topping up/reimbursing a float: DR Petty Cash (1020) / CR the
 *   funding account (default Cash at Bank, 1010).
 * - Approving an expense: DR the vote head's mapped expense account / CR
 *   Petty Cash (1020) — the vote head/sub-vote-head name is recorded on the
 *   journal line's existing free-text `cost_centre` column.
 *
 * Expenses require approval before they post (mirrors the Journal Entry
 * draft/approve maker-checker flow from Phase 1) — submitting an expense
 * never touches the GL; only approveExpense() does.
 */
class PettyCashService
{
    public function __construct(
        private JournalPostingService $journalPostingService
    ) {}

    /**
     * Issue a float to a custodian (initial issuance, top-up, or
     * reimbursement of spent float back to the opening level — all three
     * are the same GL movement, only `type` differs for reporting).
     */
    public function issueFloat(array $data): PettyCashFloatTransaction
    {
        $amount = (float) ($data['amount'] ?? 0);

        if ($amount <= 0) {
            throw new LogicException('Float amount must be greater than zero.');
        }

        $custodian = PettyCashCustodian::findOrFail($data['custodian_id']);

        $transaction = DB::transaction(function () use ($data, $custodian, $amount) {
            return PettyCashFloatTransaction::create([
                'custodian_id' => $custodian->id,
                'type' => $data['type'] ?? 'topup',
                'amount' => $amount,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'issued_by' => $data['issued_by'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        $this->postFloatToLedger($transaction, $custodian);

        return $transaction->fresh();
    }

    /**
     * Submit an expense for a custodian. Left pending — no GL impact until
     * Finance approves it via approveExpense().
     */
    public function submitExpense(array $data): PettyCashExpense
    {
        $amount = (float) ($data['amount'] ?? 0);

        if ($amount <= 0) {
            throw new LogicException('Expense amount must be greater than zero.');
        }

        return PettyCashExpense::create([
            'custodian_id' => $data['custodian_id'],
            'vote_head_id' => $data['vote_head_id'],
            'sub_vote_head_id' => $data['sub_vote_head_id'] ?? null,
            'supplier_name' => $data['supplier_name'] ?? null,
            'description' => $data['description'],
            'amount' => $amount,
            'expense_date' => $data['expense_date'] ?? now()->toDateString(),
            'receipt_path' => $data['receipt_path'] ?? null,
            'receipt_original_name' => $data['receipt_original_name'] ?? null,
            'status' => 'pending',
            'submitted_by' => $data['submitted_by'] ?? null,
        ]);
    }

    /**
     * Approve a pending expense: checks it against the custodian's current
     * available float, then posts the GL entry (DR vote-head expense
     * account / CR Petty Cash).
     */
    public function approveExpense(PettyCashExpense $expense, int $approverId): PettyCashExpense
    {
        if ($expense->status !== 'pending') {
            throw new LogicException("Petty cash expense #{$expense->id} is not pending (status: {$expense->status}).");
        }

        $expense->loadMissing('custodian', 'voteHead', 'subVoteHead');

        if ($expense->amount > $expense->custodian->available_balance) {
            throw new LogicException(
                "Expense amount ({$expense->amount}) exceeds custodian's available float balance ({$expense->custodian->available_balance})."
            );
        }

        $expense->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);

        $this->postExpenseToLedger($expense, $approverId);

        return $expense->fresh();
    }

    /**
     * Reject a pending expense. No GL impact.
     */
    public function rejectExpense(PettyCashExpense $expense, string $reason, int $rejectedBy): PettyCashExpense
    {
        if ($expense->status !== 'pending') {
            throw new LogicException("Petty cash expense #{$expense->id} is not pending (status: {$expense->status}).");
        }

        $expense->update([
            'status' => 'rejected',
            'rejected_reason' => $reason,
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
        ]);

        return $expense->fresh();
    }

    protected function postFloatToLedger(PettyCashFloatTransaction $transaction, PettyCashCustodian $custodian): void
    {
        try {
            $entry = $this->journalPostingService->post([
                'entry_date' => $transaction->transaction_date,
                'description' => ucfirst($transaction->type) . " float — {$this->custodianLabel($custodian)}",
                'source_type' => PettyCashFloatTransaction::class,
                'source_id' => $transaction->id,
                'created_by' => $transaction->issued_by,
                'lines' => [
                    ['account_code' => config('accounting.petty_cash_account_code'), 'debit' => $transaction->amount],
                    ['account_code' => config('accounting.default_cash_account_code'), 'credit' => $transaction->amount],
                ],
            ]);

            $transaction->update(['journal_entry_id' => $entry->id]);
        } catch (LogicException $e) {
            Log::warning("GL posting failed for PettyCashFloatTransaction #{$transaction->id}: {$e->getMessage()}");
        }
    }

    protected function postExpenseToLedger(PettyCashExpense $expense, int $approverId): void
    {
        try {
            $costCentre = $expense->subVoteHead?->name ?? $expense->voteHead->name;

            $entry = $this->journalPostingService->post([
                'entry_date' => $expense->expense_date,
                'description' => "Petty cash expense — {$expense->description}",
                'source_type' => PettyCashExpense::class,
                'source_id' => $expense->id,
                'created_by' => $approverId,
                'lines' => [
                    [
                        'account_code' => $expense->voteHead->expenseAccount->account_code,
                        'debit' => $expense->amount,
                        'cost_centre' => $costCentre,
                    ],
                    [
                        'account_code' => config('accounting.petty_cash_account_code'),
                        'credit' => $expense->amount,
                    ],
                ],
            ]);

            $expense->update(['journal_entry_id' => $entry->id]);
        } catch (LogicException $e) {
            Log::warning("GL posting failed for PettyCashExpense #{$expense->id}: {$e->getMessage()}");
        }
    }

    protected function custodianLabel(PettyCashCustodian $custodian): string
    {
        $custodian->loadMissing('user');

        return $custodian->title ?? $custodian->user?->name ?? "Custodian #{$custodian->id}";
    }
}
