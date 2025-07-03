<?php

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Intake;
use App\Models\Student;
use Livewire\Volt\Component;

new class extends Component {

    public $student;
    public $studentId;
    public $selectedCourseId;
    public $selectedIntakeId;
    public $courses;
    public $intakes;


    public function rules()
    {
        return [
            'selectedCourseId' => 'required|exists:courses,id',
        ];

    }

    public function mount($student_id)
    {
        $this->student = Student::with([
            'enrollments.course.modules',
            'enrollments.intake',
        ])->findOrFail($student_id);

        $this->studentId = $student_id;
        $this->courses = Course::all();
        $this->intakes = Intake::all();

    }


    public function enroll()
    {
        $this->validate();

        Enrollment::create([
            'student_id' => $this->studentId,
            'course_id' => $this->selectedCourseId,
            'intake_id' => $this->selectedIntakeId,
            'enrolled_at' => now()
        ]);

        session()->flash('success', 'Student enrolled successfully.');

        $this->dispatch('hide-enrollment-modal');

    }

}; ?>

<div class="col-12">
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Student Profile</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">
                        <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                          Student  Profile
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
                <img src="../assets/images/backgrounds/profilebg.jpg" alt="matdash-img" class="img-fluid">
                <div class="row align-items-center">
                    <div class="col-lg-4 order-lg-1 order-2">
                        <div class="d-flex align-items-center justify-content-around m-4">
                            <div class="text-center">
                                <i class="ti ti-file-description fs-6 d-block mb-2"></i>
                                <h4 class="mb-0 fw-semibold lh-1">6</h4>
                                <p class="mb-0 ">Courses</p>
                            </div>
                            <div class="text-center">
                                <i class="ti ti-user-circle fs-6 d-block mb-2"></i>
                                <h4 class="mb-0 fw-semibold lh-1">10</h4>
                                <p class="mb-0 ">Assessments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mt-n3 order-lg-2 order-1">
                        <div class="mt-n5">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="d-flex align-items-center justify-content-center round-110">
                                    <div
                                        class="border border-4 border-white d-flex align-items-center justify-content-center rounded-circle overflow-hidden round-100">
                                        <img src="../assets/images/profile/user-1.jpg" alt="matdash-img"
                                             class="w-100 h-100">
                                    </div>
                                </div>
                            </div>
                            <div class="text-center">
                                <h5 class="mb-0">David McMichael</h5>
                                <p class="mb-0">students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 order-last">
                    </div>
                </div>
                <ul class="nav nav-pills user-profile-tab justify-content-end mt-2 bg-primary-subtle rounded-2 rounded-top-0"
                    id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active hstack gap-2 rounded-0 fs-12 py-6" id="pills-courses-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-courses" type="button" role="tab"
                                aria-controls="pills-courses" aria-selected="true">
                            <i class="ti ti-book fs-5"></i> <!-- Book icon for Courses -->
                            <span class="d-none d-md-block">Courses</span>
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
                    @foreach($student->enrollments as $enrollment)
                        @php
                            $course = $enrollment->course;
                            $modules = $course->modules;
                            $intake = $enrollment->intake;
                        @endphp

                        <div class="col-lg-4 col-md-6">
                            <div class="card">
                                <img src="{{ $course->image_url ?? '../assets/images/blog/blog-img5.jpg' }}"
                                     class="card-img-top" alt="course-img"/>
                                <div class="card-body p-10">
                                    <h4 class="card-title">{{ strtoupper($course->name) }}</h4>

                                    <p class="card-text">
                                        {{ strtoupper($course->description) ?? 'No description available.' }}
                                    </p>

                                    <!-- Progress bar, attendance, etc. could be derived from $enrollment -->
                                    <div class="col d-flex align-items-left mb-4 justify-content-center">
                                        <div data-label="40%" class="css-bar mb-0 css-bar-warning css-bar-40">
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <span class="badge bg-warning text-light">In Progress</span>
                                    </div>
                                </div>

                                <!-- Modules -->
                                <ul class="list-group list-group-flush">
                                    @foreach($modules as $module)
                                        <li class="list-group-item">{{ ucfirst(strtolower($module->title)) }}</li>
                                    @endforeach
                                </ul>

                                <!-- Bottom Badges -->
                                <div class="card-body p-10">
                                    <div class="d-flex justify-content-between">
                                        <span
                                            class="badge bg-primary text-light">Attendance: {{ $enrollment->attendance ?? '0%' }}</span>
                                        <span
                                            class="badge bg-success text-light">Payment: {{ $enrollment->payment_status ?? 'Pending' }}</span>
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
                                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
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
                                                    <label class="form-check-label" for="contact-check-all"></label>
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
                                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
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
                                                                    <input type="text" id="c-name" class="form-control"
                                                                           placeholder="Name"/>
                                                                    <span class="validation-text text-danger"></span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-email">
                                                                    <input type="text" id="c-email" class="form-control"
                                                                           placeholder="Email"/>
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
                                                                    <input type="text" id="c-phone" class="form-control"
                                                                           placeholder="Phone"/>
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
                                                    <label class="form-check-label" for="contact-check-all"></label>
                                                    <span class="new-control-indicator"></span>
                                                </div>
                                            </div>
                                        </th>
                                        <th>Course</th>
                                        <th>Total Paid</th>
                                        <th>Subpayments</th>
                                        <th>Remaining Balance</th>
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
                                                            <small class="text-muted d-block mb-1">
                                                                January 2026
                                                            </small>
                                                            <span class="usr-course-amount fs-3" data-amount="$1,500">$1,500</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="usr-email-addr" data-email="$1,500">$1,500</span>
                                            </td>
                                            <td>
                                                <span class="usr-location">Payment 1: $500 (Jan 10, 2025)</span><br>
                                                <span class="usr-location">Payment 2: $500 (Feb 5, 2025)</span><br>
                                                <a href="#" data-bs-toggle="modal" data-bs-target="#paymentModal">View
                                                    More</a>
                                            </td>
                                            <td>
                                                <span class="usr-ph-no" data-phone="$500">$500</span>
                                            </td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="#"
                                                       class="btn btn-warning btn-sm">
                                                        <i class="fa fa-exchange" aria-hidden="true"></i> Reallocate
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="search-items">
                                            <td>
                                                <div class="n-chk align-self-center text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox"
                                                               class="form-check-input contact-chkbox primary"
                                                               id="checkbox2"/>
                                                        <label class="form-check-label" for="checkbox2"></label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="ti ti-brand-react fs-5 me-2"></i>
                                                    <div class="ms-3">
                                                        <div class="user-meta-info">
                                                            <h6 class="user-name mb-0" data-name="React for Beginners">
                                                                React for Beginners</h6>
                                                            <span class="usr-course-amount fs-3"
                                                                  data-amount="$500">$500</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="usr-email-addr" data-email="$500">$500</span>
                                            </td>
                                            <td>
                                                <span class="usr-location"
                                                      data-location="Payment 1: $500 (Nov 5, 2024)">Payment 1: $500 (Nov 5, 2024)</span><br>
                                                <a href="#" data-bs-toggle="modal" data-bs-target="#paymentModal">View
                                                    More</a>
                                            </td>
                                            <td>
                                                <span class="usr-ph-no" data-phone="$500">$500</span>
                                            </td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="#"
                                                       class="btn btn-warning btn-sm">
                                                        <i class="fa fa-exchange" aria-hidden="true"></i> Reallocate
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="search-items">
                                            <td>
                                                <div class="n-chk align-self-center text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox"
                                                               class="form-check-input contact-chkbox primary"
                                                               id="checkbox3"/>
                                                        <label class="form-check-label" for="checkbox3"></label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="ti ti-palette fs-5 me-2"></i>
                                                    <div class="ms-3">
                                                        <div class="user-meta-info">
                                                            <h6 class="user-name mb-0"
                                                                data-name="UI/UX Design Essentials">UI/UX Design
                                                                Essentials</h6>
                                                            <span class="usr-course-amount fs-3" data-amount="$1,000">$1,000</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="usr-email-addr" data-email="$1,000">$1,000</span>
                                            </td>
                                            <td>
                                                <span class="usr-location"
                                                      data-location="Payment 1: $1,000 (Dec 15, 2024)">Payment 1: $1,000 (Dec 15, 2024)</span><br>
                                                <a href="#" data-bs-toggle="modal" data-bs-target="#paymentModal">View
                                                    More</a>
                                            </td>
                                            <td>
                                                <span class="usr-ph-no" data-phone="$0">$0</span>
                                            </td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="#"
                                                       class="btn btn-warning btn-sm">
                                                        <i class="fa fa-exchange" aria-hidden="true"></i> Reallocate
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
                    <div>
                        <label for="course" class="mb-1">Select Course</label>
                        <select wire:model="selectedCourseId" class="form-select" id="course">
                            <option value="">-- Choose Course --</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                            @endforeach
                        </select>

                        @error('selectedCourseId')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror

                    </div>
                    <div class="mt-3">
                        <label for="intake" class="mb-1">Select Intake</label>
                        <select wire:model="selectedIntakeId" class="form-select" id="intake">
                            <option value="">-- Choose Intake --</option>
                            @foreach($intakes as $intake)
                                <option value="{{ $intake->id }}">{{ $intake->name }}</option>
                            @endforeach
                        </select>

                        @error('selectedCourseId')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror

                    </div>
                    <div class="mt-3">
                        <button wire:click="enroll" class="btn btn-success">Enroll</button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
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
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>1</td>
                            <td>$500</td>
                            <td>Jan 10, 2025</td>
                            <td><a href="#"
                                   class="btn btn-warning btn-sm">
                                    <i class="fa fa-exchange" aria-hidden="true"></i> Reallocate
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>$500</td>
                            <td>Feb 5, 2025</td>
                            <td><a href="#"
                                   class="btn btn-warning btn-sm">
                                    <i class="fa fa-exchange" aria-hidden="true"></i> Reallocate
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>$500</td>
                            <td>Mar 1, 2025</td>
                            <td><a href="#"
                                   class="btn btn-warning btn-sm">
                                    <i class="fa fa-exchange" aria-hidden="true"></i> Reallocate
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>$500</td>
                            <td>Apr 1, 2025</td>
                            <td><a href="#"
                                   class="btn btn-warning btn-sm">
                                    <i class="fa fa-exchange" aria-hidden="true"></i> Reallocate
                                </a>
                            </td>
                        </tr>
                        <!-- Add more rows as needed -->
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="1">Total</th>
                            <th colspan="2">$2000</th>
                        </tr>
                        </tfoot>
                    </table>
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
    </script>
@endpush




