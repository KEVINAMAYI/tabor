<?php

use App\Models\Budget;
use App\Models\FinancialYear;
use App\Models\PettyCashCustodian;
use App\Models\SubVoteHead;
use App\Models\User;
use App\Models\VoteHead;
use App\Services\Accounting\BudgetReportService;
use App\Services\PettyCashService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\VoteHeadSeeder;

beforeEach(function () {
    $this->seed(ChartOfAccountsSeeder::class);
    $this->seed(VoteHeadSeeder::class);

    $this->period = makeOpenPeriod('2026-08-01', '2026-08-31');
    $this->financialYear = $this->period->financialYear;

    $this->financeUser = User::factory()->create();
    $this->custodianUser = User::factory()->create();

    $this->custodian = PettyCashCustodian::create([
        'user_id' => $this->custodianUser->id,
        'title' => 'Registrar',
        'opening_float' => 20000,
        'is_active' => true,
    ]);

    $this->ictVoteHead = VoteHead::where('code', 'ICT')->firstOrFail();
});

test('a budget with no matching expenditure shows zero actual and full remaining', function () {
    $budget = Budget::create([
        'financial_year_id' => $this->financialYear->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'budgeted_amount' => 10000,
    ]);

    $result = app(BudgetReportService::class)->generate($this->financialYear->id);
    $row = $result['rows']->firstWhere('budget_id', $budget->id);

    expect($row->actual_amount)->toBe(0.0)
        ->and($row->variance)->toBe(10000.0)
        ->and($row->over_budget)->toBeFalse();
});

test('an approved petty cash expense against the vote head increases actual and reduces variance', function () {
    Budget::create([
        'financial_year_id' => $this->financialYear->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'budgeted_amount' => 10000,
    ]);

    $service = app(PettyCashService::class);
    $expense = $service->submitExpense([
        'custodian_id' => $this->custodian->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'description' => 'Router repair',
        'amount' => 4000,
        'expense_date' => '2026-08-12',
        'submitted_by' => $this->custodianUser->id,
    ]);
    $service->approveExpense($expense, $this->financeUser->id);

    $result = app(BudgetReportService::class)->generate($this->financialYear->id);
    $row = $result['rows']->first();

    expect($row->actual_amount)->toBe(4000.0)
        ->and($row->variance)->toBe(6000.0)
        ->and($row->over_budget)->toBeFalse();
});

test('a sub-vote-head budget only counts expenses tagged to that sub vote head, not the whole vote head', function () {
    $internet = SubVoteHead::where('vote_head_id', $this->ictVoteHead->id)->where('name', 'Internet Bills')->firstOrFail();

    $budget = Budget::create([
        'financial_year_id' => $this->financialYear->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'sub_vote_head_id' => $internet->id,
        'budgeted_amount' => 3000,
    ]);

    $service = app(PettyCashService::class);

    // Tagged to "Internet Bills" — should count.
    $e1 = $service->submitExpense([
        'custodian_id' => $this->custodian->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'sub_vote_head_id' => $internet->id,
        'description' => 'ISP bill',
        'amount' => 2000,
        'expense_date' => '2026-08-12',
        'submitted_by' => $this->custodianUser->id,
    ]);
    $service->approveExpense($e1, $this->financeUser->id);

    // Same vote head but NOT tagged to this sub vote head — should not count.
    $e2 = $service->submitExpense([
        'custodian_id' => $this->custodian->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'description' => 'Printer toner',
        'amount' => 1500,
        'expense_date' => '2026-08-12',
        'submitted_by' => $this->custodianUser->id,
    ]);
    $service->approveExpense($e2, $this->financeUser->id);

    $result = app(BudgetReportService::class)->generate($this->financialYear->id);
    $row = $result['rows']->firstWhere('budget_id', $budget->id);

    expect($row->actual_amount)->toBe(2000.0);
});

test('over_budget is true once actual expenditure exceeds the budgeted amount', function () {
    Budget::create([
        'financial_year_id' => $this->financialYear->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'budgeted_amount' => 1000,
    ]);

    $service = app(PettyCashService::class);
    $expense = $service->submitExpense([
        'custodian_id' => $this->custodian->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'description' => 'Emergency server repair',
        'amount' => 1500,
        'expense_date' => '2026-08-12',
        'submitted_by' => $this->custodianUser->id,
    ]);
    $service->approveExpense($expense, $this->financeUser->id);

    $result = app(BudgetReportService::class)->generate($this->financialYear->id);
    $row = $result['rows']->first();

    expect($row->over_budget)->toBeTrue()
        ->and($row->variance)->toBe(-500.0);
});
