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
    public $status = 'pending';
    public $studentId;
    public $selectedCourseId;
    public $selectedIntakeId;
    public $courses;
    public $intakes;
    public $class_group_id;
    public $classGroups = [];
    public $availableStatuses = ['pending', 'approved', 'rejected'];
    public $enrollmentId;
    public $enrollmentStatus;

    public function rules()
    {
        return [
            'selectedCourseId' => 'required|exists:courses,id',
        ];
    }

    public function mount($student_id)
    {
        $this->student = Student::with(['enrollments.course.modules', 'enrollments.intake'])->findOrFail($student_id);

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
            Enrollment::create([
                'student_id' => $this->studentId,
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
                <ul class="nav nav-pills user-profile-tab justify-content-start mt-2 bg-primary-subtle rounded-2 rounded-top-0"
                    id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active hstack gap-2 rounded-0 fs-12 py-6" id="pills-courses-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-courses" type="button" role="tab"
                                aria-controls="pills-courses" aria-selected="true">
                            <i class="ti ti-book fs-5"></i> <!-- Book icon for Courses -->
                            Courses <span class="badge bg-primary rounded-pill px-3 py-2"> Enrollments: {{ $this->student->enrollments->count() }}
                            </span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link hstack gap-2 rounded-0 fs-12 py-6" id="pills-attendance-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-attendance" type="button" role="tab"
                                aria-controls="pills-attendance" aria-selected="false">
                            <i class="ti ti-calendar-check fs-5"></i>
                            <!-- Calendar icon with checkmark for Attendance -->
                            <span class="d-none d-md-block">Attendance</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link hstack gap-2 rounded-0 fs-12 py-6" id="pills-payments-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-payments" type="button" role="tab"
                                aria-controls="pills-payments" aria-selected="false">
                            <i class="ti ti-credit-card fs-5"></i> <!-- Credit card icon for Payments -->
                            <span class="d-none d-md-block">Payments</span>
                        </button>
                    </li>
                </ul>

            </div>
        </div>
        <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="pills-courses" role="tabpanel"
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
                                                    'approved' => 'bg-success',
                                                    'pending' => 'bg-warning',
                                                    'rejected' => 'bg-danger',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp

                                            <div class="d-flex align-items-center">
                                                <div class="d-flex align-items-center gap-1">
                                                    <h6 class="mt-1">Status:</h6><span
                                                        class="badge {{ $statusClass }} text-light">{{ ucfirst($enrollmentStatus) }}</span>
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
                                            <li class="d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:currency-usd"
                                                              class="text-info fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Fee Balance:
                                                    {{ number_format($course->fee - $enrollment->payments->sum('amount'), 2) }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="tab-pane fade" id="pills-attendance" role="tabpanel" aria-labelledby="pills-attendance-tab"
                 tabindex="0">
                <div class="row">
                    <div class="col-12">
                        <div class="widget-content searchable-container list">
                            <div class="card card-body">
                                <div class="row">
                                    <div class="col-md-4 col-xl-3">
                                        <form class="position-relative">
                                            <input type="text" class="form-control product-search ps-5"
                                                   id="input-search" placeholder="Search Payments..."/>
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
                                                           id="contact-check-all"/>
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
                                                               id="checkbox1"/>
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
            <div class="tab-pane fade" id="pills-payments" role="tabpanel" aria-labelledby="pills-payments-tab"
                 tabindex="0">
                <div class="row">
                    <div class="col-12">
                        <div class="widget-content searchable-container list">
                            <div class="card card-body">
                                <div class="row">
                                    <div class="col-md-4 col-xl-3">
                                        <form class="position-relative">
                                            <input type="text" class="form-control product-search ps-5"
                                                   id="input-search" placeholder="Search Payments..."/>
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
                                        <a href="javascript:void(0)" id="btn-add-contact"
                                           class="btn btn-primary d-flex align-items-center">
                                            <i class="ti ti-users text-white me-1 fs-5"></i> Add Payment
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal -->
                            <div class="modal fade" id="addContactModal" tabindex="-1" role="dialog"
                                 aria-labelledby="addContactModalTitle" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header d-flex align-items-center">
                                            <h5 class="modal-title">Contact</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="add-contact-box">
                                                <div class="add-contact-content">
                                                    <form id="addContactModalTitle">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-name">
                                                                    <input type="text" id="c-name"
                                                                           class="form-control" placeholder="Name"/>
                                                                    <span class="validation-text text-danger"></span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-email">
                                                                    <input type="text" id="c-email"
                                                                           class="form-control" placeholder="Email"/>
                                                                    <span class="validation-text text-danger"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-occupation">
                                                                    <input type="text" id="c-occupation"
                                                                           class="form-control"
                                                                           placeholder="Occupation"/>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-phone">
                                                                    <input type="text" id="c-phone"
                                                                           class="form-control" placeholder="Phone"/>
                                                                    <span class="validation-text text-danger"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="mb-3 contact-location">
                                                                    <input type="text" id="c-location"
                                                                           class="form-control" placeholder="Location"/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <div class="d-flex gap-6 m-0">
                                                <button id="btn-add" class="btn btn-success">Add</button>
                                                <button id="btn-edit" class="btn btn-success">Save</button>
                                                <button class="btn bg-danger-subtle text-danger"
                                                        data-bs-dismiss="modal"> Discard
                                                </button>
                                            </div>

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
                                                           id="contact-check-all"/>
                                                    <label class="form-check-label"
                                                           for="contact-check-all"></label>
                                                    <span class="new-control-indicator"></span>
                                                </div>
                                            </div>
                                        </th>
                                        <th>Course</th>
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
                                                                   id="checkbox1"/>
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
                                                        <span class="usr-email-addr"
                                                              data-email="">{{ $this->student->admission_number . '/' . $course->code }}</span>
                                                </td>
                                                <td>
                                                        <span class="usr-email-addr"
                                                              data-email="">{{ number_format($enrollment->payments->sum('amount'), 2) }}</span>
                                                </td>
                                                <td>
                                                    <div class="action-btn">
                                                        <a href="#" class="btn btn-warning btn-sm"
                                                           data-bs-toggle="modal" data-bs-target="#paymentModal">
                                                            <i class="fa fa-eye" aria-hidden="true"></i>
                                                            View
                                                        </a>
                                                    </div>
                                                </td>
                                                <td>
                                                        <span class="usr-ph-no"
                                                              data-phone="">{{ number_format($course->price - $enrollment->payments->sum('amount'), 2) }}</span>
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
                        <select wire:model="selectedCourseId" class="form-select" id="course">
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
                    <button type="button" class="btn btn-primary"
                            wire:click="updateEnrollmentStatus">Update
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
    </script>
@endpush
