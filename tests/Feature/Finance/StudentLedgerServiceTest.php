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

test('a same-enrollment payment dated in trimester 2 but FIFO-allocated to trimester 1\'s older unpaid charge shows on trimester 1\'s statement, not trimester 2\'s', function () {
    // Reproduces a real production bug: PaymentPostingService allocates FIFO
    // against the oldest unpaid charge first, regardless of the payment's
    // own date. A payment dated well inside T2's window can legitimately
    // land on T1's older unpaid charge. Before this fix, such a payment
    // vanished from every statement — not "date-owned" by T1 (wrong date
    // window) and its allocation didn't belong to T2 (the progression whose
    // statement was being viewed), so it fell through both branches.
    $setup = makeLedgerTestSetup('t1t2');
    $t1Item = makeLedgerCharge($setup, 1000, 'Tuition — T1');

    $t2 = \App\Models\Trimester::create([
        'academic_year_id' => $setup['trimester']->academic_year_id,
        'name' => 'Trimester 2',
        'trimester_number' => 2,
        'start_date' => '2026-05-01',
        'end_date' => '2026-08-31',
        'status' => 'active',
    ]);

    $progression2 = EnrollmentProgression::create([
        'student_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'trimester_id' => $t2->id,
        'trimester_sequence' => 2,
        'status' => 'active',
        'started_at' => '2026-05-01',
    ]);

    $t2Item = StudentFeeItem::create([
        'student_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'enrollment_progression_id' => $progression2->id,
        'fee_definition_id' => $t1Item->fee_definition_id,
        'description' => 'Tuition — T2',
        'amount' => 1000,
        'balance' => 1000,
        'charge_date' => '2026-05-05',
        'status' => 'pending',
    ]);

    // Dated inside T2's window, but T1's charge (Jan) is older, so
    // PaymentPostingService's FIFO allocation lands it there instead.
    app(PaymentPostingService::class)->post([
        'student_id' => $setup['student']->id,
        'payment_date' => '2026-06-01',
        'amount' => 1000,
        'method' => 'mpesa',
        'reference' => 'T2-DATED-T1-ALLOCATED',
    ]);

    $t1Statement = app(StudentLedgerService::class)->buildProgressionStatement($setup['student'], $setup['progression']);
    $t2Statement = app(StudentLedgerService::class)->buildProgressionStatement($setup['student'], $progression2);

    $t1PaymentRow = $t1Statement['ledger']->firstWhere('source_type', 'payment');
    $t2PaymentRow = $t2Statement['ledger']->firstWhere('source_type', 'payment');

    expect($t1PaymentRow)->not->toBeNull()
        ->and($t1PaymentRow['cr'])->toBe(1000.0)
        ->and($t1Statement['closing_balance'])->toBe(0.0);

    expect($t2PaymentRow)->toBeNull()
        ->and($t2Statement['closing_balance'])->toBe(1000.0);

    expect($t1Item->fresh()->balance)->toEqual('0.00')
        ->and($t2Item->fresh()->balance)->toEqual('1000.00');
});

test('a payment that pays in advance for a not-yet-started German-chain level is attributed to whichever level was actually running on its own date', function () {
    // Reproduces two real production bugs, fixed in sequence (Wellington
    // Ngunyi / Clinton Ayaye Omwario, Sep 2026):
    //
    // 1. A payment recorded against GLA1 (enrollment_id set to the GLA1
    //    enrollment) is split — via the admin allocation modal, not the
    //    auto-FIFO engine — across GLA1's own fee item AND a GLB1 fee item
    //    that doesn't exist yet from the student's perspective (GLB1 hasn't
    //    started). isPreviousGermanCoursePayment() used to suppress the
    //    GLB1 portion entirely, assuming it was "already counted" via the
    //    opening-balance chain — it wasn't, so it was invisible everywhere
    //    despite genuinely reducing the GLB1 fee item's real balance.
    //
    // 2. Once visible, it showed on GLB1's OWN statement — but the payment
    //    was actually made while GLA1 was still the running level (its own
    //    date falls inside GLA1's window). The user's expectation: a
    //    payment made before a level even starts shouldn't appear as a
    //    line item on that level's ledger — it belongs to whichever level
    //    was actually running on the payment's date, and only reaches the
    //    later level via its opening "Balance Brought Forward".
    $intake = Intake::create(['name' => 'Intake german', 'starts_at' => '2026-01-01']);
    $academicYear = \App\Models\AcademicYear::firstOrCreate(
        ['name' => '2026'],
        ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'active' => true]
    );

    $gla1Course = Course::create(['title' => 'German Language', 'code' => 'GLA1', 'number_of_trimesters' => '1', 'allows_continuous_intake' => false]);
    $glb1Course = Course::create(['title' => 'German Language', 'code' => 'GLB1', 'number_of_trimesters' => '1', 'allows_continuous_intake' => false]);

    $gla1Trimester = Trimester::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'GLA1 Trimester', 'trimester_number' => 1,
        'start_date' => '2026-01-01', 'end_date' => '2026-04-30', 'status' => 'active',
    ]);
    $glb1Trimester = Trimester::create([
        'academic_year_id' => $academicYear->id,
        'name' => 'GLB1 Trimester', 'trimester_number' => 2,
        'start_date' => '2026-05-01', 'end_date' => '2026-08-31', 'status' => 'active',
    ]);

    $user = User::factory()->create();
    $student = Student::create([
        'first_name' => 'German', 'last_name' => 'Chain',
        'email' => $user->email, 'user_id' => $user->id, 'admission_number' => 'LT-german',
    ]);

    $gla1Enrollment = Enrollment::create([
        'course_id' => $gla1Course->id, 'intake_id' => $intake->id, 'student_id' => $student->id,
        'status' => 'course_completed', 'assigned_start_trimester_id' => $gla1Trimester->id, 'admission_date' => '2026-01-01',
    ]);
    $glb1Enrollment = Enrollment::create([
        'course_id' => $glb1Course->id, 'intake_id' => $intake->id, 'student_id' => $student->id,
        'status' => 'active', 'assigned_start_trimester_id' => $glb1Trimester->id, 'admission_date' => '2026-05-01',
    ]);

    $gla1Progression = EnrollmentProgression::create([
        'student_id' => $student->id, 'enrollment_id' => $gla1Enrollment->id, 'trimester_id' => $gla1Trimester->id,
        'trimester_sequence' => 1, 'status' => 'completed', 'started_at' => '2026-01-01',
    ]);
    $glb1Progression = EnrollmentProgression::create([
        'student_id' => $student->id, 'enrollment_id' => $glb1Enrollment->id, 'trimester_id' => $glb1Trimester->id,
        'trimester_sequence' => 1, 'status' => 'active', 'started_at' => '2026-05-01',
    ]);

    $category = FeeCategory::create(['code' => 'ltc-german', 'name' => 'Tuition']);
    $gla1FeeDefinition = FeeDefinition::create(['fee_category_id' => $category->id, 'name' => 'Tuition Fee', 'scope' => 'student', 'default_amount' => 25000, 'active' => true]);
    $glb1FeeDefinition = FeeDefinition::create(['fee_category_id' => $category->id, 'name' => 'Tuition Fee', 'scope' => 'student', 'default_amount' => 35000, 'active' => true]);

    $gla1Item = StudentFeeItem::create([
        'student_id' => $student->id, 'enrollment_id' => $gla1Enrollment->id, 'enrollment_progression_id' => $gla1Progression->id,
        'fee_definition_id' => $gla1FeeDefinition->id, 'description' => 'Tuition Fee', 'amount' => 25000, 'balance' => 25000,
        'charge_date' => '2026-01-05', 'status' => 'pending',
    ]);
    $glb1Item = StudentFeeItem::create([
        'student_id' => $student->id, 'enrollment_id' => $glb1Enrollment->id, 'enrollment_progression_id' => $glb1Progression->id,
        'fee_definition_id' => $glb1FeeDefinition->id, 'description' => 'Tuition Fee', 'amount' => 35000, 'balance' => 35000,
        'charge_date' => '2026-05-05', 'status' => 'pending',
    ]);

    // One 27,000 payment recorded under the GLA1 enrollment, manually split
    // via the admin modal: 25,000 clears GLA1 in full, 2,000 overflows onto
    // GLB1's tuition.
    $payment = Payment::create([
        'student_id' => $student->id, 'enrollment_id' => $gla1Enrollment->id,
        'payment_date' => '2026-04-01', 'amount' => 27000, 'unallocated_balance' => 0,
        'method' => 'mpesa', 'status' => 'completed', 'reference' => 'GERMAN-OVERFLOW',
    ]);

    PaymentAllocation::create(['payment_id' => $payment->id, 'student_fee_item_id' => $gla1Item->id, 'amount_allocated' => 25000]);
    PaymentAllocation::create(['payment_id' => $payment->id, 'student_fee_item_id' => $glb1Item->id, 'amount_allocated' => 2000]);

    $gla1Item->update(['amount_paid' => 25000, 'balance' => 0, 'status' => 'paid']);
    $glb1Item->update(['amount_paid' => 2000, 'balance' => 33000, 'status' => 'partial']);

    $service = app(StudentLedgerService::class);
    $gla1Statement = $service->buildProgressionStatement($student, $gla1Progression);
    $glb1Statement = $service->buildProgressionStatement($student, $glb1Progression);

    $gla1PaymentRow = $gla1Statement['ledger']->firstWhere('source_type', 'payment');
    $glb1PaymentRow = $glb1Statement['ledger']->firstWhere('source_type', 'payment');

    // The full 27,000 shows on GLA1 (the level actually running on
    // 2026-04-01) — GLA1's own 25,000 charge is cleared, leaving a 2,000
    // credit balance.
    expect($gla1PaymentRow['cr'])->toBe(27000.0)
        ->and($gla1Statement['closing_balance'])->toBe(-2000.0);

    // GLB1 shows no direct line item for this payment — the money reaches
    // it only via the opening balance, carrying GLA1's -2,000 credit
    // forward. The closing balance is identical to the old (pre-refinement)
    // expectation either way — this only changes which statement's ledger
    // line displays the payment, not how much anyone owes.
    expect($glb1PaymentRow)->toBeNull('the payment predates GLB1 entirely — it must not appear as a line item there')
        ->and($glb1Statement['opening_balance'])->toBe(-2000.0)
        ->and($glb1Statement['closing_balance'])->toBe(33000.0);
});
