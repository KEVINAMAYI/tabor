<?php

use Livewire\Volt\Component;
use App\Models\Enrollment;
use App\Models\IntakeModule;
use App\Services\LMS\AttendanceService;

new class extends Component {

    public $enrollment;
    public $moduleSummaries;

    public function mount()
    {
        $student = auth()->user()->student;

        $this->enrollment = $student
            ? Enrollment::where('student_id', $student->id)
                ->whereIn('status', ['approved', 'active'])
                ->latest()
                ->first()
            : null;

        if ($this->enrollment) {
            $service = app(AttendanceService::class);

            $intakeModules = IntakeModule::whereIn('trimester_id', $this->enrollment->resolvedTrimesterIds())
                ->with('module')
                ->get();

            $this->moduleSummaries = $intakeModules->map(fn($im) => [
                'module'     => $im->module,
                'intakeModule' => $im,
                ...$service->statsForEnrollment($this->enrollment, $im),
            ]);
        } else {
            $this->moduleSummaries = collect();
        }
    }

}; ?>

<div>
    <div class="mb-4">
        <h4 class="fw-bold mb-0">My Attendance</h4>
        @if($enrollment)
        <div class="text-muted small">{{ $enrollment->currentTrimesterLabel() }}</div>
        @endif
    </div>

    @if(!$enrollment)
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">No active enrollment found.</div>
    </div>
    @elseif($moduleSummaries->isEmpty())
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">No modules found.</div>
    </div>
    @else

    {{-- Overall summary --}}
    @php
        $withSessions = $moduleSummaries->where('total', '>', 0);
        $overallPct = $withSessions->isNotEmpty()
            ? round($withSessions->avg('percentage'), 1)
            : null;
        $flaggedCount = $moduleSummaries->where('flag', true)->count();
    @endphp

    @if($overallPct !== null)
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm text-center p-3">
                <div class="h2 fw-bold {{ $overallPct < 75 ? 'text-danger' : 'text-success' }} mb-0">{{ $overallPct }}%</div>
                <div class="text-muted small">Overall Attendance</div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm text-center p-3">
                <div class="h2 fw-bold mb-0">{{ $moduleSummaries->count() }}</div>
                <div class="text-muted small">Modules</div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm text-center p-3">
                <div class="h2 fw-bold text-danger mb-0">{{ $flaggedCount }}</div>
                <div class="text-muted small">Below 75%</div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm text-center p-3">
                <div class="h2 fw-bold text-success mb-0">{{ $moduleSummaries->where('flag', false)->where('total', '>', 0)->count() }}</div>
                <div class="text-muted small">Good Standing</div>
            </div>
        </div>
    </div>
    @endif

    @if($flaggedCount > 0)
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <iconify-icon icon="solar:danger-bold-duotone" class="me-2" style="font-size:20px;"></iconify-icon>
        <div>
            You have <strong>{{ $flaggedCount }}</strong> module(s) with attendance below 75%.
            Risk of being barred from examinations.
        </div>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Attendance by Module</h6>
            @foreach($moduleSummaries as $s)
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="fw-semibold">{{ $s['module']->title ?? 'N/A' }}</div>
                        <div class="text-muted small">
                            {{ $s['present'] }} present · {{ $s['late'] }} late · {{ $s['absent'] }} absent
                            of {{ $s['total'] }} sessions
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="fw-bold {{ $s['flag'] ? 'text-danger' : 'text-success' }}">
                            {{ $s['percentage'] }}%
                        </span>
                        @if($s['flag'])
                        <div>
                            <span class="badge bg-danger">Below 75%</span>
                        </div>
                        @endif
                    </div>
                </div>
                @if($s['total'] > 0)
                <div class="progress" style="height:10px;border-radius:999px;">
                    <div class="progress-bar {{ $s['flag'] ? 'bg-danger' : 'bg-success' }}"
                         style="width:{{ $s['percentage'] }}%;border-radius:999px;transition:width .6s ease;"></div>
                </div>
                @else
                <div class="text-muted small">No sessions recorded yet.</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-body p-3">
            <div class="small text-muted">
                <strong>Attendance Policy:</strong>
                Students must maintain ≥75% attendance per module to qualify for end-term examinations.
                Late arrivals count as 50% attendance.
            </div>
        </div>
    </div>

    @endif
</div>
