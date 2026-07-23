<?php

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\Enrollment;
use App\Models\IntakeModule;
use App\Models\Material;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {

    use WithFileUploads;

    public $enrollment;
    public $activeCourseModules;
    public $trimesterIds;
    public $selectedModuleId;
    public $materials = [];
    public $assessments = [];
    public $selectedAssessmentId;
    public $answerFile;

    public function mount($id)
    {
        $this->enrollment = Enrollment::with(['course'])->findOrFail($id);

        $this->trimesterIds = $this->enrollment->resolvedTrimesterIds();

        $this->activeCourseModules = \App\Models\Module::where('course_id', $this->enrollment->course->id)
            ->whereIn('id', function ($query) {
                $query->select('module_id')
                    ->from('intake_modules')
                    ->whereIn('trimester_id', $this->trimesterIds);
            })
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


    public function submitAssessment()
    {
        $this->validate([
            'answerFile' => 'required|file|max:10240',
        ]);

        $path = $this->answerFile->store('submissions', 'public');
        AssessmentSubmission::updateOrCreate(
            [
                'assessment_id' => $this->selectedAssessmentId,
                'enrollment_id' => $this->enrollment->id,
            ],
            [
                'file_path' => $path,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]
        );

        $this->dispatch('submission-success');
        LivewireAlert::text('Assessment submitted successfully.!')->success()->toast()->position('top-end')->show();
    }


    public function loadAssessments()
    {
        if ($this->getIntakeModuleId()) {
            $assessments = Assessment::where('intake_module_id', $this->getIntakeModuleId())
                ->orderByDesc('created_at')
                ->get();

            // Map submission status per assessment
            $this->assessments = $assessments->map(function ($assessment) {
                $submission = AssessmentSubmission::where('assessment_id', $assessment->id)
                    ->where('enrollment_id', $this->enrollment->id)
                    ->first();

                $assessment->submission_status = $submission?->status ?? 'not assigned'; // fallback
                $assessment->submission_id = $submission?->id;
                $assessment->submitted_at = $submission?->submitted_at;

                return $assessment;
            });
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
        $intakeModule = IntakeModule::whereIn('trimester_id', $this->trimesterIds)
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

        /* Print styling */
        @media print {
            @page {
                size: auto;
                margin: 0; /* no extra margin */
            }

            html, body {
                margin: 0 !important;
                padding: 0 !important;
                height: auto !important;
            }

            body * {
                visibility: hidden !important;
            }

            .receipt-print, .receipt-print * {
                visibility: visible !important;
            }

            .receipt-print {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: auto !important;
                margin: 0 !important;
                padding: 20mm !important; /* acts like page margin */
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
                            </div>
                            <div class="text-center">
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
                                <h5 class="mb-0">{{ Auth::user()->name }}</h5>
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
                                                Lecturer
                                                - {{ !empty($module->defaultLecturer) ? ucfirst($module->defaultLecturer->first_name).' '.ucfirst($module->defaultLecturer->last_name) : 'None' }}
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
                                                    <label class="form-check-label"
                                                           for="contact-check-all"></label>
                                                    <span class="new-control-indicator"></span>
                                                </div>
                                            </div>
                                        </th>
                                        <th>Course</th>
                                        <th>Total Paid</th>
                                        <th>Subpayments</th>
                                        <th>Remaining Balance</th>
                                        </thead>
                                        <tbody>
                                        @php
                                            $course = $enrollment->course;
                                            $modules = $course->modules;
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
                                                                {{ $enrollment->currentTrimesterLabel() }}
                                                            </small>
                                                            <span class="usr-course-amount fs-3"
                                                                  data-amount="">{{ 'Fee: KES ' }}{{ number_format($course->price, 2) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
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
    <div style="width: 80vw; max-width: 1250px;"
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
                        <th>Status</th>
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
                            <td>
                                @switch($assessment->submission_status)
                                    @case('pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                        @break

                                    @case('submitted')
                                        <span class="badge bg-info text-white">Submitted</span>
                                        @break

                                    @case('graded')
                                        <span class="badge bg-success">Graded</span>
                                        @break

                                    @default
                                        <span class="badge bg-secondary">Not Assigned</span>
                                @endswitch
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
                                    {{-- Submit Button --}}
                                    <button class="btn btn-sm btn-outline-primary ms-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#submitAssessmentModal"
                                            onclick="prepareAssessmentSubmission({{ $assessment->id }}, '{{ $assessment->title }}')">
                                        <i class="ti ti-upload"></i> Submit
                                    </button>
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


    <!-- Assessment Submission Modal -->
    <div wire:ignore class="modal fade" id="submitAssessmentModal" tabindex="-1"
         aria-labelledby="submitAssessmentModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form wire:submit.prevent="submitAssessment" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Submit Assessment: <span id="modalAssessmentTitle"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" wire:model="selectedAssessmentId">

                        <div class="mb-3">
                            <label for="answerFile" class="form-label">Upload Answer (PDF, DOCX, ZIP)</label>
                            <input type="file" class="form-control" wire:model="answerFile" id="answerFile" required>
                            @error('answerFile') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        @if ($answerFile)
                            <p class="text-success">Selected file: {{ $answerFile->getClientOriginalName() }}</p>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check"></i> Submit
                        </button>
                    </div>
                </div>
            </form>
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
                            <th>#</th>
                            <th>Reference</th>
                            <th>Transaction ID</th>
                            <th>Date</th>
                            <th>Payment Method</th>
                            <th>Amount</th>
                            <th>Paid By</th>
                             <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $totalPayments = 0;
                        @endphp
                        @foreach ($enrollment->payments as $payment)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $payment->reference ?? 'N/A' }}</td>
                                <td>{{ $payment->transaction_id ?? 'N/A' }}</td>
                                <td>{{ Carbon\Carbon::parse($payment->paid_at)->format('d/m/y h:i A') }}</td>
                                <td>{{ ucfirst($payment->payment_method) ?? 'N/A' }}</td>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->payer ?? 'N/A' }}</td>
                                <td>
                                    <button class="btn-print" onclick="printReceipt(
                    '{{ $payment->enrollment->course->title ?? 'N/A' }}',
                    '{{ number_format($payment->amount, 2) }}',
                    '{{ ucfirst($payment->payment_method) }}',
                    '{{ \Carbon\Carbon::parse($payment->paid_at)->format('d M Y') }}',
                    '{{ $payment->reference }}'
                )">
                                        <i class="ti ti-printer"></i> Print
                                    </button>
                                </td>
                            </tr>
                            @php
                                $totalPayments += $payment->amount;
                            @endphp
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


    <!-- Hidden Printable Receipt -->
    <div id="receipt" class="receipt-print">
        <div
            style="max-width: 600px; margin: auto; font-family: 'Segoe UI', sans-serif; padding: 40px; border: 1px solid #ddd; background-color: #fff;">

            <!-- Logo -->
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="assets/images/logos/tabor_logo.png" alt="Company Logo" style="height: 60px;">
                <h4 style="margin-top: 10px; color: #0e334e;">Payment Receipt</h4>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 2px solid #f69121;">

            <div style="margin-bottom: 20px;">
                <p><strong>Course Title:</strong> <span id="receipt-course"></span></p>
                <p><strong>Amount Paid:</strong> <span style="color: #0e334e;">KES <span
                            id="receipt-amount"></span></span></p>
                <p><strong>Payment Method:</strong> <span id="receipt-method"></span></p>
                <p><strong>Date Paid:</strong> <span id="receipt-date"></span></p>
                <p><strong>Reference:</strong> <span id="receipt-reference"></span></p>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 2px dashed #ccc;">

            <p style="text-align: center; font-size: 13px; color: #888; margin-top: 30px;">
                Thank you for your payment. This receipt serves as proof of payment.
            </p>

            <p style="text-align: center; font-size: 12px; color: #bbb; margin-top: 10px;">
                &copy; {{ date('Y') }} Tabor
            </p>
        </div>
    </div>

</div>
@push('scripts')
    <script>

        function prepareAssessmentSubmission(assessmentId, assessmentTitle) {
        @this.set('selectedAssessmentId', assessmentId)
            ;
            document.getElementById('modalAssessmentTitle').innerText = assessmentTitle;
        }

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

        window.addEventListener('submission-success', () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('submitAssessmentModal'));
            if (modal) modal.hide();
        });

        function printReceipt(course, amount, method, date, reference) {
            // Fill receipt
            document.getElementById('receipt-course').innerText = course;
            document.getElementById('receipt-amount').innerText = amount;
            document.getElementById('receipt-method').innerText = method;
            document.getElementById('receipt-date').innerText = date;
            document.getElementById('receipt-reference').innerText = reference;

            // Delay print to allow DOM to update
            setTimeout(() => {
                window.print();
            }, 500); // 300ms is usually enough
        }

    </script>

@endpush

