<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FeeCategory;
use App\Models\FeeDefinition;
use App\Models\Intake;
use App\Models\Student;
use App\Models\StudentFeeItem;
use App\Models\User;
use Livewire\Volt\Volt;

function makeAllocationTestStudent(string $suffix): Student
{
    $user = User::factory()->create();

    return Student::create([
        'first_name' => 'Test',
        'last_name' => 'Student',
        'email' => $user->email,
        'user_id' => $user->id,
        'admission_number' => "ALLOC-{$suffix}",
    ]);
}

function makeAllocationTestEnrollment(Student $student, string $suffix): Enrollment
{
    $intake = Intake::create(['name' => "Intake {$suffix}", 'starts_at' => '2026-01-01']);
    $course = Course::create(['title' => "Alloc Course {$suffix}", 'code' => "ALC-{$suffix}"]);

    return Enrollment::create([
        'course_id' => $course->id,
        'intake_id' => $intake->id,
        'student_id' => $student->id,
        'status' => 'active',
    ]);
}

function makeAllocationTestFeeItem(Student $student, Enrollment $enrollment, string $suffix, float $amount = 5000): StudentFeeItem
{
    $category = FeeCategory::create(['code' => "alloc-{$suffix}", 'name' => 'Tuition']);
    $feeDefinition = FeeDefinition::create([
        'fee_category_id' => $category->id,
        'name' => 'Tuition Fee',
        'scope' => 'student',
        'default_amount' => $amount,
        'active' => true,
    ]);

    return StudentFeeItem::create([
        'student_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'fee_definition_id' => $feeDefinition->id,
        'description' => 'Tuition Fee',
        'amount' => $amount,
        'balance' => $amount,
        'charge_date' => '2026-01-05',
        'status' => 'pending',
    ]);
}

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->actingAs(User::factory()->superAdmin()->create());
});

test('adding the first allocation row still defaults from the selected enrollment', function () {
    $student = makeAllocationTestStudent('1');
    $enrollment = makeAllocationTestEnrollment($student, '1');

    $component = Volt::test('admin.payments.index')
        ->set('enrollment_id', $enrollment->id)
        ->call('addPaymentAllocationRow');

    expect($component->get('paymentAllocationRows.0.student_id'))->toEqual($student->id)
        ->and($component->get('paymentAllocationRows.0.enrollment_id'))->toEqual($enrollment->id);
});

test('changing a row\'s student clears its enrollment and fee item — no auto-selection', function () {
    $student = makeAllocationTestStudent('2');
    $enrollment = makeAllocationTestEnrollment($student, '2');
    $feeItem = makeAllocationTestFeeItem($student, $enrollment, '2');

    $component = Volt::test('admin.payments.index')
        ->call('addPaymentAllocationRow')
        ->set('paymentAllocationRows.0.enrollment_id', $enrollment->id)
        ->set('paymentAllocationRows.0.student_fee_item_id', $feeItem->id)
        ->set('paymentAllocationRows.0.student_id', $student->id);

    expect($component->get('paymentAllocationRows.0.enrollment_id'))->toBeEmpty()
        ->and($component->get('paymentAllocationRows.0.student_fee_item_id'))->toBeEmpty();
});

test('selecting a fee item sets the student, enrollment, and amount from that item', function () {
    $student = makeAllocationTestStudent('3');
    $enrollment = makeAllocationTestEnrollment($student, '3');
    $feeItem = makeAllocationTestFeeItem($student, $enrollment, '3', 4200);

    $component = Volt::test('admin.payments.index')
        ->call('addPaymentAllocationRow')
        ->set('paymentAllocationRows.0.student_fee_item_id', $feeItem->id);

    expect($component->get('paymentAllocationRows.0.student_id'))->toEqual($student->id)
        ->and($component->get('paymentAllocationRows.0.enrollment_id'))->toEqual($enrollment->id)
        ->and((float) $component->get('paymentAllocationRows.0.amount'))->toBe(4200.0);
});

test('saving a payment links it to the student/enrollment picked in an allocation row, even when the top search field was never filled', function () {
    // Reproduces a real production bug: addPayment()/updatePayment() only
    // ever set Payment.student_id/enrollment_id from the top-level "Default
    // Student / Enrollment" search field. Leaving that blank — even though
    // a student was correctly picked directly in the allocation row below
    // it — saved the payment with student_id/enrollment_id = null, silently
    // unlinking it from the student despite its PaymentAllocation row
    // correctly pointing at the right fee item.
    $student = makeAllocationTestStudent('6');
    $enrollment = makeAllocationTestEnrollment($student, '6');
    $feeItem = makeAllocationTestFeeItem($student, $enrollment, '6', 5000);

    $component = Volt::test('admin.payments.index')
        ->call('addPaymentAllocationRow')
        ->set('paymentAllocationRows.0.student_fee_item_id', $feeItem->id)
        ->set('paymentAllocationRows.0.amount', 5000)
        ->set('amount', 5000)
        ->set('payment_method', 'mpesa')
        ->set('paid_at', '2026-01-10')
        // Deliberately never touching `enrollment_id` (the top search field).
        ->call('addPayment');

    $payment = \App\Models\Payment::latest('id')->first();

    expect($payment)->not->toBeNull()
        ->and($payment->student_id)->toEqual($student->id)
        ->and($payment->enrollment_id)->toEqual($enrollment->id);
});
