<?php

use App\Models\Assessment;
use Carbon\Carbon;
use Livewire\Volt\Component;

use Illuminate\Support\Facades\Auth;
use App\Models\Enrollment;
use App\Models\AssessmentSubmission;
use App\Models\Payment;

new class extends Component {

    public $myCourses;
    public $myPayments;
    public $pendingAssessments;
    public $gradedAssessments;
    public $pendingAssignments;

    public function mount()
    {
        $student = auth()->user()->student;

        if (!$student) {
            // Initialize all props safely if no student record
            $this->myCourses = 0;
            $this->myPayments = 0;
            $this->pendingAssessments = 0;
            $this->gradedAssessments = 0;
            $this->pendingAssignments = collect(); // empty collection for consistency
            return;
        }

        // 1. My Courses
        $this->myCourses = Enrollment::where('student_id', $student->id)
            ->where('status', 'approved')
            ->count();

        // 2. My Payments
        $this->myPayments = Payment::whereHas('enrollment', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })->sum('amount');

        // 3. Pending Assessments
        $this->pendingAssessments = AssessmentSubmission::whereHas('enrollment', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })->where('status', 'pending')->count();

        // 4. Graded Assessments
        $this->gradedAssessments = AssessmentSubmission::whereHas('enrollment', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })->where('status', 'graded')->count();

        // 5. Pending Assignments with relationships
        $this->pendingAssignments = AssessmentSubmission::with([
            'assessment.intakeModule.module.course',
            'assessment.intakeModule.module.defaultLecturer',
        ])
            ->whereHas('enrollment', fn($q) => $q->where('student_id', $student->id))
            ->where('status', 'pending')
            ->get();
    }



}; ?>

@push('styles')
    <style>

        .department-overview-title {
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }

        .department-overview-card,
        .recent-activity-card {
            border-radius: 12px;
        }

        .department-overview-title, .map-title, .quick-actions-title {
            font-weight: bold;
            font-size: 18px;
            color: #000; /* black */
        }

        .recent-activity-title, .map-title, .quick-actions-title {
            font-weight: bold;
            font-size: 14px;
            color: #000; /* black */
        }

        .card-action {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            color: #000;
            background: #fff;
            border: 2px dotted #333; /* dotted border on small cards */
            border-radius: 8px;
            padding: 15px;
            transition: all 0.2s ease-in-out;
            font-weight: 500;
            height: 100%;
            text-align: center;
        }

        .card-action:hover {
            background: #f5f5f5;
            transform: scale(1.05);
        }

        .recent-activity-card {
            border-radius: 12px;
        }

        .activity-item {
            background: linear-gradient(135deg, #f9fbff, #eef4ff);
            border-radius: 12px;
            padding: 12px;
            transition: 0.2s;
            box-shadow: 0 2px 6px rgba(74, 108, 247, 0.08);
        }

        .activity-item:hover {
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            box-shadow: 0 4px 10px rgba(74, 108, 247, 0.15);
        }

        .icon-wrap {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dbe4ff, #edf2ff);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4a6cf7;
            font-size: 20px;
            box-shadow: 0 2px 5px rgba(74, 108, 247, 0.2);
        }

        .status {
            font-weight: 500;
            text-transform: lowercase;
        }

        .stat-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
        }

        .stat-text {
            text-align: left;
        }

        .stat-icon {
            font-size: 36px;
            padding: 12px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-blue {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .icon-green {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .icon-red {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .icon-orange {
            background: rgba(251, 146, 60, 0.1);
            color: #fb923c;
        }

    </style>
@endpush
<div class="row g-3">

    <!-- My Courses -->
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm h-100">
            <div class="stat-card d-flex justify-content-between align-items-center p-3">
                <div class="stat-text">
                    <h6 class="text-muted mb-1">My Courses</h6>
                    <h3 class="fw-bold text-dark">{{ $myCourses }}</h3>
                    <small class="text-muted">Currently Enrolled</small>
                </div>
                <div class="stat-icon" style="color:#0c314d;">
                    <span class="iconify" data-icon="mdi:book-education-outline"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Payments -->
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm h-100">
            <div class="stat-card d-flex justify-content-between align-items-center p-3">
                <div class="stat-text">
                    <h6 class="text-muted mb-1">My Payments</h6>
                    <h3 class="fw-bold" style="color:#f69121;">KES {{ number_format($myPayments) }}</h3>
                    <small class="text-muted">Total Paid</small>
                </div>
                <div class="stat-icon" style="color:#f69121;">
                    <span class="iconify" data-icon="mdi:credit-card-outline"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Assessments -->
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm h-100">
            <div class="stat-card d-flex justify-content-between align-items-center p-3">
                <div class="stat-text">
                    <h6 class="text-muted mb-1">Pending Assessments</h6>
                    <h3 class="fw-bold text-danger">{{ $pendingAssessments }}</h3>
                    <small class="text-muted">Awaiting Submission</small>
                </div>
                <div class="stat-icon" style="color:#dc3545;">
                    <span class="iconify" data-icon="mdi:clipboard-text-clock-outline"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Graded Assessments -->
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm h-100">
            <div class="stat-card d-flex justify-content-between align-items-center p-3">
                <div class="stat-text">
                    <h6 class="text-muted mb-1">Graded</h6>
                    <h3 class="fw-bold text-success">{{ $gradedAssessments }}</h3>
                    <small class="text-muted">With Feedback</small>
                </div>
                <div class="stat-icon" style="color:#198754;">
                    <span class="iconify" data-icon="mdi:check-decagram-outline"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">

        <!-- Upcoming Deadlines -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3" style="color:#20425b;">
                        <i class="ti ti-calendar-event text-warning"></i> Upcoming Deadlines
                    </h6>

                    @forelse($pendingAssignments->take(3) as $submission)
                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                            <div class="icon-wrap me-3" style="background:#20425b; color:#fff;">
                                <i class="ti ti-file-text"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong>{{ $submission->assessment->title }}</strong>
                                <div class="text-muted small">
                                    Due: {{ \Carbon\Carbon::parse($submission->assessment->due_on)->format('d M Y') }}
                                </div>
                            </div>
                            <span class="badge bg-warning text-dark">Pending</span>
                        </div>
                    @empty
                        <p class="text-muted text-center">No upcoming deadlines 🎉</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Grades -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3" style="color:#20425b;">
                        <i class="ti ti-star text-success"></i> Recent Grades
                    </h6>

                    @php
                        $student = auth()->user()->student;
                        $recentGraded = collect();

                        if ($student) {
                            $recentGraded = \App\Models\AssessmentSubmission::with('assessment.intakeModule.module')
                                ->whereHas('enrollment', fn($q) => $q->where('student_id', $student->id))
                                ->where('status', 'graded')
                                ->latest()
                                ->take(3)
                                ->get();
                        }
                    @endphp

                    @if($student)
                        @forelse($recentGraded as $submission)
                            <div class="d-flex justify-content-between mb-3 pb-2 border-bottom">
                                <div>
                                    <strong>{{ $submission->assessment->title }}</strong>
                                    <div class="text-muted small">
                                        {{ $submission->assessment->intakeModule->module->title ?? 'N/A' }}
                                    </div>
                                </div>
                                <span class="fw-bold" style="color:#f8a952;">
                {{ $submission->mark }}/{{ $submission->assessment->max_marks }}
            </span>
                            </div>
                        @empty
                            <p class="text-muted text-center">No graded assessments yet</p>
                        @endforelse
                    @else
                        <p class="text-muted text-center">Student profile not found.</p>
                    @endif

                </div>
            </div>
        </div>
    </div>


    <div class="col-lg-12 col-12">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="ti ti-file-description text-primary"></i> Pending Assignments
                        </h5>
                        <small class="text-muted">Assignments you need to submit</small>
                    </div>
                    <span class="fw-bold text-primary">{{ $pendingAssignments->count() }}</span>
                </div>

                @forelse ($pendingAssignments as $submission)
                    @php
                        $a = $submission->assessment;

                        $courseName = $a->intakeModule->module->course->title ?? 'N/A';
                        $moduleName = $a->intakeModule->module->title ?? 'N/A';
                        $lecturer = $a->intakeModule->module->defaultLecturer;
                        $lecturerName = $lecturer ? ($lecturer->first_name.' '.$lecturer->last_name) : 'N/A';
                        $due = $a->due_on ? Carbon::parse($a->due_on)->format('d M Y') : '—';
                        $status = $submission->status ?? 'pending';
                    @endphp

                    <div class="p-3 mb-2 rounded {{ $loop->odd ? 'bg-light' : 'bg-white' }}">
                        <h6 class="fw-bold text-dark mb-1">{{ $a->title }}</h6>

                        <div class="text-muted small mb-2">
                            <i class="ti ti-book text-primary"></i> {{ $courseName }} •
                            <i class="ti ti-layout text-success"></i> {{ $moduleName }} •
                            <i class="ti ti-user text-info"></i> {{ $lecturerName }}
                        </div>

                        <div class="d-flex justify-content-between align-items-center small">
                            <span><i class="ti ti-calendar text-secondary"></i> Due: {{ $due }}</span>
                            <span class="badge
                {{ $status === 'overdue' ? 'bg-danger' : ($status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                {{ ucfirst($status) }}
            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-muted my-3">No pending assignments 🎉</p>
                @endforelse


            </div>
        </div>
    </div>

</div>

@push('scripts')
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <script src="assets/js/apps/contact.js"></script>
@endpush




