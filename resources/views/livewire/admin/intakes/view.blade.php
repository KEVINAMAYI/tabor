<?php

use App\Models\Course;
use App\Models\Student;
use App\Models\Intake;
use App\Models\IntakeModule;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

new class extends Component {

    /* ---------- public state ---------- */
    public $courses;           // collection of Course
    public $intakeId;           // collection of Course
    public $modules = [];      // collection of Module (for the chosen course)
    public $intakeCourses = [];
    public $intakeStudents = [];
    public $activeCourseModules = [];
    public $activeCourseId;
    public $activeStudentId;
    public $activeStudent;

    public $selectedCourse = '';  // <select> binding
    public $selected = [];        // row checkboxes
    public $selectAll = false;    // header checkbox

    /* ---------- mount ---------- */
    public function mount($intake_id)
    {
        $this->intakeCourses = IntakeModule::coursesForIntake($intake_id);
        $this->intakeStudents = Intake::with('students')->findOrFail($intake_id)->students;
        $this->intakeId = $intake_id;
        $this->courses = Course::orderBy('title')->get(['id', 'title']);
        $this->activeCourseId = $this->courses[0]->id ?? '';
        $this->activeStudentId = $this->intakeStudents[0]->id ?? '';
        $this->selectCourse($this->activeCourseId);
        $this->selectStudent($this->activeStudentId);
    }


    #[On('update-selected-course')]
    public function setSelectedCourse(): void
    {
        $this->selectAll = count($this->selected) === $this->modules->count();
    }


    public function selectCourse($courseId)
    {
        $this->activeCourseId = $courseId;
        $this->activeCourseModules = IntakeModule::getModulesForIntakeCourse($this->intakeId, $courseId);
    }


    public function selectStudent($studentId)
    {
        if (!empty($studentId)) {
            $this->activeStudentId = $studentId;
            $this->activeStudent = Student::with([
                'enrollments.course.modules',
                'enrollments.intake',
            ])->findOrFail($studentId);
        }

        return;
    }


    /* ---------- watchers ---------- */
    #[On('update-course')]
    public function updateCourse($courseId)
    {
        $this->modules = Course::with('modules')
            ->find($courseId)?->modules ?? collect();

        $this->selected = [];
        $this->selectAll = false;
    }

    #[On('select-all-courses')]
    public function selectAllModules()
    {
        if ($this->selectAll) {
            $this->selected = $this->modules->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selected = [];
        }
    }


    public function addModulesToIntake(): void
    {

        if (empty($this->selected)) {
            $this->dispatch('toast', title: 'Nothing selected', type: 'warning');
            return;
        }

        $intake = Intake::findOrFail($this->intakeId);
        $intake->modules()->syncWithoutDetaching($this->selected);

        $this->reset('selected', 'selectAll');
        $this->dispatch('hide-course-modal');

    }


}; ?>

<div class="col-12">
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">January 2025 Intake</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">
                        <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                          January 2025 Intake
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
                                <h4 class="mb-0 fw-semibold lh-1">100</h4>
                                <p class="mb-0 ">Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mt-n3 order-lg-2 order-1">
                        <div class="mt-n5">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="d-flex align-items-center justify-content-center round-110">
                                    <div
                                        class="border border-4 border-white d-flex align-items-center justify-content-center rounded-circle overflow-hidden"
                                        style="background-color: #f0f0f0; width: 100px; height: 100px;">
                                        <i class="ti ti-calendar-event text-primary"
                                           style="font-size: 3rem; line-height:1;"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center">
                                <h5 class="mb-0">January 2025 Intake</h5>
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
                            <i class="ti ti-book fs-5"></i>
                            <span class="d-none d-md-block">Courses</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link hstack gap-2 rounded-0 fs-12 py-6" id="pills-students-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-students" type="button" role="tab"
                                aria-controls="pills-students" aria-selected="false">
                            <i class="ti ti-calendar-check fs-5"></i>
                            <span class="d-none d-md-block">Students</span>
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
                 aria-labelledby="pills-courses-tab"
                 tabindex="0">
                <div class="card overflow-hidden chat-application">
                    <div class="d-flex align-items-center justify-content-between gap-6 m-3 d-lg-none">
                        <button class="btn btn-primary d-flex" type="button" data-bs-toggle="offcanvas"
                                data-bs-target="#chat-sidebar" aria-controls="chat-sidebar">
                            <i class="ti ti-menu-2 fs-5"></i>
                        </button>
                        <form class="position-relative w-100">
                            <input type="text" class="form-control search-chat py-2 ps-5" id="text-srh"
                                   placeholder="Search Contact">
                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div class="d-flex w-100">
                        <div class="d-flex w-100">
                            <div class="min-width-340">
                                <div class="border-end user-chat-box h-100">
                                    <div class="px-4 pt-9 pb-6 d-none d-lg-block">
                                        <form class="position-relative">
                                            <input type="text" class="form-control search-chat py-2 ps-5" id="text-srh"
                                                   placeholder="Search"/>
                                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                                        </form>
                                    </div>
                                    <div class="app-chat">
                                        <ul class="chat-users mh-n100" data-simplebar>
                                            @forelse ($intakeCourses as $course)
                                                <li>
                                                    <a href="javascript:void(0)"
                                                       class="px-4 py-3 bg-hover-light-black d-flex align-items-center chat-user
                                                       {{ $activeCourseId === $course->id ? 'bg-light-subtle' : '' }}"
                                                       id="chat_user_{{ $course->id }}"
                                                       data-user-id="{{ $course->id }}"
                                                       wire:click="selectCourse({{ $course->id }})"
                                                       wire:key="course-{{ $course->id }}">
                                                        <div class="ms-6 d-inline-block w-75">
                                                            <h6 class="mb-1 fw-semibold chat-title"
                                                                data-username="{{ $course->title }}">
                                                                {{ $course->title }}
                                                            </h6>
                                                        </div>
                                                    </a>
                                                </li>
                                            @empty
                                                <li class="text-center py-4 text-muted">
                                                    No courses assigned to this intake yet.
                                                </li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="w-100">
                                <div class="chat-container h-100 w-100">
                                    <div class="chat-box-inner-part h-100">
                                        <div class="chatting-box app-email-chatting-box">
                                            <div
                                                class="p-9 py-3 border-bottom chat-meta-user d-flex align-items-center justify-content-between">
                                                <h5 class="text-dark mb-0 fs-5">Modules</h5>
                                                <a href="javascript:void(0)"
                                                   wire:click="$dispatch('show-course-modal')"
                                                   class="btn btn-primary d-flex align-items-center">
                                                    <i class="ti ti-layout-grid text-white me-1 fs-5"></i> Manage
                                                    Modules
                                                </a>
                                            </div>
                                            <div class="position-relative overflow-hidden">
                                                <div class="position-relative">
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
                                                                                <div class="rounded-1 text-bg-light"
                                                                                     data-bs-toggle="offcanvas"
                                                                                     data-bs-target="#moduleAssessments"
                                                                                     aria-controls="moduleAssessments">
                                                                                    <img
                                                                                        src="../assets/images/chat/icon-adobe.svg"
                                                                                        alt="adobe-icon" width="20"
                                                                                        height="20"/>
                                                                                </div>

                                                                                <div
                                                                                    class="rounded-1 text-bg-light mx-2"
                                                                                    data-bs-toggle="offcanvas"
                                                                                    data-bs-target="#moduleMaterial"
                                                                                    aria-controls="moduleMaterial">
                                                                                    <img
                                                                                        src="../assets/images/chat/icon-zip-folder.svg"
                                                                                        alt="zip-icon" width="20"
                                                                                        height="20"/>
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="offcanvas offcanvas-start user-chat-box" tabindex="-1" id="chat-sidebar"
                             aria-labelledby="offcanvasExampleLabel">
                            <div class="offcanvas-header">
                                <h5 class="offcanvas-title" id="offcanvasExampleLabel"> Contact </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                        aria-label="Close"></button>
                            </div>
                            <div class="px-9 pt-4 pb-3">
                                <button class="btn btn-primary fw-semibold py-8 w-100">Add New Contact</button>
                            </div>
                            <ul class="list-group h-n150" data-simplebar>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-inbox fs-5"></i>All Contacts
                                    </a>
                                </li>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-star"></i>Starred
                                    </a>
                                </li>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-file-text fs-5"></i>Pending Approval
                                    </a>
                                </li>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-alert-circle"></i>Blocked
                                    </a>
                                </li>
                                <li class="border-bottom my-3"></li>
                                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">CATEGORIES</li>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-bookmark fs-5 text-primary"></i>Engineers
                                    </a>
                                </li>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-bookmark fs-5 text-warning"></i>Support Staff
                                    </a>
                                </li>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-bookmark fs-5 text-success"></i>Sales Team
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
            <div class="tab-pane fade" id="pills-students" role="tabpanel" aria-labelledby="pills-students-tab"
                 tabindex="0">
                <div class="card overflow-hidden chat-application">
                    <div class="d-flex align-items-center justify-content-between gap-6 m-3 d-lg-none">
                        <button class="btn btn-primary d-flex" type="button" data-bs-toggle="offcanvas"
                                data-bs-target="#chat-sidebar" aria-controls="chat-sidebar">
                            <i class="ti ti-menu-2 fs-5"></i>
                        </button>
                        <form class="position-relative w-100">
                            <input type="text" class="form-control search-chat py-2 ps-5" id="text-srh"
                                   placeholder="Search Contact">
                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div class="d-flex w-100">
                        <div class="d-flex w-100">
                            <div class="min-width-340">
                                <div class="border-end user-chat-box h-100">
                                    <div class="px-4 pt-9 pb-6 d-none d-lg-block">
                                        <form class="position-relative">
                                            <input type="text" class="form-control search-chat py-2 ps-5" id="text-srh"
                                                   placeholder="Search"/>
                                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                                        </form>
                                    </div>
                                    <div class="app-chat">
                                        <ul class="chat-users mh-n100" data-simplebar>
                                            @foreach($intakeStudents as $student)
                                                @php
                                                    $initial = strtoupper(substr($student->first_name, 0, 1));
                                                    $colors = ['#FF6B6B', '#6BCB77', '#4D96FF', '#FFB562', '#A66DD4', '#00C1D4'];
                                                    $bgColor = $colors[$loop->index % count($colors)];
                                                @endphp

                                                <li>
                                                    <a href="javascript:void(0)"
                                                       wire:click="selectStudent({{ $student->id }})"
                                                       class="px-4 py-3 d-flex align-items-center chat-user
                                                       {{ $activeStudentId === $student->id ? 'bg-light-subtle' : 'bg-hover-light-black' }}"
                                                       id="chat_user_{{ $student->id }}"
                                                       data-user-id="{{ $student->id }}">
                                                       <span class="position-relative">
                                                      <div
                                                          class="rounded-circle d-flex justify-content-center align-items-center"
                                                          style="background-color: {{ $bgColor }}; width: 40px; height: 40px; color: white; font-weight: bold;">
                                                      {{ $initial }}
                                                      </div>
                                                       </span>
                                                        <div class="ms-6 d-inline-block w-75">
                                                            <h6 class="mb-1 fw-semibold chat-title"
                                                                data-username="{{ $student->first_name.' '.$student->last_name }}">
                                                                {{ $student->first_name.' '.$student->last_name }}
                                                            </h6>
                                                            <span
                                                                class="fs-2 text-body-color d-block">{{ $student->email }}</span>
                                                        </div>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>

                                    </div>
                                </div>
                            </div>
                            <div class="w-100">
                                <div class="chat-container h-100 w-100">
                                    <div class="chat-box-inner-part h-100">
                                        <div class="chatting-box app-email-chatting-box">
                                            <div
                                                class="p-9 py-3 border-bottom chat-meta-user d-flex align-items-center justify-content-between">
                                                <h5 class="text-dark mb-0 fs-5">Student Details</h5>
                                            </div>

                                            <div class="position-relative overflow-hidden">
                                                <div class="position-relative">
                                                    <div class="chat-box email-box mh-n100 p-9" data-simplebar="init">
                                                        @if($activeStudent)
                                                            <div class="chat-list chat active-chat"
                                                                 data-user-id="{{ $activeStudent->id }}">
                                                                <div
                                                                    class="hstack align-items-start mb-7 pb-1 align-items-center justify-content-between">
                                                                    <div class="d-flex align-items-center gap-3">
                                                                        {{-- Circle avatar with initial --}}
                                                                        @php
                                                                            $initial = strtoupper(substr($activeStudent->first_name, 0, 1));
                                                                            $colors = ['#FF6B6B', '#6BCB77', '#4D96FF', '#FFB562', '#A66DD4', '#00C1D4'];
                                                                            $bgColor = $colors[$activeStudent->id % count($colors)];
                                                                        @endphp
                                                                        <div
                                                                            class="rounded-circle d-flex justify-content-center align-items-center"
                                                                            style="background-color: {{ $bgColor }}; width: 72px; height: 72px; color: white; font-weight: bold; font-size: 28px;">
                                                                            {{ $initial }}
                                                                        </div>
                                                                        <div>
                                                                            <h6 class="fw-semibold fs-4 mb-0">
                                                                                {{ $activeStudent->first_name }} {{ $activeStudent->last_name }}
                                                                            </h6>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-4 mb-7">
                                                                        <p class="mb-1 fs-2">Phone number</p>
                                                                        <h6 class="fw-semibold mb-0">
                                                                            {{ $activeStudent->phone ?? 'N/A' }}
                                                                        </h6>
                                                                    </div>
                                                                    <div class="col-8 mb-7">
                                                                        <p class="mb-1 fs-2">Email address</p>
                                                                        <h6 class="fw-semibold mb-0">
                                                                            {{ $activeStudent->email ?? 'N/A' }}
                                                                        </h6>
                                                                    </div>
                                                                    <div class="col-12 mb-9">
                                                                        <p class="mb-1 fs-2">Date of Birth</p>
                                                                        <h6 class="fw-semibold mb-0">
                                                                            {{ $activeStudent->dob ? \Carbon\Carbon::parse($activeStudent->dob)->format('d M Y') : 'N/A' }}
                                                                        </h6>
                                                                    </div>
                                                                </div>

                                                                <div class="d-flex align-items-center gap-6">
                                                                    <button class="btn btn-primary">View</button>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="text-center text-muted p-5">
                                                                <em>Select a student to view details.</em>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="offcanvas offcanvas-start user-chat-box" tabindex="-1" id="chat-sidebar"
                             aria-labelledby="offcanvasExampleLabel">
                            <div class="offcanvas-header">
                                <h5 class="offcanvas-title" id="offcanvasExampleLabel"> Contact </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                        aria-label="Close"></button>
                            </div>
                            <div class="px-9 pt-4 pb-3">
                                <button class="btn btn-primary fw-semibold py-8 w-100">Add New Contact</button>
                            </div>
                            <ul class="list-group h-n150" data-simplebar>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-inbox fs-5"></i>All Contacts
                                    </a>
                                </li>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-star"></i>Starred
                                    </a>
                                </li>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-file-text fs-5"></i>Pening Approval
                                    </a>
                                </li>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-alert-circle"></i>Blocked
                                    </a>
                                </li>
                                <li class="border-bottom my-3"></li>
                                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">CATEGORIES</li>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-bookmark fs-5 text-primary"></i>Engineers
                                    </a>
                                </li>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-bookmark fs-5 text-warning"></i>Support Staff
                                    </a>
                                </li>
                                <li class="list-group-item border-0 p-0 mx-9">
                                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                                       href="javascript:void(0)">
                                        <i class="ti ti-bookmark fs-5 text-success"></i>Sales Team
                                    </a>
                                </li>
                            </ul>
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
                                                   id="input-search" placeholder="Search Contacts..."/>
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
                                            <i class="ti ti-users text-white me-1 fs-5"></i> Add Contact
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
                                        <tr>
                                            <th>
                                                <div class="form-check text-center">
                                                    <input type="checkbox" class="form-check-input primary"/>
                                                </div>
                                            </th>
                                            <th>Course</th>
                                            <th>Student</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Paid On</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <!-- Row 1 -->
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input"/>
                                            </td>
                                            <td><span class="badge bg-light text-dark">Web Development</span></td>
                                            <td>
                                                <div class="user-meta-info">
                                                    <h6 class="user-name mb-0">John Doe</h6>
                                                    <span class="user-work fs-3">TX12345</span>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-secondary">KES 5,000</span></td>
                                            <td><span class="badge bg-warning text-dark">M-Pesa</span></td>
                                            <td><span class="badge bg-success-subtle text-success">Completed</span></td>
                                            <td>2024-05-01</td>
                                            <td><a href="#"
                                                   class="btn btn-info btn-sm">
                                                    <i class="fa fa-exchange" aria-hidden="true"></i> Reallocate
                                                </a>
                                            </td>
                                        </tr>
                                        <!-- Row 2 -->
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input"/>
                                            </td>
                                            <td><span class="badge bg-light text-dark">Graphic Design</span></td>
                                            <td>
                                                <div class="user-meta-info">
                                                    <h6 class="user-name mb-0">Jane Smith</h6>
                                                    <span class="user-work fs-3">TX12346</span>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-secondary">KES 3,200</span></td>
                                            <td><span class="badge bg-primary-subtle text-primary">Bank</span></td>
                                            <td><span class="badge bg-warning-subtle text-warning">Pending</span></td>
                                            <td>2024-05-03</td>
                                            <td><a href="#"
                                                   class="btn btn-info btn-sm">
                                                    <i class="fa fa-exchange" aria-hidden="true"></i> Reallocate
                                                </a>
                                            </td>
                                        </tr>
                                        <!-- Row 3 -->
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input"/>
                                            </td>
                                            <td><span class="badge bg-light text-dark">Data Science</span></td>
                                            <td>
                                                <div class="user-meta-info">
                                                    <h6 class="user-name mb-0">Ali Mwana</h6>
                                                    <span class="user-work fs-3">TX12347</span>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-secondary">KES 1,800</span></td>
                                            <td><span class="badge bg-info-subtle text-info">Cash</span></td>
                                            <td><span class="badge bg-success-subtle text-success">Completed</span></td>
                                            <td>2024-05-05</td>
                                            <td><a href="#"
                                                   class="btn btn-info btn-sm">
                                                    <i class="fa fa-exchange" aria-hidden="true"></i> Reallocate
                                                </a>
                                            </td>
                                        </tr>
                                        <!-- Row 4 -->
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input"/>
                                            </td>
                                            <td><span class="badge bg-light text-dark">Cybersecurity</span></td>
                                            <td>
                                                <div class="user-meta-info">
                                                    <h6 class="user-name mb-0">Mary Njoki</h6>
                                                    <span class="user-work fs-3">TX12348</span>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-secondary">KES 4,000</span></td>
                                            <td><span class="badge bg-danger-subtle text-danger">Card</span></td>
                                            <td><span class="badge bg-danger-subtle text-danger">Failed</span></td>
                                            <td>2024-05-07</td>
                                            <td><a href="#"
                                                   class="btn btn-info btn-sm">
                                                    <i class="fa fa-exchange" aria-hidden="true"></i> Reallocate
                                                </a>
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

    <!-- ========= Manage Modules   (NEW) ===================================== -->
    <div class="modal fade" id="manageModulesModal" tabindex="-1" role="dialog"
         aria-labelledby="manageModulesModalTitle" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header d-flex align-items-center">
                    <h5 class="modal-title" id="manageModulesModalTitle">Manage Modules</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <!-- ⚙️ Livewire form -->
                <form wire:submit.prevent="addModulesToIntake">
                    <div class="modal-body">
                        <div class="row gy-3">
                            <!-- SINGLE‑SELECT ▸ Courses -->
                            <div class="col-12">
                                <label class="form-label fw-bold mb-1">Course</label>
                                <select wire:change="$dispatch('update-course', { courseId: $event.target.value })"
                                        class="form-select" wire:model="selectedCourse">
                                    <option value="">— choose course —</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- MODULE TABLE (fills once a course is picked) -->
                            <div class="col-12" wire:key="modules-table">
                                <table class="table search-table align-middle text-nowrap">
                                    <thead class="header-item">
                                    <tr>
                                        <th>
                                            <div class="form-check text-center">
                                                <input wire:click="$dispatch('select-all-courses')" type="checkbox"
                                                       class="form-check-input"
                                                       wire:model="selectAll"/>
                                            </div>
                                        </th>
                                        <th>Title</th>
                                        <th>Code</th>
                                        <th>Description</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    @forelse ($modules as $module)
                                        <tr wire:key="module-{{ $module->id }}">
                                            <td>
                                                <div class="form-check text-center">
                                                    <input wire:click="$dispatch('update-selected-course')"
                                                           type="checkbox" class="form-check-input"
                                                           wire:model="selected" value="{{ $module->id }}"/>
                                                </div>
                                            </td>
                                            <td>{{ $module->title }}</td>
                                            <td>{{ strtoupper($module->code) }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($module->description, 60) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Pick a course to see its
                                                modules
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <div class="d-flex gap-3 m-0">
                            <button type="submit" class="btn btn-success">Add to Intake</button>
                            <button type="button" class="btn bg-danger-subtle text-danger"
                                    data-bs-dismiss="modal">Discard
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- ===================================================================== -->

    <!-- 3 -->
    <div style="width: 80vw; max-width: 1000px;" class="offcanvas offcanvas-end" tabindex="-1" id="moduleMaterial"
         aria-labelledby="moduleMaterialLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasExampleLabel">
                Module Materials
            </h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <table class="table search-table align-middle text-nowrap">
                <thead class="header-item">
                <tr>
                    <th>
                        <input type="checkbox" class="form-check-input text-center">
                    </th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Uploaded By</th>
                    <th>Uploaded On</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <tr class="search-items">
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input">
                    </td>
                    <td>Introduction to OOP - Lecture Notes</td>
                    <td>PDF</td>
                    <td>Dr. John Doe</td>
                    <td>01 Jul 2025</td>
                    <td>
                        <div class="action-btn">
                            <a href="#" class="text-success" title="View/Preview">
                                <i class="ti ti-eye fs-5"></i>
                            </a>
                            <a href="#" class="text-primary ms-2" title="Download">
                                <i class="ti ti-download fs-5"></i>
                            </a>
                            <a href="#" class="text-warning ms-2" title="Edit Metadata">
                                <i class="ti ti-pencil fs-5"></i>
                            </a>
                            <a href="#" class="text-danger ms-2" title="Delete">
                                <i class="ti ti-trash fs-5"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <tr class="search-items">
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input">
                    </td>
                    <td>Week 1 Tutorial - Video Walkthrough</td>
                    <td>Video (MP4)</td>
                    <td>Prof. Alice Smith</td>
                    <td>30 Jun 2025</td>
                    <td>
                        <div class="action-btn">
                            <a href="#" class="text-success" title="Play Video">
                                <i class="ti ti-player-play fs-5"></i>
                            </a>
                            <a href="#" class="text-primary ms-2" title="Download">
                                <i class="ti ti-download fs-5"></i>
                            </a>
                            <a href="#" class="text-warning ms-2" title="Edit Details">
                                <i class="ti ti-pencil fs-5"></i>
                            </a>
                            <a href="#" class="text-danger ms-2" title="Delete">
                                <i class="ti ti-trash fs-5"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <tr class="search-items">
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input">
                    </td>
                    <td>Data Structures - Slides</td>
                    <td>PPT</td>
                    <td>Dr. Jane Kim</td>
                    <td>25 Jun 2025</td>
                    <td>
                        <div class="action-btn">
                            <a href="#" class="text-success" title="View Slides">
                                <i class="ti ti-eye fs-5"></i>
                            </a>
                            <a href="#" class="text-primary ms-2" title="Download">
                                <i class="ti ti-download fs-5"></i>
                            </a>
                            <a href="#" class="text-warning ms-2" title="Edit">
                                <i class="ti ti-pencil fs-5"></i>
                            </a>
                            <a href="#" class="text-danger ms-2" title="Delete">
                                <i class="ti ti-trash fs-5"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4 -->
    <div style="width: 80vw; max-width: 900px;" class="offcanvas offcanvas-end" tabindex="-1" id="moduleAssessments"
         aria-labelledby="moduleAssessmentsLabel">
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
                        <th>Due Date</th>
                        <th>Marks</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Assignment 1: Intro to Python</td>
                        <td>10 Jul 2025</td>
                        <td>20</td>
                        <td>Assignment</td>
                        <td><span class="badge bg-success">Published</span></td>
                        <td>
                            <div class="action-btn">
                                <a href="#" class="text-success" title="View">
                                    <i class="ti ti-eye fs-5"></i>
                                </a>
                                <a href="#" class="text-warning ms-2" title="Edit">
                                    <i class="ti ti-pencil fs-5"></i>
                                </a>
                                <a href="#" class="text-danger ms-2" title="Delete">
                                    <i class="ti ti-trash fs-5"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>Quiz 1: Basics</td>
                        <td>15 Jul 2025</td>
                        <td>10</td>
                        <td>Quiz</td>
                        <td><span class="badge bg-secondary">Draft</span></td>
                        <td>
                            <div class="action-btn">
                                <a href="#" class="text-success" title="View">
                                    <i class="ti ti-eye fs-5"></i>
                                </a>
                                <a href="#" class="text-warning ms-2" title="Edit">
                                    <i class="ti ti-pencil fs-5"></i>
                                </a>
                                <a href="#" class="text-danger ms-2" title="Delete">
                                    <i class="ti ti-trash fs-5"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>Final Exam</td>
                        <td>25 Jul 2025</td>
                        <td>60</td>
                        <td>Exam</td>
                        <td><span class="badge bg-success">Published</span></td>
                        <td>
                            <div class="action-btn">
                                <a href="#" class="text-success" title="View">
                                    <i class="ti ti-eye fs-5"></i>
                                </a>
                                <a href="#" class="text-warning ms-2" title="Edit">
                                    <i class="ti ti-pencil fs-5"></i>
                                </a>
                                <a href="#" class="text-danger ms-2" title="Delete">
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
@push('scripts')
    <script>
        window.addEventListener('show-course-modal', () => {
            console.log('test');
            new bootstrap.Modal(document.getElementById('manageModulesModal')).show();
        });

        window.addEventListener('hide-course-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('manageModulesModal'))?.hide();
        });
    </script>

@endpush


