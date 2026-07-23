<?php

use Livewire\Volt\Component;
use App\Models\AssessmentSubmission;
use App\Models\ModuleSession;

new class extends Component {

    public $lecturer;
    public $intakeModules;
    public $totalStudents;
    public $ungradedCount;
    public $upcomingSessions;
    public $recentUngraded;

    public function mount()
    {
        $this->lecturer = auth()->user()->lecturer;

        if (!$this->lecturer) {
            $this->intakeModules  = collect();
            $this->totalStudents  = 0;
            $this->ungradedCount  = 0;
            $this->upcomingSessions = collect();
            $this->recentUngraded = collect();
            return;
        }

        $this->intakeModules = $this->lecturer->intakeModules()
            ->with(['module', 'trimester'])
            ->get();

        $intakeModuleIds = $this->intakeModules->pluck('id');

        // Unique enrolled students across all modules
        $this->totalStudents = \App\Models\Enrollment::forAnyTrimester($this->intakeModules->pluck('trimester_id'))
            ->distinct('student_id')
            ->count('student_id');

        // Submissions waiting to be graded
        $this->ungradedCount = AssessmentSubmission::whereHas(
            'assessment',
            fn($q) => $q->whereIn('intake_module_id', $intakeModuleIds)
        )->whereNull('graded_at')->count();

        // Upcoming sessions (next 7 days)
        $this->upcomingSessions = ModuleSession::whereIn('intake_module_id', $intakeModuleIds)
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addDays(7))
            ->with('intakeModule.module')
            ->orderBy('starts_at')
            ->take(5)
            ->get();

        // Recent submissions needing grading
        $this->recentUngraded = AssessmentSubmission::whereHas(
            'assessment',
            fn($q) => $q->whereIn('intake_module_id', $intakeModuleIds)
        )
            ->whereNull('graded_at')
            ->whereNotNull('submitted_at')
            ->with(['assessment.intakeModule.module', 'enrollment.student.user'])
            ->latest('submitted_at')
            ->take(8)
            ->get();
    }

}; ?>

<div class="row g-3">

    {{-- Stat Cards --}}
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                    <div class="text-muted small mb-1">My Modules</div>
                    <div class="h3 fw-bold mb-0">{{ $intakeModules->count() }}</div>
                </div>
                <div style="width:48px;height:48px;border-radius:50%;background:rgba(13,110,253,.1);display:flex;align-items:center;justify-content:center;font-size:22px;color:#0d6efd;">
                    <iconify-icon icon="solar:book-2-bold-duotone"></iconify-icon>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="card shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                    <div class="text-muted small mb-1">Students</div>
                    <div class="h3 fw-bold mb-0">{{ $totalStudents }}</div>
                </div>
                <div style="width:48px;height:48px;border-radius:50%;background:rgba(25,135,84,.1);display:flex;align-items:center;justify-content:center;font-size:22px;color:#198754;">
                    <iconify-icon icon="solar:users-group-two-rounded-bold-duotone"></iconify-icon>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="card shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                    <div class="text-muted small mb-1">To Grade</div>
                    <div class="h3 fw-bold mb-0 text-danger">{{ $ungradedCount }}</div>
                </div>
                <div style="width:48px;height:48px;border-radius:50%;background:rgba(220,53,69,.1);display:flex;align-items:center;justify-content:center;font-size:22px;color:#dc3545;">
                    <iconify-icon icon="solar:clipboard-text-bold-duotone"></iconify-icon>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="card shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                    <div class="text-muted small mb-1">Sessions This Week</div>
                    <div class="h3 fw-bold mb-0 text-warning">{{ $upcomingSessions->count() }}</div>
                </div>
                <div style="width:48px;height:48px;border-radius:50%;background:rgba(255,193,7,.1);display:flex;align-items:center;justify-content:center;font-size:22px;color:#ffc107;">
                    <iconify-icon icon="solar:calendar-bold-duotone"></iconify-icon>
                </div>
            </div>
        </div>
    </div>

    {{-- My Modules --}}
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">My Modules</h6>
                @forelse($intakeModules as $im)
                    <a href="{{ route('lecturer.module.view', $im->id) }}"
                       class="d-flex align-items-center p-3 mb-2 rounded border text-decoration-none text-dark"
                       style="transition:.2s;background:#f8f9fa;" onmouseover="this.style.background='#e9ecef'" onmouseout="this.style.background='#f8f9fa'">
                        <div style="width:44px;height:44px;border-radius:10px;background:#0c314d;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;flex-shrink:0;">
                            <iconify-icon icon="solar:book-bold-duotone"></iconify-icon>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="fw-semibold">{{ $im->module->title ?? 'Unnamed Module' }}</div>
                            <div class="text-muted small">{{ $im->trimester?->name }} {{ $im->trimester?->academicYear?->name }}</div>
                        </div>
                        <iconify-icon icon="solar:arrow-right-linear" class="text-muted"></iconify-icon>
                    </a>
                @empty
                    <p class="text-muted text-center py-4">No modules assigned yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Upcoming Sessions --}}
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Sessions This Week</h6>
                @forelse($upcomingSessions as $session)
                    <div class="d-flex align-items-start mb-3 pb-2 border-bottom">
                        <div class="text-center me-3" style="min-width:44px;">
                            <div class="fw-bold" style="font-size:20px;color:#0c314d;line-height:1;">
                                {{ $session->starts_at->format('d') }}
                            </div>
                            <div class="text-muted" style="font-size:11px;">
                                {{ $session->starts_at->format('M') }}
                            </div>
                        </div>
                        <div>
                            <div class="fw-semibold small">{{ $session->intakeModule->module->title ?? 'N/A' }}</div>
                            <div class="text-muted" style="font-size:11px;">
                                {{ $session->starts_at->format('H:i') }}
                                @if($session->ends_at) – {{ $session->ends_at->format('H:i') }} @endif
                                @if($session->room) · {{ $session->room }} @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-3">No sessions this week.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Submissions to Grade --}}
    @if($recentUngraded->isNotEmpty())
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Submissions Awaiting Grading</h6>
                    <span class="badge bg-danger">{{ $ungradedCount }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Assessment</th>
                                <th>Module</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentUngraded as $sub)
                            <tr>
                                <td>{{ $sub->enrollment->student->user->name ?? '—' }}</td>
                                <td>{{ $sub->assessment->title ?? '—' }}</td>
                                <td>{{ $sub->assessment->intakeModule->module->title ?? '—' }}</td>
                                <td class="text-muted small">
                                    {{ $sub->submitted_at?->format('d M Y H:i') ?? '—' }}
                                </td>
                                <td>
                                    <a href="{{ route('lecturer.module.view', $sub->assessment->intake_module_id) }}?tab=assessments"
                                       class="btn btn-sm btn-outline-primary">
                                        Grade
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
