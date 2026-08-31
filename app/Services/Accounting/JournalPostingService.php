<?php

namespace App\Services\Accounting;

use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Generic double-entry posting API. Every module that needs to move money
 * through the General Ledger (Student Finance today; Petty Cash, Procurement,
 * Payroll, Inventory, Fixed Assets in later phases) calls this class instead
 * of writing balances directly — it has no knowledge of any calling domain.
 *
 * Expected `$data` shape for post()/draft():
 * [
 *   'entry_date'   => Carbon|string (required),
 *   'description'  => string (required),
 *   'reference'    => string|null,
 *   'source_type'  => string|null (e.g. \App\Models\Payment::class),
 *   'source_id'    => int|null,
 *   'created_by'   => int|null,
 *   'lines'        => [
 *       ['account_code' => '1010', 'debit' => 5000, 'credit' => 0, 'cost_centre' => null, 'description' => null],
 *       ['account_code' => '1100', 'debit' => 0, 'credit' => 5000],
 *   ],
 * ]
 */
class JournalPostingService
{
    /**
     * Build and immediately post a balanced journal entry.
     */
    public function post(array $data): JournalEntry
    {
        return $this->create($data, status: 'posted');
    }

    /**
     * Build a balanced journal entry but leave it as a draft, for the manual
     * journal-entry UI's maker-checker flow.
     */
    public function draft(array $data): JournalEntry
    {
        return $this->create($data, status: 'draft');
    }

    /**
     * Move a draft entry to posted.
     */
    public function approveAndPost(JournalEntry $entry, int $approverId): JournalEntry
    {
        if ($entry->status !== 'draft') {
            throw new LogicException("Journal entry #{$entry->id} is not a draft (status: {$entry->status}).");
        }

        return DB::transaction(function () use ($entry, $approverId) {
            $entry->loadMissing('lines');
            $this->assertBalanced($entry->lines->toArray());
            $this->resolveOpenPeriod($entry->entry_date);

            $entry->update([
                'status' => 'posted',
                'approved_by' => $approverId,
                'approved_at' => now(),
                'posted_at' => now(),
            ]);

            return $entry->fresh('lines');
        });
    }

    /**
     * Post a mirror-image (debits<->credits swapped) entry linked to the
     * original, and mark the original as reversed. Used for full-entry
     * corrections/voids. Partial corrections (e.g. a partial refund) should
     * call post() with their own explicit lines instead — see RefundService.
     */
    public function reverse(JournalEntry $entry, string $reason, ?int $userId = null): JournalEntry
    {
        if ($entry->status !== 'posted') {
            throw new LogicException("Journal entry #{$entry->id} is not posted (status: {$entry->status}) and cannot be reversed.");
        }

        return DB::transaction(function () use ($entry, $reason, $userId) {
            $entry->loadMissing('lines.account');

            $mirrorLines = $entry->lines->map(fn ($line) => [
                'account_code' => $line->account->account_code,
                'debit' => (float) $line->credit,
                'credit' => (float) $line->debit,
                'cost_centre' => $line->cost_centre,
                'description' => $line->description,
            ])->all();

            $reversalEntry = $this->create([
                'entry_date' => now()->toDateString(),
                'description' => "Reversal of JE #{$entry->id}: {$reason}",
                'reference' => $entry->reference,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
                'created_by' => $userId,
                'lines' => $mirrorLines,
            ], status: 'posted');

            $entry->update([
                'status' => 'reversed',
                'reversed_by' => $userId,
                'reversed_at' => now(),
                'reversal_journal_entry_id' => $reversalEntry->id,
            ]);

            return $reversalEntry;
        });
    }

    protected function create(array $data, string $status): JournalEntry
    {
        $lines = $data['lines'] ?? [];

        if (empty($lines)) {
            throw new LogicException('A journal entry must have at least one line.');
        }

        $this->assertBalanced($lines);

        $entryDate = $data['entry_date'] ?? now()->toDateString();

        return DB::transaction(function () use ($data, $lines, $status, $entryDate) {
            $period = $this->resolveOpenPeriod($entryDate);

            $entry = JournalEntry::create([
                'accounting_period_id' => $period->id,
                'entry_date' => $entryDate,
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? '',
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'status' => $status,
                'created_by' => $data['created_by'] ?? null,
                'approved_by' => $status === 'posted' ? ($data['created_by'] ?? null) : null,
                'approved_at' => $status === 'posted' ? now() : null,
                'posted_at' => $status === 'posted' ? now() : null,
            ]);

            foreach ($lines as $line) {
                $account = $this->resolveAccount($line['account_code']);

                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $account->id,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'cost_centre' => $line['cost_centre'] ?? null,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry->fresh('lines.account');
        });
    }

    protected function resolveAccount(string $accountCode): ChartOfAccount
    {
        $account = ChartOfAccount::where('account_code', $accountCode)->first();

        if (!$account) {
            throw new LogicException("Unknown chart of accounts code '{$accountCode}'.");
        }

        if (!$account->is_active) {
            throw new LogicException("Chart of accounts code '{$accountCode}' ({$account->name}) is inactive.");
        }

        return $account;
    }

    protected function resolveOpenPeriod(\DateTimeInterface|string $date): AccountingPeriod
    {
        $period = AccountingPeriod::containingDate($date)->first();

        if (!$period) {
            throw new LogicException("No accounting period covers {$date}. Create a Financial Year/Accounting Period before posting.");
        }

        if (!$period->isOpen()) {
            throw new LogicException("Accounting period '{$period->name}' is {$period->status} — cannot post to it.");
        }

        return $period;
    }

    protected function assertBalanced(array $lines): void
    {
        $debits = round(array_sum(array_map(fn ($l) => (float) ($l['debit'] ?? 0), $lines)), 2);
        $credits = round(array_sum(array_map(fn ($l) => (float) ($l['credit'] ?? 0), $lines)), 2);

        if ($debits !== $credits) {
            throw new LogicException("Journal entry lines do not balance: debits={$debits}, credits={$credits}.");
        }

        if ($debits <= 0) {
            throw new LogicException('Journal entry lines must total more than zero.');
        }
    }
}
