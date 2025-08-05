<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Group all students routes under the 'student' prefix
Route::middleware(['auth'])->prefix('student')->group(function () {

    // Route to manage dashboard
    Volt::route('dashboard', 'student.dashboard')->name('student.dashboard');

    // Route to manage profile
    Volt::route('profile', 'student.profile')->name('student.profile');

    // Route to manage enrollments
    Volt::route('enrollments', 'student.enrollments.index')->name('student.enrollments.index');
    Volt::route('enrollments/view/{id}', 'student.enrollments.view')->name('student.enrollments.view');

    // Route to manage payments
    Volt::route('payments', 'student.payments')->name('student.payments.index');

    // Route to manage attendance
    Volt::route('attendance', 'student.attendance')->name('student.attendance.index');

});
