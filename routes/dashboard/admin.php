<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Group all admin routes under the 'admin' prefix
Route::middleware(['auth'])->prefix('admin')->group(function () {

    // Route to manage students
    Volt::route('students', 'students.index')->name('students.index');
    Volt::route('students/view/{student_id}', 'students.view')->name('students.view');

    // Route to manage courses
    Volt::route('courses', 'courses.index')->name('courses.index');
    Volt::route('courses/view/{course_id}', 'courses.view')->name('courses.view');

    // Route to manage lecturers
    Volt::route('lecturers', 'lecturers.index')->name('lecturers.index');

    // Route to manage financial records
    Volt::route('payments', 'payments.index')->name('payments.index');

    // Route to manage exams
    Volt::route('exams', 'exams.index')->name('exams.index');

    // Route to manage attendance
    Volt::route('attendance', 'attendance.index')->name('attendance.index');

    // Route to manage classes
    Volt::route('classes', 'classes.index')->name('classes.index');

    // Route to manage reports resources
    Volt::route('reports', 'reports.index')->name('reports.index');

    // Route to manage payments
    Volt::route('payments', 'payments.index')->name('payments.index');

    // Route to manage intakes
    Volt::route('intakes', 'intakes.index')->name('intakes.index');
});
