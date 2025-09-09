<?php

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Intake;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use App\Models\ClassGroup;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $student;
    public $amount, $payment_method, $enrollment_id, $reference, $paid_at, $payer;
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

    public function enrollmentRules()
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
            $this->student->enrollments()->create([
                'course_id' => $this->selectedCourseId,
                'intake_id' => $this->selectedIntakeId,
                'status' => $this->status,
                'enrolled_at' => now(),
            ]);

            //To review with Kevin

            // DB::table('student_class_group')->insert([
            //     'student_id' => $this->studentId,
            //     'class_group_id' => $this->class_group_id,
            // ]);

            LivewireAlert::text('Student enrolled successfully.!')->success()->toast()->position('top-end')->show();

            $this->dispatch('hide-enrollment-modal');
        }
    }

    public function openEditModal($id)
    {
        $this->enrollmentId = $id;
        $this->dispatch('show-enrollment-status-modal');
    }

    public function updateEnrollmentStatus()
    {
        try {
            DB::beginTransaction();

            $enrollment = Enrollment::findOrFail($this->enrollmentId);
            $enrollment->status = $this->enrollmentStatus;
            $enrollment->save();

            if ($this->enrollmentStatus == 'approved') {
                $user = User::find($enrollment->student->user_id);
                $user->active = true;
                $user->save();
            }

            DB::commit();

            $this->dispatch('hide-enrollment-status-modal');

            LivewireAlert::text('Enrollment status updated successfully.!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update enrollment status: ' . $e->getMessage());

            LivewireAlert::text('Failed to update enrollment status.!')->error()->toast()->position('top-end')->show();
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
                'method' => $this->payment_method,
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
                            Courses
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link hstack gap-2 rounded-0 fs-12 py-6
    {{ $activeTab === 'pills-attendance' ? 'active' : '' }}"
                            wire:click="$set('activeTab', 'pills-attendance')" id="pills-attendance-tab"
                            data-bs-toggle="pill" data-bs-target="#pills-attendance" type="button" role="tab">
                            <i class="ti ti-calendar-check fs-5"></i>
                            Attendance
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
            <div class="tab-pane fade {{ $activeTab === 'pills-courses' ? 'show active' : '' }}" id="pills-courses" role="tabpanel"
                aria-labelledby="pills-courses-tab" tabindex="0">
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
                            $enrollmentStatus = $enrollment->status ?? 'In Progress'; // Default status
                        @endphp

                        <div class="col-lg-4 col-md-6 mb-2">
                            <div class="card rounded-3 overflow-hidden h-100">
                                <div class="mt-2 px-7 pb-7 h-100">
                                    <div class="d-flex gap-3 flex-column h-100 justify-content-between">
                                        <!-- Course Title -->
                                        <h3 class="fs-7 mt-3 fw-bolder">{{ $course->title }}</h3>

                                        <!-- Enrollment Status Badge -->
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

                                            <div class="d-flex align-items-center">
                                                <div class="d-flex align-items-center gap-1">
                                                    <h6 class="mt-1">Status:</h6><span
                                                        class="badge {{ $statusClass }} text-light">{{ ucfirst($enrollmentStatus == 'approved' ? 'Active' : $enrollmentStatus) }}</span>
                                                </div>
                                                <!-- Pencil Icon Button -->
                                                <button style="border:0px;"
                                                    wire:click="openEditModal({{ $enrollment->id }})"
                                                    class="btn btn-sm btn-outline-warning" title="Edit Status">
                                                    <iconify-icon style="font-weight:bold;" icon="mdi:pencil"
                                                        width="16" height="16"></iconify-icon>
                                                </button>

                                            </div>
                                        </div>
                                        <!-- Course Details -->
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2 d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:map-marker-radius"
                                                    class="text-primary fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Intake:
                                                    {{ $enrollment->intake->name }}</span>
                                            </li>
                                            <li class="mb-2 d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:calendar-clock"
                                                    class="text-primary fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Duration:
                                                    {{ $course->duration ?? 'N/A' }}</span>
                                            </li>
                                            <li class="mb-2 d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:laptop"
                                                    class="text-success fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Mode:
                                                    {{ ucfirst($course->mode) ?? 'N/A' }}</span>
                                            </li>
                                            <li class="mb-2 d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:school-outline"
                                                    class="text-warning fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Level:
                                                    {{ $course->level ?? 'N/A' }}</span>
                                            </li>
                                            <li class="d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:certificate-outline"
                                                    class="text-info fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Certification:
                                                    {{ $course->certification ?? 'N/A' }}</span>
                                            </li>
                                            @if ($enrollment->status == 'approved')
                                                <li class="d-flex align-items-start gap-2">
                                                    <iconify-icon icon="mdi:currency-usd"
                                                        class="text-info fs-4 mt-1"></iconify-icon>
                                                    @if ($course->price - $enrollment->payments->sum('amount') > 0)
                                                        <span class="text-danger fs-3">Fee Balance:
                                                            {{ number_format($course->price - $enrollment->payments->sum('amount'), 2) }}</span>
                                                    @else
                                                        <span class="text-dark fs-3">Fee Balance:
                                                            {{ number_format($course->price - $enrollment->payments->sum('amount'), 2) }}</span>
                                                    @endif
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
            <!-- Attendance Tab -->
            <div class="tab-pane fade {{ $activeTab === 'pills-attendance' ? 'show active' : '' }}" id="pills-attendance" role="tabpanel" aria-labelledby="pills-attendance-tab"
                tabindex="0">
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
                                            <th>Attendance</th>
                                            <th>Action</th>
                                        </thead>
                                        <tbody>
                                            <tr class="search-items">
                                                <td>
                                                    <div class="n-chk align-self-center text-center">
                                                        <div class="form-check">
                                                            <input type="checkbox"
                                                                class="form-check-input contact-chkbox primary"
                                                                id="checkbox1" />
                                                            <label class="form-check-label" for="checkbox1"></label>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="ti ti-code fs-5 me-2"></i>
                                                        <div class="ms-3">
                                                            <div class="user-meta-info">
                                                                <h6 class="user-name mb-0"
                                                                    data-name="Advanced Web Development">Advanced Web
                                                                    Development</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success-subtle text-success">
                                                        85%
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-btn">
                                                        <a href="javascript:void(0)" class="text-primary edit">
                                                            <i class="ti ti-eye fs-5"></i>
                                                        </a>
                                                        <a href="javascript:void(0)" class="text-dark delete ms-2">
                                                            <i class="ti ti-trash fs-5"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Payments Tab -->
            <div class="tab-pane fade {{ $activeTab === 'pills-payments' ? 'show active' : '' }}" id="pills-payments" role="tabpanel" aria-labelledby="pills-payments-tab"
                tabindex="0">
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
                                                        <span class="badge rounded-pill
                                                        {{ $enrollment->status == 'completed' ? 'bg-success' :
                                                        ($enrollment->status == 'rejected'|| $enrollment->status == 'withdrawn' ? 'bg-danger' :
                                                        ($enrollment->status == 'pending' ? 'bg-warning' : 'bg-primary')) }}">{{ $enrollment->status=='approved' ? 'active' : $enrollment->status }}</span>

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
                                                            <a href="#" class="btn btn-secondary btn-sm"
                                                                data-bs-toggle="modal" data-bs-target="#paymentModal">
                                                                <i class="fa fa-eye" aria-hidden="true"></i>
                                                                View
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-danger">{{ number_format($course->price - $enrollment->payments->sum('amount'), 2) }}</span>
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
                                                        <input type="number" wire:model="amount"
                                                            class="form-control" placeholder="Amount" />
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
                                                            <option value="cash">Cash</option>
                                                            <option value="mpesa">M-Pesa</option>
                                                            <option value="card">Card</option>
                                                            <option value="bank">Bank</option>
                                                        </select>
                                                        @error('method')
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </div>
                                                    <!-- Reference Input -->
                                                    <div class="col-md-6 mb-3">
                                                        <label for="reference" class="form-label">Reference</label>
                                                        <input type="text" wire:model="reference"
                                                            class="form-control" placeholder="Reference" />
                                                    </div>
                                                    <!-- Paid Date Input -->
                                                    <div class="col-md-6 mb-3">
                                                        <label for="paid_at" class="form-label">Paid On</label>
                                                        <input type="date" wire:model="paid_at"
                                                            class="form-control" />
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
                        <label for="course" class="mb-1">Select Course</label>
                        <select wire:model="selectedCourseId" class="form-select select2" id="course">
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
                        <label for="intake" class="mb-1">Select Intake</label>
                        <select wire:model="selectedIntakeId" class="form-select" id="intake">
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
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Sub-Payments Breakdown</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Reference</th>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Payment Method</th>
                                <th>Amount</th>
                                <th>Paid By</th>
                                {{-- <th>Action</th> --}}
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalPayments = 0;
                            @endphp
                            @foreach ($student->enrollments as $enrollment)
                                @foreach ($enrollment->payments as $payment)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $payment->reference ?? 'N/A' }}</td>
                                        <td>{{ $payment->transaction_id ?? 'N/A' }}</td>
                                        <td>{{ Carbon\Carbon::parse($payment->paid_at)->format('d/m/y h:i A') }}</td>
                                        <td>{{ ucfirst($payment->payment_method) ?? 'N/A' }}</td>
                                        <td>{{ number_format($payment->amount, 2) }}</td>
                                        <td>{{ $payment->payer ?? 'N/A' }}</td>
                                        {{-- <td>
                                        <a href="#" class="btn btn-warning btn-sm">
                                            <i class="fa fa-exchange" aria-hidden="true"></i> Reallocate
                                        </a>
                                    </td> --}}
                                    </tr>
                                    @php
                                        $totalPayments += $payment->amount;
                                    @endphp
                                @endforeach
                            @endforeach
                            <!-- Add more rows as needed -->
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
                    <h5 class="modal-title">Edit Enrollment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    <button type="button" class="btn btn-primary" wire:click="updateEnrollmentStatus">Update
                    </button>
                </div>
            </div>
        </div>
    </div>


</div>



@push('scripts')
    <script>
        window.addEventListener('show-enrollment-modal', () => {
            new bootstrap.Modal(document.getElementById('enrollCourseModal')).show();
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
    </script>
@endpush
