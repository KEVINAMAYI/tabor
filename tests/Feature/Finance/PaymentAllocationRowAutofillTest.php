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

test('opening the edit modal and saving without changing anything preserves the existing allocation', function () {
    // Reproduces a real production bug: allocationFeeItems (the Fee Item
    // dropdown's option list) only ever included fee items with
    // balance > 0 and status in [pending, partial] — but editing an
    // existing, already-allocated payment means its own fee item is
    // exactly the one now sitting at balance = 0 / status = paid. With no
    // matching <option>, the dropdown rendered blank despite the row's
    // real student_fee_item_id being correctly set. Saving in that state
    // treated every row as empty, reversed the real allocation, and fell
    // through to a blind FIFO re-allocation — silently moving the
    // student's money onto a completely different fee item while still
    // showing "Payment updated successfully."
    $student = makeAllocationTestStudent('7');
    $enrollment = makeAllocationTestEnrollment($student, '7');

    // The item the payment actually covers.
    $targetItem = makeAllocationTestFeeItem($student, $enrollment, '7a', 5000);

    // A second, older, cheaper outstanding item that a blind FIFO
    // re-allocation would grab first instead — makes the bug's failure
    // mode unambiguous rather than coincidentally reproducing the same
    // result.
    $decoyCategory = FeeCategory::create(['code' => 'alloc-7b', 'name' => 'Exam']);
    $decoyDefinition = FeeDefinition::create([
        'fee_category_id' => $decoyCategory->id,
        'name' => 'Exam Fee',
        'scope' => 'student',
        'default_amount' => 1000,
        'active' => true,
    ]);
    $decoyItem = StudentFeeItem::create([
        'student_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'fee_definition_id' => $decoyDefinition->id,
        'description' => 'Exam Fee',
        'amount' => 1000,
        'balance' => 1000,
        'charge_date' => '2025-01-01',
        'status' => 'pending',
    ]);

    $component = Volt::test('admin.payments.index')
        ->call('addPaymentAllocationRow')
        ->set('paymentAllocationRows.0.student_fee_item_id', $targetItem->id)
        ->set('paymentAllocationRows.0.amount', 5000)
        ->set('amount', 5000)
        ->set('payment_method', 'mpesa')
        ->set('paid_at', '2026-01-10')
        ->call('addPayment');

    $payment = \App\Models\Payment::latest('id')->first();
    expect($targetItem->fresh()->balance)->toEqual('0.00');

    // Simulates opening "Edit" on this payment and immediately clicking
    // Save, without touching any field.
    $component->call('editPayment', $payment->id)
        ->call('updatePayment');

    $payment->refresh();
    $allocations = $payment->allocations()->get();

    expect($allocations)->toHaveCount(1)
        ->and((int) $allocations->first()->student_fee_item_id)->toBe($targetItem->id)
        ->and((float) $allocations->first()->amount_allocated)->toBe(5000.0)
        ->and($targetItem->fresh()->balance)->toEqual('0.00')
        ->and($decoyItem->fresh()->balance)->toEqual('1000.00');
});

test('editing a payment to move its allocation onto a genuinely different fee item saves that change', function () {
    // The exact real-world action this whole bug chain was blocking: an
    // admin opens an existing payment that's wrongly allocated to fee item
    // A, and re-points it at the correct fee item B instead.
    $student = makeAllocationTestStudent('8');
    $enrollment = makeAllocationTestEnrollment($student, '8');

    $wrongItem = makeAllocationTestFeeItem($student, $enrollment, '8a', 5000);

    $correctCategory = FeeCategory::create(['code' => 'alloc-8b', 'name' => 'German']);
    $correctDefinition = FeeDefinition::create([
        'fee_category_id' => $correctCategory->id,
        'name' => 'German Tuition Fee',
        'scope' => 'student',
        'default_amount' => 5000,
        'active' => true,
    ]);
    $correctItem = StudentFeeItem::create([
        'student_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'fee_definition_id' => $correctDefinition->id,
        'description' => 'German Tuition Fee',
        'amount' => 5000,
        'balance' => 5000,
        'charge_date' => '2026-01-01',
        'status' => 'pending',
    ]);

    $component = Volt::test('admin.payments.index')
        ->call('addPaymentAllocationRow')
        ->set('paymentAllocationRows.0.student_fee_item_id', $wrongItem->id)
        ->set('paymentAllocationRows.0.amount', 5000)
        ->set('amount', 5000)
        ->set('payment_method', 'mpesa')
        ->set('paid_at', '2026-01-10')
        ->call('addPayment');

    $payment = \App\Models\Payment::latest('id')->first();
    expect($wrongItem->fresh()->balance)->toEqual('0.00');

    // Open edit, then actually change the row's fee item to the correct one.
    $component->call('editPayment', $payment->id)
        ->set('paymentAllocationRows.0.student_fee_item_id', $correctItem->id)
        ->set('paymentAllocationRows.0.amount', 5000)
        ->call('updatePayment');

    $payment->refresh();
    $allocations = $payment->allocations()->get();

    expect($allocations)->toHaveCount(1)
        ->and((int) $allocations->first()->student_fee_item_id)->toBe($correctItem->id)
        ->and((float) $allocations->first()->amount_allocated)->toBe(5000.0)
        ->and($correctItem->fresh()->balance)->toEqual('0.00')
        ->and($wrongItem->fresh()->balance)->toEqual('5000.00');
});
