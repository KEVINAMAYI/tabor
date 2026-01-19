<?php

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Intake;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Notifications\EnrollmentStatus;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use App\Models\ClassGroup;
use App\Services\EnrollmentService;
use App\Models\EnrollmentTrimester;
use Illuminate\Support\Facades\Log;

use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $student;
    public $amount, $payment_method, $payment_reason, $enrollment_id, $reference, $paid_at, $payer;
    public $status = 'pending';
    public $activeTab = 'pills-courses';
    public $studentId;
    public $selectedCourseId;
    public $selectedIntakeId;
    public $courses;
    public $intakes;
    public $class_group_id;
    public $classGroups = [];
    public $availableStatuses = ['pending', 'approved', 'rejected', 'completed', 'withdrawn'];
    public $enrollmentId;
    public $enrollmentStatus;
    public $enrollmentPayments = [];
    public $enrollmentTrimesters = [];
    public $course_id = null;

    public function rules()
    {
        return [
            'selectedCourseId' => 'required|exists:courses,id',
        ];
    }

    public function paymentRules()
    {
        return [
            'enrollment_id' => 'required|exists:enrollments,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'payment_reason' => 'required|string|max:100',
            'reference' => 'nullable|string|max:100',
            'paid_at' => 'required|date',
            'payer' => 'nullable|string|max:100',
        ];
    }

    public function mount($student_id)
    {
        $this->student = Student::with(['enrollments.course.modules', 'enrollments.payments', 'enrollments.intake'])->findOrFail($student_id);

        $this->studentId = $student_id;
        $this->courses = Course::all();
        $this->intakes = Intake::all();
        $this->classGroups = ClassGroup::with('intake')->latest()->get();
    }

    public function enroll()
    {
        $this->validate();

        $enrollment = Enrollment::where('student_id', $this->studentId)->where('course_id', $this->selectedCourseId)->first();

        if ($enrollment) {
            LivewireAlert::text('Enrollment already exists.!')->error()->toast()->position('top-end')->show();
        } else {
            // Using EnrollmentService to handle enrollment creation
            $enrollment = EnrollmentService::enrollStudent($this->studentId, $this->selectedCourseId, $this->selectedIntakeId, $this->status);

            LivewireAlert::text('Student enrolled successfully.!')->success()->toast()->position('top-end')->show();
            // Ensure the course relationship is loaded
            $enrollment->load('course');
            $this->dispatch('hide-enrollment-modal');

            // Create enrollment trimesters
            EnrollmentService::createEnrollmentTrimesters($enrollment);

            // Send email notification
            $user = $this->student->user ?? null;

            if ($user) {
                $notification = new EnrollmentStatus($this->status, $enrollment->course->title ?? 'Unknown Program');

                $user->notify($notification);
            }
        }
    }

    public function openEditModal($id)
    {
        $this->enrollmentId = $id;
        $model = Enrollment::findOrFail($id);
        // $this->course_id = $model->course_id;
        $this->enrollmentStatus = $model->status;
        $this->dispatch('show-enrollment-status-modal');
    }

    public function updateEnrollment()
    {
        try {
            DB::beginTransaction();
            $enrollment = Enrollment::withCount('trimesters')->findOrFail($this->enrollmentId);

            //Update enrollment status
            $enrollment->update([
                'status' => $this->enrollmentStatus,
            ]);

            //Activate user only when approved
            if ($this->enrollmentStatus === 'approved') {
                if ($enrollment->student && $enrollment->student->user) {
                    $enrollment->student->user->update([
                        'active' => true,
                    ]);
                }

                //Create enrollment trimesters ONLY if none exist
                if ($enrollment->trimesters_count === 0) {
                    EnrollmentService::createEnrollmentTrimesters($enrollment);
                }
            }
            DB::commit();

            // Notify user (OUTSIDE transaction)
            $user = optional($enrollment->student)->user;

            if ($user) {
                $user->notify(new EnrollmentStatus($this->enrollmentStatus, $enrollment->course->title ?? 'Unknown Program'));
            }

            // 5️⃣ UI cleanup
            $this->dispatch('hide-enrollment-status-modal');

            LivewireAlert::text('Enrollment status updated successfully!')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Enrollment update failed', [
                'enrollment_id' => $this->enrollmentId,
                'error' => $e->getMessage(),
            ]);

            LivewireAlert::text('Failed to update enrollment status.')->error()->toast()->position('top-end')->show();
        }
    }

    public function deleteEnrollment($id)
    {
        DB::beginTransaction();
        try {
            $enrollment = Enrollment::with('trimesters')->findOrFail($id);
            // $enrollment->payments->each->delete();
            $enrollment->trimesters->each->delete();
            $enrollment->delete();

            DB::commit();

            LivewireAlert::text('Enrollment deleted successfully!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete enrollment: ' . $e->getMessage());

            LivewireAlert::text('Failed to delete enrollment!')->error()->toast()->position('top-end')->show();
        }
    }

    public function addPayment()
    {
        // Validate input
        $this->validate($this->paymentRules());

        DB::beginTransaction();
        try {
            $enrollment = Enrollment::findOrFail($this->enrollment_id);

            $enrollment->payments()->create([
                'amount' => $this->amount,
                'payment_method' => $this->payment_method,
                'payment_reason' => $this->payment_reason,
                'reference' => $this->reference,
                'paid_at' => $this->paid_at,
                'payer' => $this->payer,
            ]);

            DB::commit();

            LivewireAlert::text('Payment added successfully!')->success()->toast()->position('top-end')->show();

            // ✅ Reset only after success
            $this->reset(['amount', 'payment_method', 'enrollment_id', 'reference', 'paid_at', 'payer']);

            // ✅ Reset only after success
            $this->activeTab = 'pills-payments';
            // ✅ Close modal only after successful save
            $this->dispatch('hide-payment-modal');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add payment: ' . $e->getMessage());

            LivewireAlert::text('Failed to add payment!')->error()->toast()->position('top-end')->show();
        }
    }

    public function showEnrollmentPayments($enrollmentId)
    {
        $this->enrollmentPayments = Payment::with('enrollment.course')->where('enrollment_id', $enrollmentId)->get();

        $this->dispatch('show-enrollment-payments-modal');
    }
}; ?>
@push('styles')
    <style>
        /* Make tab buttons look like Apply Now when active */
        .custom-course-tabs .nav-link {
            color: black !important;
            background-color: transparent !important;
            border: 1px solid transparent !important;
            transition: all 0.2s ease !important;
            border-radius: 8px !important;
            padding: 8px 8px !important;
        }

        .custom-course-tabs .nav-link:hover {
            background-color: #f5f5f5 !important;
            color: #000 !important;
        }

        .custom-course-tabs .nav-link.active {
            background-color: #f79020 !important;
            /* Match Apply Now */
            color: #fff !important;
            font-weight: 600 !important;
            border-color: #f79020 !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
        }

        /* Optional spacing between tab items */
        .custom-course-tabs .nav-item {
            margin-right: 10px !important;
        }

        /* Add spacing and alignment to icon */
        .btn-outline-primary iconify-icon {
            vertical-align: middle !important;
            margin-right: 8px !important;
            font-size: 1.1rem !important;
        }

        .btn-print {
            background-color: #0e334e;
            color: #fff;
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-print:hover {
            background-color: #f69121;
            color: #fff;
        }


        /* Hidden printable receipt */
        .receipt-print {
            visibility: hidden;
            position: absolute;
            top: -9999px;
            left: -9999px;
            width: 100%;
        }

        /* Add top border to the last row */
        .styled-payment-table tbody tr:last-child td {
            border-top: 1px solid #f0f0f0;
        }

        /* Print styling */
        @media print {
            @page {
                size: auto;
                margin: 0;
                /* no extra margin */
            }

            html,
            body {
                margin: 0 !important;
                padding: 0 !important;
                height: auto !important;
            }

            body * {
                visibility: hidden !important;
            }

            .receipt-print,
            .receipt-print * {
                visibility: visible !important;
            }

            .receipt-print {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: auto !important;
                margin: 0 !important;
                padding: 20mm !important;
                /* acts like page margin */
                background: white !important;
                box-sizing: border-box !important;
                page-break-inside: avoid;
                page-break-after: always;
            }
        }
    </style>
@endpush

<div class="col-12">
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">
                            {{ 'TTI/' . $this->student->admission_number . '/' . $this->student->created_at->format('Y') }}
                        </h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">
                                    <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                        Student Profile
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-body p-0">
                {{-- <img src="../assets/images/backgrounds/profilebg.jpg" alt="matdash-img" class="img-fluid"> --}}
                <div class="row align-items-center">
                    <div class="mt-5 order-lg-2 order-1">
                        <div class="mt-n5">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="d-flex align-items-center justify-content-center round-110">
                                    <div
                                        class="border-4 border-white d-flex mt-4 align-items-center justify-content-center rounded-circle overflow-hidden round-100">
                                        <img src="../assets/images/profile/user-1.jpg" alt="matdash-img"
                                            class="w-100 h-100">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-2 text-center">
                                <h5 class="mb-0">{{ $this->student->first_name . ' ' . $this->student->last_name }}
                                </h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 order-last">
                    </div>
                </div>
                <!-- Nav tabs -->
                <ul class="nav nav-pills user-profile-tab justify-content-start mt-2 bg-primary-subtle rounded-2 rounded-top-0"
                    id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link hstack gap-2 rounded-0 fs-12 py-6
    {{ $activeTab === 'pills-courses' ? 'active' : '' }}"
                            wire:click="$set('activeTab', 'pills-courses')" id="pills-courses-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-courses" type="button" role="tab">
                            <i class="ti ti-book fs-5"></i>
                            Enrollments
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link hstack gap-2 rounded-0 fs-12 py-6
    {{ $activeTab === 'pills-payments' ? 'active' : '' }}"
                            wire:click="$set('activeTab', 'pills-payments')" id="pills-payments-tab"
                            data-bs-toggle="pill" data-bs-target="#pills-payments" type="button" role="tab">
                            <i class="ti ti-credit-card fs-5"></i>
                            Payments
                        </button>
                    </li>
                </ul>

            </div>
        </div>
        <div class="tab-content" id="pills-tabContent">
            <!-- Courses Tab -->
            <div class="tab-pane fade {{ $activeTab === 'pills-courses' ? 'show active' : '' }}" id="pills-courses"
                role="tabpanel" aria-labelledby="pills-courses-tab" tabindex="0">
                <div class="row mb-4">
                    <div class="col-md-4 col-xl-3">
                    </div>
                    <div
                        class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">

                        <a href="javascript:void(0)" wire:click="$dispatch('show-enrollment-modal')"
                            class="btn btn-primary d-flex align-items-center">
                            <i class="ti ti-school text-white me-1 fs-5"></i> Enroll in a Course
                        </a>
                    </div>

                </div>
                <div class="row">
                    @foreach ($student->enrollments as $enrollment)
                        @php
                            $course = $enrollment->course;
                            $modules = $course->modules;
                            $intake = $enrollment->intake;
                            $enrollmentStatus = $enrollment->status ?? 'In Progress';
                        @endphp

                        <div class="col-lg-4 col-md-6 mb-2">
                            <div class="card rounded-3 overflow-hidden h-100">
                                <div class="mt-2 px-7 pb-7 h-100">
                                    <div class="d-flex gap-3 flex-column h-100 justify-content-between">

                                        <h5 class="mt-3 fw-bolder">{{ $course->title }} - {{ $course->level }}</h5>

                                        <div class="d-flex justify-content-between">
                                            @php
                                                $statusClass = match (strtolower($enrollmentStatus)) {
                                                    'approved' => 'bg-primary',
                                                    'pending' => 'bg-warning',
                                                    'rejected' => 'bg-danger',
                                                    'completed' => 'bg-success',
                                                    'withdrawn' => 'bg-dark',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp

                                            <div class="d-flex col-md-6 gap-2">
                                                <h6 class="mt-1 mb-0">Status:</h6>
                                                <span class="badge {{ $statusClass }} text-light">
                                                    {{ ucfirst($enrollmentStatus == 'approved' ? 'Active' : $enrollmentStatus) }}
                                                </span>
                                            </div>

                                            <div class="col-md-6 text-end">
                                                @can('view-enrollments')
                                                    <a style="border:0;"
                                                        href="{{ route('students.enrollment-details', $enrollment->id) }}"
                                                        class="btn btn-sm btn-outline-secondary" title="View Trimesters">
                                                        <iconify-icon style="font-weight:bold;" icon="mdi:eye"
                                                            width="16" height="16"></iconify-icon>
                                                    </a>
                                                @endcan
                                                @can('edit-enrollments')
                                                    <button style="border:0;"
                                                        wire:click="openEditModal({{ $enrollment->id }})"
                                                        class="btn btn-sm btn-outline-primary" title="Edit Status">
                                                        <iconify-icon style="font-weight:bold;" icon="mdi:pencil"
                                                            width="16" height="16"></iconify-icon>
                                                    </button>
                                                @endcan
                                                @can('delete-enrollments')
                                                    <button style="border:0;"
                                                        onclick="confirm('Are you sure you want to delete this enrollment? This action cannot be undone.') || event.stopImmediatePropagation()"
                                                        wire:click="deleteEnrollment({{ $enrollment->id }})"
                                                        class="btn btn-sm btn-outline-danger" title="Delete Enrollment">
                                                        <iconify-icon style="font-weight:bold;" icon="mdi:delete"
                                                            width="16" height="16"></iconify-icon>
                                                    </button>
                                                @endcan
                                            </div>
                                        </div>

                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2 d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:school"
                                                    class="text-primary fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">
                                                    Intake: {{ $enrollment->intake->name }}
                                                </span>
                                            </li>

                                            <li class="mb-2 d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:calendar-range"
                                                    class="text-primary fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">
                                                    Duration: {{ $course->number_of_trimesters }} Trimesters
                                                </span>
                                            </li>

                                            @php
                                                $currentTrimester = $enrollment->trimesters
                                                    ->where('status', 'in-progress')
                                                    ->first();

                                                $hasCurrentTrimester = !is_null($currentTrimester);

                                                $currentNumber = $currentTrimester?->trimester_number;
                                                $total = $course->number_of_trimesters;

                                                $totalTuitionPaid = $enrollment->payments
                                                    ->where('payment_reason', 'tuition')
                                                    ->sum('amount');

                                                $remainingPaid = $totalTuitionPaid;
                                                $effectivePaid = 0;
                                                $balance = 0;

                                                foreach (
                                                    $enrollment->trimesters->sortBy('trimester_number')
                                                    as $trimester
                                                ) {
                                                    if ($remainingPaid <= 0) {
                                                        break;
                                                    }

                                                    if ($trimester->trimester_number < $currentNumber) {
                                                        $remainingPaid -= $trimester->fee_amount;
                                                        continue;
                                                    }

                                                    if ($trimester->trimester_number === $currentNumber) {
                                                        $effectivePaid = min($remainingPaid, $trimester->fee_amount);
                                                        $balance = max(0, $trimester->fee_amount - $remainingPaid);
                                                        break;
                                                    }
                                                }
                                            @endphp

                                            <li class="d-flex align-items-start gap-2 mt-2">
                                                <iconify-icon icon="mdi:progress-check"
                                                    class="text-info fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">
                                                    Current Trimester:
                                                    {{ $hasCurrentTrimester ? $currentNumber . '/' . $total : 'N/A' }}
                                                </span>
                                            </li>

                                            {{-- PAID: show ONLY if there is an in-progress trimester --}}
                                            @if ($hasCurrentTrimester)
                                                <li class="d-flex align-items-start gap-2 mt-2">
                                                    <iconify-icon icon="mdi:cash-check"
                                                        class="text-success fs-4 mt-1"></iconify-icon>
                                                    <span class="text-success fs-3">
                                                        Paid: {{ number_format($effectivePaid, 2) }}
                                                    </span>
                                                </li>
                                                <li class="d-flex align-items-start gap-2 mt-2">
                                                    <iconify-icon icon="mdi:wallet"
                                                        class="fs-4 mt-1 {{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                                                    </iconify-icon>

                                                    <span
                                                        class="fs-3 {{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                                                        Balance: {{ number_format($balance, 2) }}
                                                    </span>
                                                </li>
                                            @endif
                                            @if (!$hasCurrentTrimester && ($course->price - $totalTuitionPaid) > 0)
                                                <li class="d-flex align-items-start gap-2 mt-2">
                                                    <iconify-icon icon="mdi:wallet"
                                                        class="fs-4 mt-1 {{ $course->price - $totalTuitionPaid > 0 ? 'text-danger' : 'text-success' }}">
                                                    </iconify-icon>

                                                    <span
                                                        class="fs-3 {{ $course->price - $totalTuitionPaid > 0 ? 'text-danger' : 'text-success' }}">
                                                        Balance: {{ number_format($course->price - $totalTuitionPaid, 2) }}
                                                    </span>
                                                </li>
                                            @endif
                                        </ul>

                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

            <div class="tab-pane fade {{ $activeTab === 'pills-payments' ? 'show active' : '' }}" id="pills-payments"
                role="tabpanel" aria-labelledby="pills-payments-tab" tabindex="0">
                <div class="row">
                    <div class="col-12">
                        <div class="widget-content searchable-container list">
                            <div class="card card-body">
                                <div class="row">
                                    <div class="col-md-4 col-xl-3">
                                        <form class="position-relative">
                                            <input type="text" class="form-control product-search ps-5"
                                                id="input-search" placeholder="Search Payments..." />
                                            <i
                                                class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                                        </form>
                                    </div>
                                    <div
                                        class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                                        <div class="action-btn show-btn">
                                            <a href="javascript:void(0)"
                                                class="delete-multiple bg-danger-subtle btn me-2 text-danger d-flex align-items-center ">
                                                <i class="ti ti-trash me-1 fs-5"></i> Delete All Row
                                            </a>
                                        </div>
                                        <a href="javascript:void(0)" data-bs-toggle="modal"
                                            data-bs-target="#addPaymentModal"
                                            class="btn btn-primary d-flex align-items-center">
                                            <i class="ti ti-users text-white me-1 fs-5"></i> Add Payment
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-body">
                                <div class="table-responsive">
                                    <table class="table search-table align-middle text-nowrap">
                                        <thead class="header-item">
                                            <th>
                                                <div class="n-chk align-self-center text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input primary"
                                                            id="contact-check-all" />
                                                        <label class="form-check-label"
                                                            for="contact-check-all"></label>
                                                        <span class="new-control-indicator"></span>
                                                    </div>
                                                </div>
                                            </th>
                                            <th>Course</th>
                                            <th>Status</th>
                                            <th>Account Number</th>
                                            <th>Total Paid</th>
                                            <th>Subpayments</th>
                                            <th>Remaining Balance</th>
                                        </thead>
                                        <tbody>
                                            @foreach ($student->enrollments as $enrollment)
                                                @php
                                                    $course = $enrollment->course;
                                                    $modules = $course->modules;
                                                    $intake = $enrollment->intake;
                                                    $enrollmentStatus = $enrollment->status ?? 'In Progress'; // Default status
                                                @endphp
                                                <tr class="search-items">
                                                    <td>
                                                        <div class="n-chk align-self-center text-center">
                                                            <div class="form-check">
                                                                <input type="checkbox"
                                                                    class="form-check-input contact-chkbox primary"
                                                                    id="checkbox1" />
                                                                <label class="form-check-label"
                                                                    for="checkbox1"></label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="ms-3">
                                                                <div class="user-meta-info">
                                                                    <h6 class="user-name mb-0"
                                                                        data-name="{{ $course->title }}">
                                                                        {{ $course->title }}</h6>
                                                                    <small class="text-muted d-block mb-1">
                                                                        {{ 'Intake: ' }}{{ $intake->name ?? '' }}
                                                                    </small>
                                                                    <span class="usr-course-amount fs-3"
                                                                        data-amount="">{{ 'Fee: KES ' }}{{ number_format($course->price, 2) }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge rounded-pill
                                                        {{ $enrollment->status == 'completed'
                                                            ? 'bg-success'
                                                            : ($enrollment->status == 'rejected' || $enrollment->status == 'withdrawn'
                                                                ? 'bg-danger'
                                                                : ($enrollment->status == 'pending'
                                                                    ? 'bg-warning'
                                                                    : 'bg-primary')) }}">{{ $enrollment->status == 'approved' ? 'active' : $enrollment->status }}</span>

                                                    </td>
                                                    <td>
                                                        <span class="usr-email-addr"
                                                            data-email="">{{ $this->student->admission_number . '/' . $course->code }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="usr-email-addr"
                                                            data-email="">{{ number_format($enrollment->payments->sum('amount'), 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="action-btn">
                                                            <a wire:click.prevent='showEnrollmentPayments({{ $enrollment->id }})'
                                                                class="btn btn-secondary btn-sm">
                                                                <i class="fa fa-eye" aria-hidden="true"></i> View
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="{{ $course->price - $enrollment->payments->sum('amount') > 0 ? 'text-danger' : 'text-dark' }}">{{ number_format($course->price - $enrollment->payments->sum('amount'), 2) }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog"
            aria-labelledby="addPaymentModalTitle" aria-hidden="true" wire:ignore.self>
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header d-flex align-items-center">
                        <h5 class="modal-title">Add Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="{{ 'addPayment' }}">
                        <div class="modal-body">
                            <div class="row">
                                <!-- Enrollment Selector -->
                                <div class="col-md-6 mb-3">
                                    <label for="enrollment_id" class="form-label">Student</label>
                                    <select wire:model="enrollment_id" class="form-control">
                                        <option value="">Select Enrollment</option>
                                        @foreach ($student->enrollments()->where('status', 'approved')->get() as $enrollment)
                                            @php
                                                $student = $enrollment->student;
                                                $course = $enrollment->course;
                                                $intake = $enrollment->intake;
                                            @endphp
                                            <option value="{{ $enrollment->id }}">
                                                {{ $course->title }} {{ $course->level ?? '' }} -
                                                Intake: {{ $intake->name ?? 'N/A' }}
                                            </option>
                                        @endforeach

                                    </select>
                                    @error('enrollment_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <!-- Amount Input -->
                                <div class="col-md-6 mb-3">
                                    <label for="amount" class="form-label">Amount</label>
                                    <input type="number" wire:model="amount" class="form-control"
                                        placeholder="Amount" />
                                    @error('amount')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <!-- Payment Method Selector -->
                                <div class="col-md-6 mb-3">
                                    <label for="method" class="form-label">Payment
                                        Method</label>
                                    <select wire:model="payment_method" class="form-control">
                                        <option value="">Select Payment Method</option>
                                        <option value="mpesa">M-Pesa</option>
                                        <option value="bank">Bank</option>
                                        @can('give-discounts')
                                            <option value="discount">Discount</option>
                                        @endcan
                                    </select>
                                    @error('method')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="method" class="form-label">Payment For</label>
                                    <select wire:model="payment_reason" class="form-control">
                                        <option value="">Select Reason</option>
                                        <option value="tuition">Tuition</option>
                                        <option value="exam">Exam</option>
                                        <option value="attachment">Attachment</option>
                                        @can('give-discounts')
                                            <option value="discount">Discount</option>
                                        @endcan
                                    </select>
                                    @error('method')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <!-- Reference Input -->
                                <div class="col-md-6 mb-3">
                                    <label for="reference" class="form-label">Reference</label>
                                    <input type="text" wire:model="reference" class="form-control"
                                        placeholder="Reference" />
                                </div>
                                <!-- Paid Date Input -->
                                <div class="col-md-6 mb-3">
                                    <label for="paid_at" class="form-label">Paid On</label>
                                    <input type="date" wire:model="paid_at" class="form-control" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="payer" class="form-label">Paid By</label>
                                    <input type="text" wire:model="payer" class="form-control"
                                        placeholder="Paid By" />
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="d-flex gap-1 m-0">
                                <button type="submit" class="btn btn-success">
                                    {{ 'Add' }}
                                </button>
                                <button type="button" class="btn bg-danger-subtle text-danger"
                                    data-bs-dismiss="modal">Discard
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="enrollCourseModal" tabindex="-1" aria-labelledby="enrollCourseModalTitle"
        aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header d-flex align-items-center">
                    <h5 class="modal-title">Enroll Student to a Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <!-- Select Course -->
                    <div>
                        <p class="form-label d-block mb-1 fw-semibold text-muted">Select Course</p>
                        <select wire:model="selectedCourseId" data-model="selectedCourseId"
                            class="select2 form-select" id="course">
                            <option value="">-- Choose Course --</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->title }} - {{ $course->level }}
                                </option>
                            @endforeach
                        </select>
                        @error('selectedCourseId')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Select Intake -->
                    <div class="mt-3">
                        <p class="form-label d-block mb-1 fw-semibold text-muted">Select Intake</p>
                        <select wire:model="selectedIntakeId" data-model="selectedIntakeId"
                            class="select2 form-select" id="intake">
                            <option value="">-- Choose Intake --</option>
                            @foreach ($intakes as $intake)
                                <option value="{{ $intake->id }}">{{ $intake->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedIntakeId')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <!-- Select Status -->
                    <div class="mt-3">
                        <label for="status" class="mb-1">Status</label>
                        <select wire:model="status" class="form-select" id="status">
                            @foreach ($availableStatuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Select Class Group -->
                    {{-- <div class="mt-3">
                        <label for="class_group_id" class="mb-1">Select Class Group</label>
                        <select id="class_group_id" wire:model.live="class_group_id" class="form-control">
                            <option value="">-- Choose Class Group --</option>
                            @foreach ($classGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}
                                    ({{ $group->intake->name ?? 'No Intake' }})
                                </option>
                            @endforeach
                        </select>
                        @error('class_group_id')
                            <small class="text-error">{{ $message }}</small>
                        @enderror
                    </div> --}}

                    <!-- Enroll Button -->
                    <div class="mt-3">
                        <button wire:click="enroll" class="btn btn-success">Enroll</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Sub-Payments Breakdown</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Payment Method</th>
                                <th>Payment For</th>
                                <th>Amount</th>
                                <th>Paid By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalPayments = 0; @endphp
                            @foreach ($enrollmentPayments as $payment)
                                <tr>
                                    <td>{{ $payment->reference ?? 'N/A' }}</td>
                                    <td>{{ $payment->transaction_id ?? 'N/A' }}</td>
                                    <td>
                                        {{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d/m/y h:i A') : 'N/A' }}
                                    </td>
                                    <td>{{ $payment->payment_method ? ucfirst($payment->payment_method) : 'N/A' }}</td>
                                    <td>{{ $payment->payment_reason ? ucfirst($payment->payment_reason) : 'N/A' }}</td>
                                    <td>{{ number_format($payment->amount ?? 0, 2) }}</td>
                                    <td>{{ $payment->payer ?? 'N/A' }}</td>
                                    <td>
                                        <button class="btn-print"
                                            onclick="printReceipt(
                    '{{ $payment->enrollment->course->title ?? 'N/A' }}',
                    '{{ number_format($payment->amount, 2) }}',
                    '{{ ucfirst($payment->payment_method) }}',
                    '{{ \Carbon\Carbon::parse($payment->paid_at)->format('d M Y') }}',
                    '{{ $payment->transaction_id ?? 'N/A' }}',
                    '{{ $payment->enrollment->student->first_name ?? 'N/A' }}',
                    '{{ $payment->enrollment->student->last_name ?? 'N/A' }}',
                    '{{ 'TTI/' . $payment->enrollment->student->admission_number . '/' . $payment->enrollment->student->created_at->format('Y') ?? 'N/A' }}',
                    '{{ 'RCT' . $payment->id }}',
                    '{{ $payment->enrollment->course->level ?? 'N/A' }}',
                    '{{ ucfirst($payment->payment_reason) ?? 'N/A' }}',
                    '{{ ucfirst($payment->payer) ?? 'N/A' }}',
                    '{{ $payment->narration ?? 'N/A' }}',

                )">
                                            <i class="ti ti-printer"></i> Print
                                        </button>
                                    </td>
                                </tr>
                                @php $totalPayments += $payment->amount ?? 0; @endphp
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="5">Total</th>
                                <th colspan="1">Ksh {{ number_format($totalPayments, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="enrollmentStatusModal" tabindex="-1" role="dialog"
        aria-labelledby="enrollmentStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Enrollment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{-- <label class="my-2" for="enr">Course:</label>
                    <select wire:model="course_id" class="form-control" id="enr">
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ ucfirst($course->title) }} -
                                {{ $course->level }}</option>
                        @endforeach
                    </select> --}}
                </div>
                <div class="modal-body">
                    <label class="my-2" for="status">Select Status:</label>
                    <select wire:model="enrollmentStatus" class="form-control" id="status">
                        @foreach ($availableStatuses as $status)
                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Discard</button>
                    <button type="button" class="btn btn-primary" wire:click="updateEnrollment">Update
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Printable Receipt -->
    <div id="receipt" class="receipt-print">
        <div
            style="max-width: 650px; margin: auto; font-family: 'Segoe UI', Tahoma, sans-serif; padding: 25px; border: 1px solid #bbb; background-color: #fff; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">

            <!-- Header -->
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="assets/images/logos/tabor_logo.png" alt="Company Logo" style="height: 70px;">
                <h2 style="margin: 10px 0 4px; color: #0e334e; letter-spacing: 0.4px; font-size: 20px;">Tabor Training
                    Institute</h2>
                <p style="font-size: 13px; color: #666; margin: 0;">Official Payment Receipt</p>
            </div>

            <hr style="border: none; border-top: 2px solid #0e334e; margin: 15px 0 25px;">

            <!-- Receipt Info -->
            <table style="width: 100%; font-size: 13px; border-collapse: collapse; margin-bottom: 15px;">
                <tr>
                    <td style="color: #555;">Receipt No:</td>
                    <td style="text-align: right;"><strong><span id="receipt-number"></span></strong></td>
                </tr>
                <tr>
                    <td style="color: #555;">Payment Date:</td>
                    <td style="text-align: right;"><strong><span id="receipt-date"></span></strong></td>
                </tr>
            </table>

            <!-- Section: Student Info -->
            <div style="border: 1px solid #ddd; border-radius: 6px; margin-bottom: 20px;">
                <div style="background: #f8f9fb; padding: 10px 15px; border-bottom: 1px solid #ddd;">
                    <h3 style="color: #0e334e; margin: 0; font-size: 15px;">Student Information</h3>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Student Name:</strong>
                        </td>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span
                                id="receipt-student-name"></span></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Student ID:</strong></td>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span
                                id="receipt-student-id"></span></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Course Title:</strong>
                        </td>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span
                                id="receipt-course"></span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Section: Payment Info -->
            <div style="border: 1px solid #ddd; border-radius: 6px;">
                <div style="background: #f8f9fb; padding: 10px 15px; border-bottom: 1px solid #ddd;">
                    <h3 style="color: #0e334e; margin: 0; font-size: 15px;">Payment Details</h3>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Amount Paid:</strong>
                        </td>
                        <td
                            style="padding: 8px 15px; border-bottom: 1px solid #eee; color: #0e334e; font-weight: bold;">
                            KES <span id="receipt-amount"></span></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Payment Method:</strong>
                        </td>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span
                                id="receipt-method"></span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Ref/TranID:</strong></td>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span
                                id="receipt-reference"></span></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><strong>Reason:</strong></td>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span
                                id="receipt-payment-reason"></span></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;">
                            <strong>Narration/Comments:</strong>
                        </td>
                        <td style="padding: 8px 15px; border-bottom: 1px solid #eee;"><span
                                id="receipt-narration"></span></td>
                    </tr>
                </table>
            </div>

            <hr style="border: none; border-top: 1px dashed #ccc; margin: 25px 0;">

            <!-- Footer -->
            <div style="text-align: center; font-size: 13px; color: #555;">
                <p>Thank you for your payment. This receipt serves as <strong>official proof of payment</strong>.</p>
                <p style="font-size: 12px; color: #888; margin-top: 8px;">For assistance, contact
                    <a href="mailto:support@tabor.ac.ke"
                        style="color: #0e334e; text-decoration: none;">office@tabor.ac.ke</a>
                </p>
                <p style="font-size: 12px; color: #aaa; margin-top: 10px;">&copy; {{ date('Y') }} Tabor Training
                    Institute. All Rights Reserved.</p>
            </div>
        </div>
    </div>

</div>



@push('scripts')
    <script>
        window.addEventListener('show-enrollment-modal', () => {
            const modal = $('#enrollCourseModal');

            new bootstrap.Modal(modal[0]).show();

            modal.find('.select2').each(function() {
                const select = $(this);

                if (select.hasClass('select2-hidden-accessible')) {
                    select.select2('destroy');
                }

                select.select2({
                    dropdownParent: modal.find('.modal-body')
                }).on('change', function(e) {
                    const value = $(this).val();
                    const model = $(this).data('model');
                    if (model) {
                        @this.set(model, value);
                    }
                });
            });
        });


        window.addEventListener('hide-enrollment-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('enrollCourseModal'))?.hide();
        });

        window.addEventListener('show-enrollment-status-modal', () => {
            new bootstrap.Modal(document.getElementById('enrollmentStatusModal')).show();
        });

        window.addEventListener('hide-enrollment-status-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('enrollmentStatusModal'))?.hide();
        });
        window.addEventListener('hide-payment-modal', () => {
            const modalElement = document.getElementById('addPaymentModal');

            // Ensure the modal is initialized
            const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
            modalInstance.hide();

            // Activate the Payments tab
            const paymentsTabButton = document.getElementById('pills-payments-tab');
            const paymentsTabContent = document.getElementById('pills-payments');

            // Remove 'active show' from all tab buttons and contents
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('show', 'active'));

            // Add 'active show' to the payments tab and content
            paymentsTabButton.classList.add('active');
            paymentsTabContent.classList.add('show', 'active');
        });

        window.addEventListener('show-enrollment-payments-modal', () => {
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        });
        window.addEventListener('hide-enrollment-payments-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('paymentModal'))?.hide();
        });

        function printReceipt(course, amount, method, date, reference, firstName, lastName, studentId, receiptNumber, level,
            reason, narration) {
            // Fill receipt
            document.getElementById('receipt-course').innerText = course + ' - ' + level;
            document.getElementById('receipt-amount').innerText = amount;
            document.getElementById('receipt-method').innerText = method;
            document.getElementById('receipt-date').innerText = date;
            document.getElementById('receipt-reference').innerText = reference;
            document.getElementById('receipt-student-name').innerText = firstName + ' ' + lastName;
            document.getElementById('receipt-student-id').innerText = studentId;
            document.getElementById('receipt-number').innerText = receiptNumber;
            document.getElementById('receipt-payment-reason').innerText = reason;
            document.getElementById('receipt-narration').innerText = narration;

            // Delay print to allow DOM to update
            setTimeout(() => {
                window.print();
            }, 500); // 300ms is usually enough
        }
    </script>
@endpush
