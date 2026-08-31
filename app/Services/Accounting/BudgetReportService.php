<?php

namespace App\Services\Accounting;

use App\Models\Budget;
use App\Models\FinancialYear;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

/**
 * "Budget vs Actual" (SRS §17 / §8). Actual expenditure is computed live
 * from posted journal_entry_lines debited against each vote head's mapped
 * expense account — not stored anywhere — so it automatically reflects
 * Petty Cash today, and Procurement/Payroll postings once those phases
 * exist, with no changes needed here.
 *
 * A vote-head-level budget (sub_vote_head_id null) counts ALL debits to
 * that expense account, regardless of which sub-vote-head (or none) they
 * were tagged with. A sub-vote-head-level budget only counts debits whose
 * journal_entry_lines.cost_centre matches that sub-vote-head's name exactly
 * — the same free-text value PettyCashService writes when posting.
 */
class BudgetReportService
{
    /**
     * Returns ['financialYear' => FinancialYear, 'rows' => Collection<object{
     *   budget_id, vote_head, sub_vote_head, budgeted_amount, actual_amount,
     *   variance, percent_used, over_budget: bool
     * }>, 'totals' => object{total_budgeted, total_actual}].
     */
    public function generate(int $financialYearId): array
    {
        $financialYear = FinancialYear::findOrFail($financialYearId);

        $budgets = Budget::where('financial_year_id', $financialYearId)
            ->with(['voteHead.expenseAccount', 'subVoteHead'])
            ->get();

        $actuals = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.entry_date', [$financialYear->start_date, $financialYear->end_date])
            ->whereIn('journal_entry_lines.account_id', $budgets->pluck('voteHead.expense_account_id')->unique())
            ->selectRaw('journal_entry_lines.account_id, journal_entry_lines.cost_centre, SUM(journal_entry_lines.debit) as total')
            ->groupBy('journal_entry_lines.account_id', 'journal_entry_lines.cost_centre')
            ->get();

        $byAccount = [];
        $byAccountAndCostCentre = [];

        foreach ($actuals as $row) {
            $byAccount[$row->account_id] = ($byAccount[$row->account_id] ?? 0) + (float) $row->total;

            if ($row->cost_centre !== null) {
                $byAccountAndCostCentre[$row->account_id][$row->cost_centre] =
                    ($byAccountAndCostCentre[$row->account_id][$row->cost_centre] ?? 0) + (float) $row->total;
            }
        }

        $rows = $budgets->map(function (Budget $budget) use ($byAccount, $byAccountAndCostCentre) {
            $accountId = $budget->voteHead->expense_account_id;

            $actual = $budget->sub_vote_head_id
                ? (float) ($byAccountAndCostCentre[$accountId][$budget->subVoteHead->name] ?? 0)
                : (float) ($byAccount[$accountId] ?? 0);

            $budgeted = (float) $budget->budgeted_amount;
            $variance = round($budgeted - $actual, 2);

            return (object) [
                'budget_id' => $budget->id,
                'vote_head' => $budget->voteHead->name,
                'sub_vote_head' => $budget->subVoteHead?->name,
                'budgeted_amount' => $budgeted,
                'actual_amount' => round($actual, 2),
                'variance' => $variance,
                'percent_used' => $budgeted > 0 ? round(($actual / $budgeted) * 100, 1) : null,
                'over_budget' => $actual > $budgeted,
            ];
        })->sortBy('vote_head')->values();

        return [
            'financialYear' => $financialYear,
            'rows' => $rows,
            'totals' => $this->totals($rows),
        ];
    }

    protected function totals(Collection $rows): object
    {
        return (object) [
            'total_budgeted' => round((float) $rows->sum('budgeted_amount'), 2),
            'total_actual' => round((float) $rows->sum('actual_amount'), 2),
        ];
    }
}
