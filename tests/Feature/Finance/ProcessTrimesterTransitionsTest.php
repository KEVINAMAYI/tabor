<?php

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseFeePlan;
use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\FeeCategory;
use App\Models\FeeDefinition;
use App\Models\Intake;
use App\Models\Student;
use App\Models\StudentFeeItem;
use App\Models\Trimester;
use App\Models\User;
use App\Services\AcademicCalendarService;
use App\Services\EnrollmentProgressionService;
use Illuminate\Support\Facades\Artisan;

function makeRolloverEnrollment(string $suffix, string $status = 'active'): array
{
    $intake = Intake::create(['name' => "Intake {$suffix}", 'starts_at' => '2026-01-01']);

    $course = Course::create([
        'title' => "Test Course {$suffix}",
        'code' => "TC-{$suffix}",
        'number_of_trimesters' => '2',
        'allows_continuous_intake' => false,
    ]);

    $category = FeeCategory::create(['code' => "tuition-{$suffix}", 'name' => 'Tuition']);
    $feeDefinition = FeeDefinition::create([
        'fee_category_id' => $category->id,
        'name' => 'Tuition',
        'scope' => 'student',
        'default_amount' => 1000,
        'active' => true,
    ]);
    CourseFeePlan::create([
        'course_id' => $course->id,
        'fee_definition_id' => $feeDefinition->id,
        'charge_timing' => 'every_trimester',
        'amount' => 1000,
        'mandatory' => true,
    ]);

    $year = AcademicYear::create(['name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'active' => true]);
    $t1 = Trimester::create([
        'academic_year_id' => $year->id,
        'name' => 'Trimester 1',
        'trimester_number' => 1,
        'start_date' => '2026-01-01',
        'end_date' => '2026-04-30',
        'status' => 'closed',
    ]);

    $user = User::factory()->create();
    $student = Student::create([
        'first_name' => 'Test',
        'last_name' => 'Student',
        'email' => $user->email,
        'user_id' => $user->id,
    ]);

    $enrollment = Enrollment::create([
        'course_id' => $course->id,
        'intake_id' => $intake->id,
        'student_id' => $student->id,
        'status' => $status,
        'assigned_start_trimester_id' => $t1->id,
        'admission_date' => '2026-01-01',
    ]);

    $progression = app(EnrollmentProgressionService::class)->createFirstProgression($enrollment);
    app(\App\Services\FeeGenerationService::class)->generateChargesForProgression($progression);
    $progression->update(['status' => 'completed', 'completed_at' => '2026-04-30']);

    return compact('course', 'student', 'enrollment', 'progression', 't1');
}

test('ensureNextTrimesterWithinLeadTime creates the next trimester only within the lead window', function () {
    $year = AcademicYear::create(['name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'active' => true]);
    $t1 = Trimester::create([
        'academic_year_id' => $year->id,
        'name' => 'Trimester 1',
        'trimester_number' => 1,
        'start_date' => '2026-01-01',
        'end_date' => '2026-04-30',
        'status' => 'active',
    ]);

    $service = app(AcademicCalendarService::class);

    // 30 days before end_date — outside the 14-day lead window.
    $tooEarly = $service->ensureNextTrimesterWithinLeadTime($t1, 14, now()->parse('2026-04-01'));
    expect($tooEarly)->toBeNull()
        ->and(Trimester::count())->toBe(1);

    // 10 days before end_date — within the window.
    $created = $service->ensureNextTrimesterWithinLeadTime($t1, 14, now()->parse('2026-04-20'));
    expect($created)->not->toBeNull()
        ->and($created->trimester_number)->toBe(2)
        ->and($created->status)->toBe('upcoming');
});

test('the next trimester derives its dates from the reference trimester duration, not hardcoded quarters', function () {
    $year = AcademicYear::create(['name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'active' => true]);

    // A deliberately non-quarter-aligned custom trimester: 2 months long.
    $t1 = Trimester::create([
        'academic_year_id' => $year->id,
        'name' => 'Trimester 1',
        'trimester_number' => 1,
        'start_date' => '2026-02-10',
        'end_date' => '2026-04-10',
        'status' => 'active',
    ]);

    $created = app(AcademicCalendarService::class)
        ->ensureNextTrimesterWithinLeadTime($t1, 14, now()->parse('2026-04-05'));

    expect($created->start_date->toDateString())->toBe('2026-04-11')
        ->and($created->end_date->toDateString())->toBe('2026-06-09'); // same 59-day duration as T1 (Feb 10 - Apr 10)
});

test('a course_completed enrollment is never rolled into a new progression, even with trimester capacity remaining', function () {
    ['t1' => $t1, 'enrollment' => $enrollment] = makeRolloverEnrollment('cc', status: 'course_completed');

    Trimester::create([
        'academic_year_id' => $t1->academic_year_id,
        'name' => 'Trimester 2',
        'trimester_number' => 2,
        'start_date' => '2026-05-01',
        'end_date' => '2026-08-31',
        'status' => 'active',
    ]);

    Artisan::call('finance:process-trimester-transitions', ['--date' => '2026-05-10']);

    expect(EnrollmentProgression::where('enrollment_id', $enrollment->id)->count())->toBe(1);
});

test('an active enrollment correctly rolls into the next trimester with new charges', function () {
    ['t1' => $t1, 'enrollment' => $enrollment, 'student' => $student] = makeRolloverEnrollment('active', status: 'active');

    Trimester::create([
        'academic_year_id' => $t1->academic_year_id,
        'name' => 'Trimester 2',
        'trimester_number' => 2,
        'start_date' => '2026-05-01',
        'end_date' => '2026-08-31',
        'status' => 'active',
    ]);

    Artisan::call('finance:process-trimester-transitions', ['--date' => '2026-05-10']);

    $progressions = EnrollmentProgression::where('enrollment_id', $enrollment->id)->orderBy('trimester_sequence')->get();

    expect($progressions)->toHaveCount(2)
        ->and($progressions->last()->trimester_sequence)->toBe(2)
        ->and($progressions->last()->status)->toBe('active');

    expect(StudentFeeItem::where('student_id', $student->id)->count())->toBe(2);
});

test('an unpaid balance from a prior trimester is still counted after rolling into the next one', function () {
    ['t1' => $t1, 'enrollment' => $enrollment, 'student' => $student] = makeRolloverEnrollment('carry', status: 'active');

    // T1's charge (1000) is left unpaid on purpose.
    $t1Balance = StudentFeeItem::where('student_id', $student->id)->sum('balance');
    expect((float) $t1Balance)->toBe(1000.0);

    Trimester::create([
        'academic_year_id' => $t1->academic_year_id,
        'name' => 'Trimester 2',
        'trimester_number' => 2,
        'start_date' => '2026-05-01',
        'end_date' => '2026-08-31',
        'status' => 'active',
    ]);

    Artisan::call('finance:process-trimester-transitions', ['--date' => '2026-05-10']);

    // T1's unpaid 1000 + T2's new 1000 charge = 2000 total outstanding.
    // Nothing archives or zeroes out the old trimester's StudentFeeItem —
    // it simply persists alongside the new one.
    $totalBalance = StudentFeeItem::where('student_id', $student->id)->sum('balance');
    expect((float) $totalBalance)->toBe(2000.0);
});
