<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\StudentStatementController;
use App\Http\Controllers\Admin\BlogContentImageUploadController;

// Group all admin routes under the 'admin' prefix
Route::middleware(['auth', 'active', 'password_changed'])->prefix('admin')->group(function () {

    // Route to manage dashboard
    Volt::route('dashboard', 'admin.dashboard')->name('admin.dashboard');

    Route::get('/portal', function () {
        if (auth()->check()) {
            auth()->logout();
        }

        return redirect()->route('login');
    })->name('portal.redirect');

    // Route to manage students
    Volt::route('students', 'admin.students.index')->name('students.index');
    Volt::route('students/pending', 'admin.students.pending')->name('students.pending');
    Volt::route('students/course-applications', 'admin.students.course-applications')->name('students.course-applications');
    Volt::route('students/enrollments', 'admin.students.enrollments')->name('students.enrollments');
    Volt::route('students/enrollment-details/{enrollment_id}', 'admin.students.enrollment-details')->name('students.enrollment-details');
    Volt::route('students/view/{student_id}', 'admin.students.view')->name('students.view');
    Route::get('/statements/enrollment/{enrollment}', [StatementController::class, 'show'])
        ->name('statements.show');

    // Route to manage courses
    Volt::route('courses', 'admin.courses.index')->name('courses.index');
    Volt::route('courses/view/{course_id}', 'admin.courses.view')->name('courses.view');

    // Route to manage lecturers
    Volt::route('lecturers', 'admin.lecturers.index')->name('lecturers.index');

    // Route to manage financial records
    Volt::route('payments', 'admin.payments.index')->name('payments.index');

    Route::get('/payments/{payment}/receipt', [PaymentReceiptController::class, 'show'])
        ->name('payments.receipt');

    // Route to manage exams
    Volt::route('exams', 'admin.exams.index')->name('exams.index');

    // Route to manage attendance
    Volt::route('attendance', 'admin.attendance.index')->name('attendance.index');

    // LMS admin management
    Volt::route('lms/modules', 'admin.lms.modules')->name('admin.lms.modules');

    // Route to manage class_groups
    Volt::route('class_groups', 'admin.class_groups.index')->name('class_groups.index');

    // Route to manage reports resources
    Volt::route('reports', 'admin.reports.index')->name('reports.index');
    Volt::route('reports/arrears', 'admin.reports.arrears')->name('reports.arrears');
    Volt::route('reports/revenue', 'admin.reports.revenue')->name('reports.revenue');
    Volt::route('reports/course-applications', 'admin.reports.course-application-funnel')->name('reports.applications-funnel');
    Volt::route('reports/enrollment-retention', 'admin.reports.enrollment-retention')->name('reports.retention');
    Volt::route('reports/reconciliation', 'admin.reports.reconciliation-health')->name('reports.reconciliation');

    // Route to manage payments
    // Volt::route('payments', 'admin.payments.index')->name('payments.index');

    // Route to manage intakes
    Volt::route('intakes', 'admin.intakes.index')->name('intakes.index');
    Volt::route('intakes/view/{intake_id}', 'admin.intakes.view')->name('intakes.view');

    //Routes to manage roles
    Volt::route('users', 'admin.roles.users')->name('roles.users');
    Volt::route('roles', 'admin.roles.index')->name('roles.index');

    //Routes to manage teams
    Volt::route('team', 'admin.team.index')->name('team.index');

    // Route to manage settings
    Volt::route('settings/academic-calendar', 'admin.settings.academic-calendar.index')->name('settings.academic-calendar');
    Volt::route('settings/fee-definitions', 'admin.settings.fee-definitions.index')->name('settings.fee-definitions');
    Volt::route('settings/course-fee-plans', 'admin.settings.course-fee-plans')->name('settings.course-fee-plans');
    Volt::route('settings/student-fee-items', 'admin.settings.student-fee-items')->name('settings.student-fee-items');

    // Accounting / General Ledger (Phase 1)
    Volt::route('accounting/chart-of-accounts', 'admin.accounting.chart-of-accounts')->name('accounting.chart-of-accounts');
    Volt::route('accounting/financial-years', 'admin.accounting.financial-years')->name('accounting.financial-years');
    Volt::route('accounting/journal-entries', 'admin.accounting.journal-entries.index')->name('accounting.journal-entries.index');
    Volt::route('accounting/journal-entries/create', 'admin.accounting.journal-entries.create')->name('accounting.journal-entries.create');
    Volt::route('accounting/trial-balance', 'admin.accounting.trial-balance')->name('accounting.trial-balance');

    // Petty Cash & Imprest (Phase 2)
    Volt::route('accounting/petty-cash/vote-heads', 'admin.accounting.petty-cash.vote-heads')->name('accounting.petty-cash.vote-heads');
    Volt::route('accounting/petty-cash/custodians', 'admin.accounting.petty-cash.custodians')->name('accounting.petty-cash.custodians');
    Volt::route('accounting/petty-cash/expenses', 'admin.accounting.petty-cash.expenses')->name('accounting.petty-cash.expenses');

    // Budget Management (Phase 3)
    Volt::route('accounting/budget', 'admin.accounting.budget.manage')->name('accounting.budget.manage');
    Volt::route('accounting/budget/report', 'admin.accounting.budget.report')->name('accounting.budget.report');

    // Procurement & Supplier Payments (Phase 4)
    Volt::route('accounting/procurement/suppliers', 'admin.accounting.procurement.suppliers')->name('accounting.procurement.suppliers');
    Volt::route('accounting/procurement/requisitions', 'admin.accounting.procurement.requisitions')->name('accounting.procurement.requisitions');
    Volt::route('accounting/procurement/purchase-orders', 'admin.accounting.procurement.purchase-orders')->name('accounting.procurement.purchase-orders');
    Volt::route('accounting/procurement/goods-received', 'admin.accounting.procurement.goods-received')->name('accounting.procurement.goods-received');
    Volt::route('accounting/procurement/supplier-invoices', 'admin.accounting.procurement.supplier-invoices')->name('accounting.procurement.supplier-invoices');
    Volt::route('accounting/procurement/supplier-payments', 'admin.accounting.procurement.supplier-payments')->name('accounting.procurement.supplier-payments');

    Volt::route('blog/categories', 'admin.blog.categories')
        ->name('admin.blog.categories');
    Volt::route('blog/posts', 'admin.blog.posts')->name('admin.blog.posts');
    Route::post('blog/content-images/upload', BlogContentImageUploadController::class)
        ->name('admin.blog.content-images.upload');

    Route::get('/students/{student}/statements/{progression}', [StudentStatementController::class, 'show'])
        ->name('students.statement');


});
