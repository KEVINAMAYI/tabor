<?php

use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\Accounting\JournalPostingService;
use Database\Seeders\ChartOfAccountsSeeder;

beforeEach(function () {
    $this->seed(ChartOfAccountsSeeder::class);
    $this->service = app(JournalPostingService::class);
    $this->user = User::factory()->create();
});

test('post() creates a balanced posted journal entry', function () {
    makeOpenPeriod();

    $entry = $this->service->post([
        'entry_date' => '2026-08-15',
        'description' => 'Test entry',
        'lines' => [
            ['account_code' => '1010', 'debit' => 1000],
            ['account_code' => '1100', 'credit' => 1000],
        ],
    ]);

    expect($entry->status)->toBe('posted')
        ->and($entry->lines)->toHaveCount(2)
        ->and((float) $entry->lines->sum('debit'))->toBe(1000.0)
        ->and((float) $entry->lines->sum('credit'))->toBe(1000.0);
});

test('post() throws when debits and credits do not balance', function () {
    makeOpenPeriod();

    $this->service->post([
        'entry_date' => '2026-08-15',
        'description' => 'Unbalanced',
        'lines' => [
            ['account_code' => '1010', 'debit' => 1000],
            ['account_code' => '1100', 'credit' => 500],
        ],
    ]);
})->throws(LogicException::class, 'do not balance');

test('post() throws when no accounting period covers the entry date', function () {
    // No period created at all.
    $this->service->post([
        'entry_date' => '2026-08-15',
        'description' => 'No period',
        'lines' => [
            ['account_code' => '1010', 'debit' => 100],
            ['account_code' => '1100', 'credit' => 100],
        ],
    ]);
})->throws(LogicException::class, 'No accounting period');

test('post() throws when the covering period is closed', function () {
    makeOpenPeriod(status: 'closed');

    $this->service->post([
        'entry_date' => '2026-08-15',
        'description' => 'Closed period',
        'lines' => [
            ['account_code' => '1010', 'debit' => 100],
            ['account_code' => '1100', 'credit' => 100],
        ],
    ]);
})->throws(LogicException::class, 'closed');

test('post() throws for an unknown account code', function () {
    makeOpenPeriod();

    $this->service->post([
        'entry_date' => '2026-08-15',
        'description' => 'Bad account',
        'lines' => [
            ['account_code' => '9999', 'debit' => 100],
            ['account_code' => '1100', 'credit' => 100],
        ],
    ]);
})->throws(LogicException::class, 'Unknown chart of accounts code');

test('draft() leaves the entry unposted until approveAndPost()', function () {
    makeOpenPeriod();

    $entry = $this->service->draft([
        'entry_date' => '2026-08-15',
        'description' => 'Draft entry',
        'lines' => [
            ['account_code' => '1010', 'debit' => 200],
            ['account_code' => '1100', 'credit' => 200],
        ],
    ]);

    expect($entry->status)->toBe('draft')
        ->and($entry->posted_at)->toBeNull();

    $posted = $this->service->approveAndPost($entry, $this->user->id);

    expect($posted->status)->toBe('posted')
        ->and($posted->approved_by)->toBe($this->user->id);
});

test('reverse() posts a mirror-image entry and marks the original reversed', function () {
    makeOpenPeriod();

    $entry = $this->service->post([
        'entry_date' => '2026-08-15',
        'description' => 'Original',
        'lines' => [
            ['account_code' => '1010', 'debit' => 500],
            ['account_code' => '1100', 'credit' => 500],
        ],
    ]);

    $reversal = $this->service->reverse($entry, 'test reversal', $this->user->id);

    $entry->refresh();

    expect($entry->status)->toBe('reversed')
        ->and($entry->reversal_journal_entry_id)->toBe($reversal->id)
        ->and($reversal->status)->toBe('posted');

    $cashLine = $reversal->lines->firstWhere('account_id', ChartOfAccount::where('account_code', '1010')->value('id'));
    $debtorsLine = $reversal->lines->firstWhere('account_id', ChartOfAccount::where('account_code', '1100')->value('id'));

    expect((float) $cashLine->credit)->toBe(500.0)
        ->and((float) $debtorsLine->debit)->toBe(500.0);
});
