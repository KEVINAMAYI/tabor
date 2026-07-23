<?php

use Livewire\Volt\Component;
use App\Models\IntakeModule;
use App\Models\Trimester;
use App\Services\LMS\AttendanceService;

new class extends Component {

    public string $filterTrimester = '';
    public $trimesters;
    public $moduleSummaries;

    public function mount()
    {
        $this->trimesters = Trimester::with('academicYear')->orderByDesc('start_date')->get();
        $this->loadSummaries();
    }

    public function updatedFilterTrimester(): void
    {
        $this->loadSummaries();
    }

    protected function loadSummaries(): void
    {
        $query = IntakeModule::with(['module', 'trimester.academicYear', 'sessions'])
            ->whereHas('sessions');

        if ($this->filterTrimester) {
            $query->where('trimester_id', $this->filterTrimester);
        }

        $intakeModules = $query->get();

        $this->moduleSummaries = $intakeModules->map(function ($im) {
            $totalSessions = $im->sessions->count();
            if ($totalSessions === 0) return null;

            $enrollmentCount = \App\Models\Enrollment::forTrimester($im->trimester_id)->count();

            $presentCount = \App\Models\Attendance::whereIn('module_session_id', $im->sessions->pluck('id'))
                ->where('status', 'present')->count();
            $lateCount    = \App\Models\Attendance::whereIn('module_session_id', $im->sessions->pluck('id'))
                ->where('status', 'late')->count();
            $absentCount  = \App\Models\Attendance::whereIn('module_session_id', $im->sessions->pluck('id'))
                ->where('status', 'absent')->count();

            $maxAttendance = $totalSessions * $enrollmentCount;
            $attended      = $presentCount + ($lateCount * 0.5);
            $classPct      = $maxAttendance > 0 ? round(($attended / $maxAttendance) * 100, 1) : 0;

            return [
                'intakeModule'    => $im,
                'module'          => $im->module,
                'trimester'       => $im->trimester,
                'totalSessions'   => $totalSessions,
                'enrollmentCount' => $enrollmentCount,
                'presentCount'    => $presentCount,
                'lateCount'       => $lateCount,
                'absentCount'     => $absentCount,
                'classPct'        => $classPct,
            ];
        })->filter()->values();
    }

}; ?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Attendance Overview</h4>
            <div class="text-muted small">Attendance statistics across all modules</div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-lg-4">
                    <select class="form-select" wire:model.live="filterTrimester">
                        <option value="">All Trimesters</option>
                        @foreach($trimesters as $trimester)
                        <option value="{{ $trimester->id }}">{{ $trimester->name }} {{ $trimester->academicYear?->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if($moduleSummaries->isEmpty())
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            No session data found. Sessions need to be created by lecturers first.
        </div>
    </div>
    @else

    {{-- Summary --}}
    @php
        $avgPct = $moduleSummaries->isNotEmpty() ? round($moduleSummaries->avg('classPct'), 1) : 0;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm text-center p-3">
                <div class="h2 fw-bold {{ $avgPct < 70 ? 'text-danger' : 'text-success' }} mb-0">{{ $avgPct }}%</div>
                <div class="text-muted small">Overall Class Attendance</div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm text-center p-3">
                <div class="h2 fw-bold mb-0">{{ $moduleSummaries->count() }}</div>
                <div class="text-muted small">Modules with Sessions</div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm text-center p-3">
                <div class="h2 fw-bold mb-0">{{ $moduleSummaries->sum('totalSessions') }}</div>
                <div class="text-muted small">Total Sessions</div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm text-center p-3">
                <div class="h2 fw-bold mb-0">{{ $moduleSummaries->sum('presentCount') }}</div>
                <div class="text-muted small">Total Attendances</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Module</th>
                            <th>Trimester</th>
                            <th class="text-center">Sessions</th>
                            <th class="text-center">Students</th>
                            <th class="text-center">Present</th>
                            <th class="text-center">Late</th>
                            <th class="text-center">Absent</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($moduleSummaries as $s)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $s['module']->title ?? '—' }}</div>
                            </td>
                            <td class="text-muted small">{{ $s['trimester']->name ?? '—' }} {{ $s['trimester']?->academicYear?->name }}</td>
                            <td class="text-center">{{ $s['totalSessions'] }}</td>
                            <td class="text-center">{{ $s['enrollmentCount'] }}</td>
                            <td class="text-center">
                                <span class="badge bg-success">{{ $s['presentCount'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark">{{ $s['lateCount'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger">{{ $s['absentCount'] }}</span>
                            </td>
                            <td style="min-width:160px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:8px;">
                                        <div class="progress-bar {{ $s['classPct'] < 70 ? 'bg-danger' : ($s['classPct'] < 80 ? 'bg-warning' : 'bg-success') }}"
                                             style="width:{{ $s['classPct'] }}%;"></div>
                                    </div>
                                    <span class="small fw-bold {{ $s['classPct'] < 70 ? 'text-danger' : 'text-success' }}">
                                        {{ $s['classPct'] }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @endif
</div>
