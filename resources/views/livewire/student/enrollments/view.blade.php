<?php

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\IntakeModule;
use App\Models\Material;
use Livewire\Volt\Component;

new class extends Component {

    public $enrollment;
    public $activeCourseModules;
    public $intakeId;
    public $selectedModuleId;
    public $materials = [];
    public $assessments = [];

    public function mount($id)
    {
        $this->enrollment = Enrollment::with(['course', 'intake'])->findOrFail($id);

        $this->intakeId = $this->enrollment->intake->id;

        $this->activeCourseModules = $this->enrollment->intake
            ->modules()
            ->where('course_id', $this->enrollment->course->id)
            ->get();
    }


    public function selectMaterialModule($moduleId)
    {
        $this->selectedModuleId = $moduleId;
        $this->loadMaterials();
        $this->dispatch('show-material-offcanvas');

    }

    public function selectAssessmentModule($moduleId)
    {
        $this->selectedModuleId = $moduleId;
        $this->loadAssessments();
        $this->dispatch('show-assessment-offcanvas');
    }


    public function loadAssessments()
    {


        if ($this->getIntakeModuleId()) {
            $this->assessments = Assessment::where('intake_module_id', $this->getIntakeModuleId())
                ->orderByDesc('created_at')
                ->get();
        }
    }


    public function loadMaterials()
    {
        if ($this->getIntakeModuleId()) {
            $this->materials = Material::where('intake_module_id', $this->getIntakeModuleId())
                ->with('uploader')
                ->orderByDesc('created_at')
                ->get();
        }
    }


    public function getIntakeModuleId()
    {
        $intakeModule = IntakeModule::where('intake_id', $this->intakeId)
            ->where('module_id', $this->selectedModuleId)
            ->first();

        return $intakeModule->id;
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
            background-color: #f79020 !important; /* Match Apply Now */
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
                        <h4 class="mb-4 mb-sm-0 card-title">{{ $enrollment->course->title }}</h4>
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
                                <p class="mb-0 ">Attendance</p>
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
                            <span class="d-none d-md-block">Modules</span>
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

                <div class="row">
                    <div class="row">
                        <ul class="chat-users mh-n100" data-simplebar>
                            @forelse($activeCourseModules as $module)
                                <li>
                                    <a href="javascript:void(0)"
                                       class="px-4 py-3 bg-hover-light-black d-flex align-items-start chat-user bg-light-subtle">


                                        <div class="position-relative w-100 ms-2">
                                            <div
                                                class="d-flex align-items-center justify-content-between mb-2">
                                                <h6 class="mb-0">{{ $module->title }}</h6>
                                                <span
                                                    class="d-flex align-items-center gap-2">
                                                                                <span
                                                                                    class="badge bg-primary text-white">{{ $module->code }}</span>
                                                                             </span>
                                            </div>

                                            <h6 class="fw-semibold text-dark">
                                                Lecturer - Kevin Amayi Musungu
                                            </h6>

                                            <div
                                                class="d-flex align-items-center justify-content-start">
                                                <div class="d-flex align-items-center gap-2">

                                                    <div
                                                        class="rounded-1 text-bg-light d-flex align-items-center px-2 py-1 cursor-pointer"
                                                        wire:click="selectAssessmentModule({{ $module->id }})">
                                                        <img src="../assets/images/chat/icon-adobe.svg" alt="adobe-icon"
                                                             width="20" height="20" class="me-2">
                                                        <span>Assessments</span>
                                                    </div>

                                                    <div
                                                        class="rounded-1 text-bg-light d-flex align-items-center px-2 py-1 cursor-pointer"
                                                        wire:click="selectMaterialModule({{ $module->id }})">
                                                        <img src="../assets/images/chat/icon-zip-folder.svg"
                                                             alt="zip-icon" width="20" height="20" class="me-2">
                                                        <span>Materials</span>
                                                    </div>

                                                </div>


                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @empty
                                <li class="text-center py-4 text-muted">
                                    No modules found for this course.
                                </li>
                            @endforelse
                        </ul>
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
                                                                        <input type="text" id="c-name"
                                                                               class="form-control"
                                                                               placeholder="Name"/>
                                                                        <span
                                                                            class="validation-text text-danger"></span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="mb-3 contact-email">
                                                                        <input type="text" id="c-email"
                                                                               class="form-control"
                                                                               placeholder="Email"/>
                                                                        <span
                                                                            class="validation-text text-danger"></span>
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
                                                                               class="form-control"
                                                                               placeholder="Phone"/>
                                                                        <span
                                                                            class="validation-text text-danger"></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <div class="mb-3 contact-location">
                                                                        <input type="text" id="c-location"
                                                                               class="form-control"
                                                                               placeholder="Location"/>
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
                                                                <span class="usr-course-amount fs-3"
                                                                      data-amount="$1,500">$1,500</span>
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
                                                                <h6 class="user-name mb-0"
                                                                    data-name="React for Beginners">
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
                                                                <span class="usr-course-amount fs-3"
                                                                      data-amount="$1,000">$1,000</span>
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

    </div>

    <!-- 3 -->
    <div style="width: 80vw; max-width: 1000px;"
         class="offcanvas offcanvas-end"
         tabindex="-1"
         id="moduleMaterial"
         wire:ignore.self
         aria-labelledby="moduleMaterialLabel"
         data-bs-backdrop="static">

        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasExampleLabel">
                Module Materials
            </h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">

            <table class="table my-4 search-table align-middle text-nowrap">
                <thead class="header-item">
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Uploaded By</th>
                    <th>Uploaded On</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>

                @forelse ($materials as $material)
                    <tr class="search-items">
                        <td>{{ $material->title }}</td>
                        <td>{{ strtoupper($material->type) }}</td>
                        <td>{{ $material->uploader->name ?? 'Unknown' }}</td>
                        <td>{{ $material->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="action-btn">
                                <a href="{{ $material->type === 'video' ? $material->file_path : asset('storage/' . $material->file_path) }}"
                                   class="text-success" title="View/Preview" target="_blank">
                                    <i class="ti ti-eye fs-5"></i>
                                </a>
                                @if($material->type !== 'video')
                                    <a href="{{ asset('storage/' . $material->file_path) }}"
                                       class="text-primary ms-2" title="Download" download>
                                        <i class="ti ti-download fs-5"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No materials uploaded for this module.</td>
                    </tr>
                @endforelse
                </tbody>

            </table>

        </div>
    </div>

    <!-- 4 -->
    <div style="width: 80vw; max-width: 900px;"
         class="offcanvas offcanvas-end"
         tabindex="-1"
         id="moduleAssessments"
         aria-labelledby="moduleAssessmentsLabel"
         wire.ignore.self
         data-bs-backdrop="static">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="moduleAssessmentsLabel">Module Assessments</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">


            <div class="table-responsive">
                <table class="table table-striped align-middle text-nowrap">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Due Date</th>
                        <th>Max Marks</th>
                        <th>File</th>
                        <th>Uploaded On</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($assessments as $assessment)
                        <tr>
                            <td>{{ $assessment->title }}</td>
                            <td>{{ $assessment->type }}</td>
                            <td>{{ $assessment->due_on ? \Carbon\Carbon::parse($assessment->due_on)->format('d M Y') : '-' }}</td>
                            <td>{{ $assessment->max_marks }}</td>
                            <td>
                                @if ($assessment->file_path)
                                    <a href="{{ Storage::url($assessment->file_path) }}" target="_blank">
                                        {{ $assessment->original_name ?? 'Download' }}
                                    </a>
                                @else
                                    <span class="text-muted">No file</span>
                                @endif
                            </td>
                            <td>{{ $assessment->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="action-btn">
                                    @if($assessment->file_path)
                                        <a href="{{ $assessment->type === 'video' ? $assessment->file_path : asset('storage/' . $assessment->file_path) }}"
                                           class="text-success" title="View/Preview" target="_blank">
                                            <i class="ti ti-eye fs-5"></i>
                                        </a>
                                        <a href="{{ asset('storage/' . $assessment->file_path) }}"
                                           class="text-primary ms-2" title="Download" download>
                                            <i class="ti ti-download fs-5"></i>
                                        </a>
                                    @else
                                        #
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No assessments found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>
@push('scripts')
    <script>

        window.addEventListener('show-assessment-offcanvas', () => {
            const el = document.getElementById('moduleAssessments');
            const offcanvas = new bootstrap.Offcanvas(el);
            offcanvas.show();
        });

        window.addEventListener('show-material-offcanvas', () => {
            const el = document.getElementById('moduleMaterial');
            const offcanvas = new bootstrap.Offcanvas(el);
            offcanvas.show();
        });

        window.addEventListener('hide-material-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('materialUploaderModal'))?.hide();
        });

        window.addEventListener('hide-assessment-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('assessmentUploaderModal'))?.hide();
        });

    </script>

@endpush

