<?php

use App\Models\ChartOfAccount;
use App\Models\PettyCashCustodian;
use App\Models\User;
use App\Models\VoteHead;
use App\Services\PettyCashService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\VoteHeadSeeder;

function pcAccountId(string $code): int
{
    return ChartOfAccount::where('account_code', $code)->value('id');
}

beforeEach(function () {
    $this->seed(ChartOfAccountsSeeder::class);
    $this->seed(VoteHeadSeeder::class);

    makeOpenPeriod('2026-08-01', '2026-08-31');

    $this->financeUser = User::factory()->create();
    $this->custodianUser = User::factory()->create();

    $this->custodian = PettyCashCustodian::create([
        'user_id' => $this->custodianUser->id,
        'title' => 'Registrar',
        'opening_float' => 10000,
        'is_active' => true,
    ]);

    $this->ictVoteHead = VoteHead::where('code', 'ICT')->firstOrFail();
});

test('issuing a float posts DR Petty Cash / CR Cash at Bank and increases available balance', function () {
    $transaction = app(PettyCashService::class)->issueFloat([
        'custodian_id' => $this->custodian->id,
        'amount' => 5000,
        'type' => 'topup',
        'transaction_date' => '2026-08-10',
        'issued_by' => $this->financeUser->id,
    ]);

    expect($transaction->journal_entry_id)->not->toBeNull();

    $entry = $transaction->journalEntry;
    $pettyCashLine = $entry->lines->firstWhere('account_id', pcAccountId('1020'));
    $cashLine = $entry->lines->firstWhere('account_id', pcAccountId('1010'));

    expect((float) $pettyCashLine->debit)->toBe(5000.0)
        ->and((float) $cashLine->credit)->toBe(5000.0)
        ->and($this->custodian->fresh()->available_balance)->toBe(15000.0);
});

test('submitting an expense creates a pending row with no GL entry yet', function () {
    $expense = app(PettyCashService::class)->submitExpense([
        'custodian_id' => $this->custodian->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'description' => 'Router repair',
        'amount' => 1500,
        'expense_date' => '2026-08-12',
        'submitted_by' => $this->custodianUser->id,
    ]);

    expect($expense->status)->toBe('pending')
        ->and($expense->journal_entry_id)->toBeNull()
        ->and($this->custodian->fresh()->available_balance)->toBe(10000.0);
});

test('approving an expense posts DR vote-head expense account / CR Petty Cash and reduces available balance', function () {
    $service = app(PettyCashService::class);

    $expense = $service->submitExpense([
        'custodian_id' => $this->custodian->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'description' => 'Router repair',
        'amount' => 1500,
        'expense_date' => '2026-08-12',
        'submitted_by' => $this->custodianUser->id,
    ]);

    $approved = $service->approveExpense($expense, $this->financeUser->id);

    expect($approved->status)->toBe('approved')
        ->and($approved->journal_entry_id)->not->toBeNull();

    $entry = $approved->journalEntry;
    $expenseLine = $entry->lines->firstWhere('account_id', pcAccountId('5050')); // ICT
    $pettyCashLine = $entry->lines->firstWhere('account_id', pcAccountId('1020'));

    expect((float) $expenseLine->debit)->toBe(1500.0)
        ->and((float) $pettyCashLine->credit)->toBe(1500.0)
        ->and($this->custodian->fresh()->available_balance)->toBe(8500.0);
});

test('approving an expense that exceeds the available float balance throws', function () {
    $service = app(PettyCashService::class);

    $expense = $service->submitExpense([
        'custodian_id' => $this->custodian->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'description' => 'New server',
        'amount' => 50000,
        'expense_date' => '2026-08-12',
        'submitted_by' => $this->custodianUser->id,
    ]);

    expect(fn () => $service->approveExpense($expense, $this->financeUser->id))
        ->toThrow(LogicException::class);

    expect($expense->fresh()->status)->toBe('pending');
});

test('rejecting an expense has no GL impact and leaves the balance untouched', function () {
    $service = app(PettyCashService::class);

    $expense = $service->submitExpense([
        'custodian_id' => $this->custodian->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'description' => 'Unnecessary purchase',
        'amount' => 2000,
        'expense_date' => '2026-08-12',
        'submitted_by' => $this->custodianUser->id,
    ]);

    $rejected = $service->rejectExpense($expense, 'Not a valid business expense', $this->financeUser->id);

    expect($rejected->status)->toBe('rejected')
        ->and($rejected->journal_entry_id)->toBeNull()
        ->and($this->custodian->fresh()->available_balance)->toBe(10000.0);
});

test('approving an already-approved expense throws', function () {
    $service = app(PettyCashService::class);

    $expense = $service->submitExpense([
        'custodian_id' => $this->custodian->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'description' => 'Router repair',
        'amount' => 1000,
        'expense_date' => '2026-08-12',
        'submitted_by' => $this->custodianUser->id,
    ]);

    $service->approveExpense($expense, $this->financeUser->id);

    expect(fn () => $service->approveExpense($expense->fresh(), $this->financeUser->id))
        ->toThrow(LogicException::class);
});
