<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Group all admin routes under the 'admin' prefix
Route::middleware(['auth'])->prefix('admin')->group(function () {

    // Route to manage dashboard
    Volt::route('dashboard', 'admin.dashboard')->name('admin.dashboard');

    // Route to manage students
    Volt::route('students', 'admin.students.index')->name('students.index');
    Volt::route('students/pending', 'admin.students.pending')->name('students.pending');
    Volt::route('students/enrollments', 'admin.students.enrollments')->name('students.enrollments');
    Volt::route('students/view/{student_id}', 'admin.students.view')->name('students.view');

    // Route to manage courses
    Volt::route('courses', 'admin.courses.index')->name('courses.index');
    Volt::route('courses/view/{course_id}', 'admin.courses.view')->name('courses.view');

    // Route to manage lecturers
    Volt::route('lecturers', 'admin.lecturers.index')->name('lecturers.index');

    // Route to manage financial records
    Volt::route('payments', 'admin.payments.index')->name('payments.index');

    // Route to manage exams
    Volt::route('exams', 'admin.exams.index')->name('exams.index');

    // Route to manage attendance
    Volt::route('attendance', 'admin.attendance.index')->name('attendance.index');

    // Route to manage class_groups
    Volt::route('class_groups', 'admin.class_groups.index')->name('class_groups.index');

    // Route to manage reports resources
    Volt::route('reports', 'admin.reports.index')->name('reports.index');

    // Route to manage payments
    Volt::route('payments', 'admin.payments.index')->name('payments.index');

    // Route to manage intakes
    Volt::route('intakes', 'admin.intakes.index')->name('intakes.index');
    Volt::route('intakes/view/{intake_id}', 'admin.intakes.view')->name('intakes.view');

    //Routes to manage roles
    Volt::route('users', 'admin.roles.users')->name('roles.users');
    Volt::route('roles', 'admin.roles.index')->name('roles.index');
});
