<?php

use App\Models\ChartOfAccount;
use App\Models\FeeCategory;
use App\Models\FeeDefinition;
use App\Models\JournalEntry;
use App\Models\PaymentMethodAccountMap;
use App\Models\Student;
use App\Models\StudentFeeItem;
use App\Models\User;
use App\Services\Accounting\TrialBalanceReportService;
use App\Services\CreditService;
use App\Services\PaymentPostingService;
use App\Services\RefundService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FeeCategoryAccountMapSeeder;
use Database\Seeders\FeeCategorySeeder;
use Database\Seeders\PaymentMethodAccountMapSeeder;

function accountId(string $code): int
{
    return ChartOfAccount::where('account_code', $code)->value('id');
}

function createTestStudent(): Student
{
    $user = User::factory()->create();

    return Student::create([
        'first_name' => 'Test',
        'last_name' => 'Student',
        'email' => $user->email,
        'user_id' => $user->id,
    ]);
}

beforeEach(function () {
    $this->seed(ChartOfAccountsSeeder::class);
    $this->seed(FeeCategorySeeder::class);
    $this->seed(PaymentMethodAccountMapSeeder::class);
    $this->seed(FeeCategoryAccountMapSeeder::class);

    makeOpenPeriod('2026-08-01', '2026-12-31');

    $this->student = createTestStudent();

    $this->tuitionCategory = FeeCategory::where('code', 'tuition')->first();
    $this->feeDefinition = FeeDefinition::create([
        'fee_category_id' => $this->tuitionCategory->id,
        'name' => 'Tuition',
        'scope' => 'student',
        'default_amount' => 5000,
        'active' => true,
    ]);
});

test('creating a StudentFeeItem posts a balanced DR Debtors / CR Revenue charge entry', function () {
    $item = StudentFeeItem::create([
        'student_id' => $this->student->id,
        'fee_definition_id' => $this->feeDefinition->id,
        'description' => 'Tuition — August',
        'amount' => 5000,
        'balance' => 5000,
        'charge_date' => '2026-08-05',
        'status' => 'pending',
    ]);

    $entry = JournalEntry::where('source_type', StudentFeeItem::class)->where('source_id', $item->id)->first();

    expect($entry)->not->toBeNull()
        ->and($entry->status)->toBe('posted');

    $debtorsLine = $entry->lines->firstWhere('account_id', accountId('1100'));
    $revenueLine = $entry->lines->firstWhere('account_id', accountId('4010')); // Tuition Fees

    expect((float) $debtorsLine->debit)->toBe(5000.0)
        ->and((float) $revenueLine->credit)->toBe(5000.0);
});

test('PaymentPostingService::post() with mpesa posts DR Cash / CR Debtors', function () {
    $payment = app(PaymentPostingService::class)->post([
        'student_id' => $this->student->id,
        'payment_date' => '2026-08-10',
        'amount' => 2000,
        'method' => 'mpesa',
        'reference' => 'MPESA-TEST-1',
    ]);

    $entry = JournalEntry::where('source_type', \App\Models\Payment::class)->where('source_id', $payment->id)->first();

    expect($entry)->not->toBeNull();

    $cashLine = $entry->lines->firstWhere('account_id', accountId('1010'));
    $debtorsLine = $entry->lines->firstWhere('account_id', accountId('1100'));

    expect((float) $cashLine->debit)->toBe(2000.0)
        ->and((float) $debtorsLine->credit)->toBe(2000.0);
});

test('payments with method=credit (the path CreditService::applyDiscount/applyScholarship/etc. all route through) post to the waiver/expense account', function () {
    // CreditService::applyDiscount() etc. are thin wrappers that call
    // PaymentPostingService::post() with method='credit' (confirmed by reading
    // CreditService::postCredit()) — this exercises the same account-resolution
    // path without needing a full Enrollment/Course/Intake fixture.
    $admin = User::factory()->create();

    $payment = app(PaymentPostingService::class)->post([
        'student_id' => $this->student->id,
        'payment_date' => '2026-08-10',
        'amount' => 500,
        'method' => 'credit',
        'reference' => 'DISCOUNT-TEST-1',
        'created_by' => $admin->id,
    ]);

    $entry = JournalEntry::where('source_type', \App\Models\Payment::class)->where('source_id', $payment->id)->first();

    $expenseLine = $entry->lines->firstWhere('account_id', accountId('5100'));
    $debtorsLine = $entry->lines->firstWhere('account_id', accountId('1100'));

    expect((float) $expenseLine->debit)->toBe(500.0)
        ->and((float) $debtorsLine->credit)->toBe(500.0);
});

test('CreditService::waiveFee() posts DR Waiver Expense / CR Debtors', function () {
    $item = StudentFeeItem::create([
        'student_id' => $this->student->id,
        'fee_definition_id' => $this->feeDefinition->id,
        'description' => 'Tuition — August',
        'amount' => 5000,
        'balance' => 5000,
        'charge_date' => '2026-08-05',
        'status' => 'pending',
    ]);

    $admin = User::factory()->create();

    app(CreditService::class)->waiveFee($item, 'Financial hardship', $admin, 1000);

    $entries = JournalEntry::where('source_type', StudentFeeItem::class)->where('source_id', $item->id)->get();

    // One for the original charge, one for the waiver.
    expect($entries)->toHaveCount(2);

    $waiverEntry = $entries->last();
    $expenseLine = $waiverEntry->lines->firstWhere('account_id', accountId('5100'));
    $debtorsLine = $waiverEntry->lines->firstWhere('account_id', accountId('1100'));

    expect((float) $expenseLine->debit)->toBe(1000.0)
        ->and((float) $debtorsLine->credit)->toBe(1000.0);
});

test('RefundService::processRefund() (partial) posts DR Debtors / CR cash for the refunded amount only', function () {
    $item = StudentFeeItem::create([
        'student_id' => $this->student->id,
        'fee_definition_id' => $this->feeDefinition->id,
        'description' => 'Tuition — August',
        'amount' => 5000,
        'balance' => 5000,
        'charge_date' => '2026-08-05',
        'status' => 'pending',
    ]);

    $payment = app(PaymentPostingService::class)->post([
        'student_id' => $this->student->id,
        'payment_date' => '2026-08-10',
        'amount' => 3000,
        'method' => 'mpesa',
        'reference' => 'MPESA-TEST-2',
    ]);

    $refundService = app(RefundService::class);
    $refund = $refundService->initiateRefund($payment, 1000, 'Overcharged');
    $refundService->processRefund($refund, 'mpesa', 'REFUND-REF-1');

    $entry = JournalEntry::where('source_type', \App\Models\PaymentRefund::class)->where('source_id', $refund->id)->first();

    expect($entry)->not->toBeNull();

    $debtorsLine = $entry->lines->firstWhere('account_id', accountId('1100'));
    $cashLine = $entry->lines->firstWhere('account_id', accountId('1010'));

    expect((float) $debtorsLine->debit)->toBe(1000.0)
        ->and((float) $cashLine->credit)->toBe(1000.0);
});

test('allocateExistingPayment() does not create a duplicate journal entry', function () {
    $item = StudentFeeItem::create([
        'student_id' => $this->student->id,
        'fee_definition_id' => $this->feeDefinition->id,
        'description' => 'Tuition — August',
        'amount' => 5000,
        'balance' => 5000,
        'charge_date' => '2026-08-05',
        'status' => 'pending',
    ]);

    $payment = app(PaymentPostingService::class)->post([
        'student_id' => $this->student->id,
        'payment_date' => '2026-08-10',
        'amount' => 2000,
        'method' => 'mpesa',
        'reference' => 'MPESA-TEST-3',
    ]);

    $countBefore = JournalEntry::where('source_type', \App\Models\Payment::class)->where('source_id', $payment->id)->count();

    app(PaymentPostingService::class)->allocateExistingPayment($payment);

    $countAfter = JournalEntry::where('source_type', \App\Models\Payment::class)->where('source_id', $payment->id)->count();

    expect($countBefore)->toBe(1)
        ->and($countAfter)->toBe(1);
});

test('trial balance totals stay balanced across a mix of charges, payments, waivers and refunds', function () {
    $item = StudentFeeItem::create([
        'student_id' => $this->student->id,
        'fee_definition_id' => $this->feeDefinition->id,
        'description' => 'Tuition — August',
        'amount' => 5000,
        'balance' => 5000,
        'charge_date' => '2026-08-05',
        'status' => 'pending',
    ]);

    $payment = app(PaymentPostingService::class)->post([
        'student_id' => $this->student->id,
        'payment_date' => '2026-08-10',
        'amount' => 3000,
        'method' => 'mpesa',
        'reference' => 'MPESA-TEST-4',
    ]);

    $admin = User::factory()->create();
    app(CreditService::class)->waiveFee($item->fresh(), 'Goodwill', $admin, 500);

    $refundService = app(RefundService::class);
    $refund = $refundService->initiateRefund($payment, 500, 'Refund test');
    $refundService->processRefund($refund, 'mpesa', 'REFUND-REF-2');

    $result = app(TrialBalanceReportService::class)->generate(null, '2026-08-31');

    expect($result['totals']->balanced)->toBeTrue()
        ->and($result['totals']->total_debit)->toBe($result['totals']->total_credit);
});
