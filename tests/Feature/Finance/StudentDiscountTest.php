<?php

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\Intake;
use App\Models\Student;
use App\Models\StudentFeeItem;
use App\Models\Trimester;
use App\Models\User;
use Livewire\Volt\Volt;

function makeDiscountTestFixture(string $suffix): array
{
    $intake = Intake::create(['name' => "Intake {$suffix}", 'starts_at' => '2026-01-01']);
    $year = AcademicYear::create(['name' => "20{$suffix}", 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'active' => true]);
    $trimester = Trimester::create([
        'academic_year_id' => $year->id, 'name' => 'Trimester 1', 'trimester_number' => 1,
        'start_date' => '2026-09-01', 'end_date' => '2026-12-18', 'status' => 'active',
    ]);

    $course = Course::create(['title' => "Discount Course {$suffix}", 'code' => "DSC-{$suffix}", 'number_of_trimesters' => '1', 'allows_continuous_intake' => false]);

    $user = User::factory()->create();
    $student = Student::create([
        'first_name' => 'Discount', 'last_name' => 'Test',
        'email' => $user->email, 'user_id' => $user->id, 'admission_number' => "DISC-{$suffix}",
    ]);

    $enrollment = Enrollment::create([
        'course_id' => $course->id, 'intake_id' => $intake->id, 'student_id' => $student->id,
        'status' => 'active', 'assigned_start_trimester_id' => $trimester->id, 'admission_date' => '2026-09-01',
    ]);

    $progression = EnrollmentProgression::create([
        'student_id' => $student->id, 'enrollment_id' => $enrollment->id, 'trimester_id' => $trimester->id,
        'trimester_sequence' => 1, 'status' => 'active', 'started_at' => '2026-09-01',
    ]);

    return compact('student', 'enrollment', 'progression');
}

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->actingAs(User::factory()->superAdmin()->create());
});

test('applying a discount through the actual Livewire flow (open modal, fill amount, save) persists a fee item', function () {
    ['student' => $student, 'enrollment' => $enrollment, 'progression' => $progression] = makeDiscountTestFixture('1');

    $countBefore = StudentFeeItem::where('student_id', $student->id)->count();

    $component = Volt::test('admin.students.view', ['student_id' => $student->id])
        ->set('selectedEnrollmentId', $enrollment->id)
        ->call('openDiscountModal', $enrollment->id);

    expect($component->get('discount_progression_id'))->toEqual($progression->id);
    $component->assertSeeHtml('value="' . $progression->id . '"');

    $component->set('discount_amount', 1500)
        ->set('discount_description', 'Test discount')
        ->call('saveDiscount');

    expect($component->errors()->all())->toBeEmpty();

    $countAfter = StudentFeeItem::where('student_id', $student->id)->count();
    expect($countAfter)->toBe($countBefore + 1);

    $item = StudentFeeItem::where('student_id', $student->id)->where('description', 'Test discount')->first();
    expect($item)->not->toBeNull()
        ->and((float) $item->amount)->toBe(-1500.0)
        ->and((int) $item->enrollment_progression_id)->toBe($progression->id);
});

test('opening the discount modal for an enrollment that is not the page\'s currently-selected one still targets the right progression', function () {
    // Reproduces the suspected real-world failure: the student view page
    // defaults selectedEnrollmentId to the student's most-recently-created
    // enrollment on mount(). If the admin arrives at (or returns to) the
    // page with a DIFFERENT enrollment still selected than the one they
    // click "Discount" on, discountProgressions (sourced from
    // $selectedEnrollment) could show options for the wrong enrollment
    // while discount_progression_id (sourced from openDiscountModal's own
    // $enrollmentId argument) points at the right one — a mismatch the
    // <select> can't render as selected, exactly like the earlier
    // Fee Item dropdown bug.
    ['student' => $student, 'enrollment' => $enrollmentA, 'progression' => $progressionA] = makeDiscountTestFixture('2a');

    $courseB = Course::create(['title' => 'Discount Course 2b', 'code' => 'DSC-2b', 'number_of_trimesters' => '1', 'allows_continuous_intake' => false]);
    $enrollmentB = Enrollment::create([
        'course_id' => $courseB->id, 'intake_id' => $enrollmentA->intake_id, 'student_id' => $student->id,
        'status' => 'active', 'assigned_start_trimester_id' => $enrollmentA->assigned_start_trimester_id, 'admission_date' => '2026-09-01',
    ]);
    $progressionB = EnrollmentProgression::create([
        'student_id' => $student->id, 'enrollment_id' => $enrollmentB->id, 'trimester_id' => $enrollmentA->assigned_start_trimester_id,
        'trimester_sequence' => 1, 'status' => 'active', 'started_at' => '2026-09-01',
    ]);

    // Page's own selectedEnrollmentId is enrollment B (e.g. left over from
    // mount()'s "latest enrollment" default, or a prior selection) —
    // but the admin clicks "Discount" on enrollment A's card.
    $component = Volt::test('admin.students.view', ['student_id' => $student->id])
        ->set('selectedEnrollmentId', $enrollmentB->id)
        ->call('openDiscountModal', $enrollmentA->id);

    dump('discount_progression_id: ' . $component->get('discount_progression_id'));
    dump('progressionA id: ' . $progressionA->id . ', progressionB id: ' . $progressionB->id);

    expect($component->get('discount_progression_id'))->toEqual($progressionA->id);
    $component->assertSeeHtml('value="' . $progressionA->id . '"');
});
