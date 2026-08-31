<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'active', 'password_changed'])->prefix('student')->group(function () {

    Volt::route('dashboard',  'student.dashboard')->name('student.dashboard');
    Volt::route('profile',    'student.profile')->name('student.profile');

    // Enrollments / finance
    Volt::route('enrollments',          'student.enrollments.index')->name('student.enrollments.index');
    Volt::route('enrollments/view/{id}','student.enrollments.view')->name('student.enrollments.view');
    Volt::route('payments',             'student.payments')->name('student.payments.index');

    // LMS
    Volt::route('modules',                    'student.modules')->name('student.modules.index');
    Volt::route('modules/{intakeModule}',     'student.module-view')->name('student.module.view');
    Volt::route('grades',                     'student.grades')->name('student.grades.index');
    Volt::route('timetable',                  'student.timetable')->name('student.timetable.index');
    Volt::route('announcements',              'student.announcements')->name('student.announcements.index');
    Volt::route('attendance',                 'student.attendance')->name('student.attendance.index');
    Volt::route('assignments',                'student.assignments')->name('student.assignments.index');
});
