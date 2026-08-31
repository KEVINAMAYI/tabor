<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Shared helper for Accounting tests: creates a FinancialYear + a single
 * AccountingPeriod covering the given range, since JournalPostingService
 * refuses to post outside an open period.
 */
function makeOpenPeriod(string $start = '2026-08-01', string $end = '2026-08-31', string $status = 'open'): \App\Models\AccountingPeriod
{
    $year = \App\Models\FinancialYear::create([
        'name' => 'FY-' . $start,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'active' => true,
    ]);

    return \App\Models\AccountingPeriod::create([
        'financial_year_id' => $year->id,
        'name' => 'Period ' . $start,
        'period_number' => (int) date('n', strtotime($start)),
        'start_date' => $start,
        'end_date' => $end,
        'status' => $status,
    ]);
}
