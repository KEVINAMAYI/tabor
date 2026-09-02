<?php

use App\Models\FeeCategory;
use App\Models\FeeDefinition;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFeeItem;
use App\Models\User;
use App\Services\Finance\FinanceReconciliationService;

function makeMpesaTestStudent(string $admissionNumber, float $outstandingBalance): Student
{
    $user = User::factory()->create();

    $student = Student::create([
        'first_name' => 'Priscillah',
        'last_name' => 'Kang\'ethe',
        'email' => $user->email,
        'user_id' => $user->id,
        'admission_number' => $admissionNumber,
    ]);

    $category = FeeCategory::create(['code' => 'tuition-' . $admissionNumber, 'name' => 'Tuition']);
    $feeDefinition = FeeDefinition::create([
        'fee_category_id' => $category->id,
        'name' => 'Tuition',
        'scope' => 'student',
        'default_amount' => $outstandingBalance,
        'active' => true,
    ]);

    StudentFeeItem::create([
        'student_id' => $student->id,
        'fee_definition_id' => $feeDefinition->id,
        'description' => 'Tuition Fee',
        'amount' => $outstandingBalance,
        'balance' => $outstandingBalance,
        'charge_date' => now()->toDateString(),
        'status' => 'pending',
    ]);

    return $student;
}

test('a C2B payment matched to a student but no course/enrollment is allocated, not left orphaned', function () {
    $student = makeMpesaTestStudent('00380', 45000);

    $response = $this->postJson('/api/finance/confirmation', [
        'TransID' => 'UGHAMBM8NS',
        'TransAmount' => '15000',
        // Deliberately unmatchable course code so $course stays null and the
        // fallback branch runs, with only $student resolving.
        'BillRefNumber' => '00380/NOPE',
        'MSISDN' => '254700000000',
        'FirstName' => 'Millicent',
    ]);

    $response->assertOk();

    $payment = Payment::where('receipt_no', 'UGHAMBM8NS')->orWhere('reference', '00380/NOPE')->first();

    expect($payment)->not->toBeNull()
        ->and($payment->student_id)->toBe($student->id)
        ->and((float) $payment->unallocated_balance)->toBe(0.0)
        ->and($payment->allocations()->count())->toBeGreaterThan(0);

    expect((float) StudentFeeItem::where('student_id', $student->id)->sum('balance'))->toBe(30000.0);
});

test('a C2B payment matched to no student at all is still saved unallocated for manual review', function () {
    $response = $this->postJson('/api/finance/confirmation', [
        'TransID' => 'UGDAMB5QVD',
        'TransAmount' => '10000',
        'BillRefNumber' => 'NOTFOUND/ALSO-NOPE',
        'MSISDN' => '254700000000',
        'FirstName' => 'Millicent',
    ]);

    $response->assertOk();

    $payment = Payment::where('transaction_id', 'UGDAMB5QVD')->first();

    expect($payment)->not->toBeNull()
        ->and($payment->student_id)->toBeNull()
        ->and((float) $payment->unallocated_balance)->toBe(10000.0);
});

test('FinanceReconciliationService now flags a null-enrollment unallocated payment with real outstanding balance', function () {
    $student = makeMpesaTestStudent('00381', 45000);

    // Simulate what used to happen before the MpesaApi fix: a payment with
    // student_id set, enrollment_id null, and zero allocations.
    $payment = Payment::create([
        'student_id' => $student->id,
        'enrollment_id' => null,
        'payment_date' => now()->toDateString(),
        'amount' => 15000,
        'unallocated_balance' => 15000,
        'method' => 'mpesa',
        'status' => 'completed',
    ]);

    $mismatches = app(FinanceReconciliationService::class)->detectMismatches($student->id);

    expect($mismatches->firstWhere('type', 'Payment.money_not_applied'))->not->toBeNull();
});
