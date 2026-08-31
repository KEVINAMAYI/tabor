<?php

namespace App\Services\Accounting;

use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TrialBalanceReportService
{
    /**
     * Cumulative trial balance as of a date (standard practice), or scoped
     * to a single accounting period if given.
     *
     * Returns ['rows' => Collection<object{account_code, name, account_type,
     * normal_balance, total_debit, total_credit, closing_balance}>,
     * 'totals' => object{total_debit, total_credit, balanced: bool}].
     */
    public function generate(?int $accountingPeriodId = null, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? Carbon::now()->toDateString();

        $rows = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->when($accountingPeriodId, fn ($q) => $q->where('journal_entries.accounting_period_id', $accountingPeriodId))
            ->when(!$accountingPeriodId, fn ($q) => $q->where('journal_entries.entry_date', '<=', $asOfDate))
            ->selectRaw('
                chart_of_accounts.id as account_id,
                chart_of_accounts.account_code,
                chart_of_accounts.name,
                chart_of_accounts.account_type,
                chart_of_accounts.normal_balance,
                SUM(journal_entry_lines.debit) as total_debit,
                SUM(journal_entry_lines.credit) as total_credit
            ')
            ->groupBy(
                'chart_of_accounts.id',
                'chart_of_accounts.account_code',
                'chart_of_accounts.name',
                'chart_of_accounts.account_type',
                'chart_of_accounts.normal_balance'
            )
            ->orderBy('chart_of_accounts.account_code')
            ->get()
            ->map(function ($row) {
                $debit = (float) $row->total_debit;
                $credit = (float) $row->total_credit;

                $row->closing_balance = $row->normal_balance === 'dr'
                    ? round($debit - $credit, 2)
                    : round($credit - $debit, 2);

                return $row;
            });

        return [
            'rows' => $rows,
            'totals' => $this->totals($rows),
        ];
    }

    protected function totals(Collection $rows): object
    {
        $totalDebit = round((float) $rows->sum('total_debit'), 2);
        $totalCredit = round((float) $rows->sum('total_credit'), 2);

        return (object) [
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            // If this is ever false, JournalPostingService allowed an
            // unbalanced entry through — a bug in the posting engine, not a
            // data problem. Surface it visibly rather than silently reporting.
            'balanced' => $totalDebit === $totalCredit,
        ];
    }
}
