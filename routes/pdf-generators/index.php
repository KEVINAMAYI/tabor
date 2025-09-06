<?php

// This routes will handle the PDFs download
use App\Http\Controllers\PDFExports\ClassGroupsController;
use App\Http\Controllers\PDFExports\CoursesController;
use App\Http\Controllers\PDFExports\IntakesController;
use App\Http\Controllers\PDFExports\LecturersController;
use App\Http\Controllers\PDFExports\PaymentsController;
use App\Http\Controllers\PDFExports\StudentsController;
use Illuminate\Support\Facades\Route;

Route::get('/enrollments/export/pdf', [StudentsController::class, 'exportEnrollmentsPdf'])->name('enrollments.export.pdf');
Route::get('/pending-enrollments/export/pdf', [StudentsController::class, 'exportPendingEnrollmentsPdf'])->name('pending-enrollments.export.pdf');
Route::get('/students/export/pdf', [StudentsController::class, 'exportStudentsPdf'])->name('students.export.pdf');
Route::get('/lecturers/export/pdf', [LecturersController::class, 'exportLecturersPdf'])->name('lecturers.export.pdf');
Route::get('/payments/export/pdf', [PaymentsController::class, 'exportPaymentsPdf'])->name('payments.export.pdf');
Route::get('/courses/export/pdf', [CoursesController::class, 'exportCoursesPdf'])->name('courses.export.pdf');
Route::get('/intakes/export/pdf', [IntakesController::class, 'exportIntakesPdf'])->name('intakes.export.pdf');
Route::get('/class-groups/export/pdf', [ClassGroupsController::class, 'exportClassGroupsPdf'])->name('class-groups.export.pdf');
