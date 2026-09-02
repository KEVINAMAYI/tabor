<?php

use App\Models\Course;
use App\Models\CourseFeePlan;
use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\FeeCategory;
use App\Models\FeeDefinition;
use App\Models\Intake;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Models\StudentFeeItem;
use App\Models\Trimester;
use App\Models\User;
use App\Services\CreditService;
use App\Services\Finance\StudentLedgerService;
use App\Services\PaymentPostingService;

function makeLedgerTestSetup(string $suffix): array
{
    $intake = Intake::create(['name' => "Intake {$suffix}", 'starts_at' => '2026-01-01']);

    $course = Course::create([
        'title' => "Ledger Test Course {$suffix}",
        'code' => "LTC-{$suffix}",
        'number_of_trimesters' => '1',
        'allows_continuous_intake' => false,
    ]);

    $trimester = Trimester::create([
        'academic_year_id' => \App\Models\AcademicYear::firstOrCreate(
            ['name' => '2026'],
            ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'active' => true]
        )->id,
        'name' => "Trimester {$suffix}",
        'trimester_number' => 1,
        'start_date' => '2026-01-01',
        'end_date' => '2026-04-30',
        'status' => 'active',
    ]);

    $user = User::factory()->create();
    $student = Student::create([
        'first_name' => 'Ledger',
        'last_name' => 'Test',
        'email' => $user->email,
        'user_id' => $user->id,
        'admission_number' => "LT-{$suffix}",
    ]);

    $enrollment = Enrollment::create([
        'course_id' => $course->id,
        'intake_id' => $intake->id,
        'student_id' => $student->id,
        'status' => 'active',
        'assigned_start_trimester_id' => $trimester->id,
        'admission_date' => '2026-01-01',
    ]);

    $progression = EnrollmentProgression::create([
        'student_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'trimester_id' => $trimester->id,
        'trimester_sequence' => 1,
        'status' => 'active',
        'started_at' => '2026-01-01',
    ]);

    return compact('course', 'student', 'enrollment', 'progression', 'trimester');
}

function makeLedgerCharge(array $setup, float $amount, string $description = 'Tuition Fee'): StudentFeeItem
{
    $category = FeeCategory::create(['code' => 'ltc-' . uniqid(), 'name' => 'Tuition']);
    $feeDefinition = FeeDefinition::create([
        'fee_category_id' => $category->id,
        'name' => $description,
        'scope' => 'student',
        'default_amount' => $amount,
        'active' => true,
    ]);

    return StudentFeeItem::create([
        'student_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'enrollment_progression_id' => $setup['progression']->id,
        'fee_definition_id' => $feeDefinition->id,
        'description' => $description,
        'amount' => $amount,
        'balance' => $amount,
        'charge_date' => '2026-01-05',
        'status' => 'pending',
    ]);
}

test('a fully allocated payment credits its actual allocated amount (regression guard)', function () {
    $setup = makeLedgerTestSetup('full');
    makeLedgerCharge($setup, 1000);

    app(PaymentPostingService::class)->post([
        'student_id' => $setup['student']->id,
        'payment_date' => '2026-01-10',
        'amount' => 1000,
        'method' => 'mpesa',
        'reference' => 'FULL-PAY',
    ]);

    $statement = app(StudentLedgerService::class)->buildProgressionStatement($setup['student'], $setup['progression']);

    $paymentRow = $statement['ledger']->firstWhere('source_type', 'payment');

    expect($paymentRow['cr'])->toBe(1000.0)
        ->and($statement['closing_balance'])->toBe(0.0);
});

test('an unallocated payment credits nothing — the balance still reflects the full unpaid charge', function () {
    $setup = makeLedgerTestSetup('unalloc');
    makeLedgerCharge($setup, 1000);

    // Simulates the MpesaApi bypass bug: a raw Payment with zero allocations.
    Payment::create([
        'student_id' => $setup['student']->id,
        'enrollment_id' => null,
        'payment_date' => '2026-01-10',
        'amount' => 1000,
        'unallocated_balance' => 1000,
        'method' => 'mpesa',
        'status' => 'completed',
    ]);

    $statement = app(StudentLedgerService::class)->buildProgressionStatement($setup['student'], $setup['progression']);

    $paymentRow = $statement['ledger']->firstWhere('source_type', 'payment');

    expect($paymentRow)->toBeNull()
        ->and($statement['closing_balance'])->toBe(1000.0);
});

test('a partially allocated payment credits only the allocated portion, not the raw amount', function () {
    $setup = makeLedgerTestSetup('partial');
    // Only 400 outstanding — a 1000 payment can only allocate 400 of itself.
    makeLedgerCharge($setup, 400);

    $payment = app(PaymentPostingService::class)->post([
        'student_id' => $setup['student']->id,
        'payment_date' => '2026-01-10',
        'amount' => 1000,
        'method' => 'mpesa',
        'reference' => 'PARTIAL-PAY',
    ]);

    expect((float) $payment->fresh()->unallocated_balance)->toBe(600.0);

    $statement = app(StudentLedgerService::class)->buildProgressionStatement($setup['student'], $setup['progression']);

    $paymentRow = $statement['ledger']->firstWhere('source_type', 'payment');

    expect($paymentRow['cr'])->toBe(400.0)
        ->and($statement['closing_balance'])->toBe(0.0);
});

test('a waiver on one fee item is unaffected by an unrelated unallocated payment', function () {
    $setup = makeLedgerTestSetup('waiver');
    $item = makeLedgerCharge($setup, 1000);

    $admin = User::factory()->create();
    app(CreditService::class)->waiveFee($item, 'Financial hardship', $admin);

    // An unrelated unallocated payment sitting on the same student.
    Payment::create([
        'student_id' => $setup['student']->id,
        'enrollment_id' => null,
        'payment_date' => '2026-01-15',
        'amount' => 500,
        'unallocated_balance' => 500,
        'method' => 'mpesa',
        'status' => 'completed',
    ]);

    $statement = app(StudentLedgerService::class)->buildProgressionStatement($setup['student'], $setup['progression']);

    $waiverRow = $statement['ledger']->firstWhere('source_type', 'waiver');
    $paymentRow = $statement['ledger']->firstWhere('source_type', 'payment');

    expect($waiverRow['cr'])->toBe(1000.0)
        ->and($paymentRow)->toBeNull()
        ->and($statement['closing_balance'])->toBe(0.0);
});

test('a cross-enrollment payment (date outside this progression, allocated into it) still credits its allocated amount', function () {
    $setup = makeLedgerTestSetup('cross');
    $item = makeLedgerCharge($setup, 1000);

    // Payment dated well before the progression's window, no enrollment_id —
    // wouldn't be "date-owned", but has been allocated directly to this
    // progression's fee item (the cross-progression ownership case).
    $payment = Payment::create([
        'student_id' => null,
        'enrollment_id' => null,
        'payment_date' => '2025-06-01',
        'amount' => 1000,
        'unallocated_balance' => 0,
        'method' => 'mpesa',
        'status' => 'completed',
    ]);

    PaymentAllocation::create([
        'payment_id' => $payment->id,
        'student_fee_item_id' => $item->id,
        'amount_allocated' => 1000,
    ]);

    $statement = app(StudentLedgerService::class)->buildProgressionStatement($setup['student'], $setup['progression']);

    $paymentRow = $statement['ledger']->firstWhere('source_type', 'payment');

    expect($paymentRow['cr'])->toBe(1000.0)
        ->and($statement['closing_balance'])->toBe(0.0);
});
