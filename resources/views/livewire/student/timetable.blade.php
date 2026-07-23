<?php

use Livewire\Volt\Component;
use App\Models\Enrollment;
use App\Models\IntakeModule;
use App\Models\ModuleSession;

new class extends Component {

    public $enrollment;
    public $upcomingSessions;
    public $pastSessions;

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
            $intakeModuleIds = IntakeModule::whereIn('trimester_id', $this->enrollment->resolvedTrimesterIds())->pluck('id');

            $this->upcomingSessions = ModuleSession::whereIn('intake_module_id', $intakeModuleIds)
                ->where('starts_at', '>=', now())
                ->with(['intakeModule.module', 'intakeModule.lecturers'])
                ->orderBy('starts_at')
                ->get();

            $this->pastSessions = ModuleSession::whereIn('intake_module_id', $intakeModuleIds)
                ->where('starts_at', '<', now())
                ->with(['intakeModule.module'])
                ->orderByDesc('starts_at')
                ->take(20)
                ->get();
        } else {
            $this->upcomingSessions = collect();
            $this->pastSessions     = collect();
        }
    }

}; ?>

<div>
    <div class="mb-4">
        <h4 class="fw-bold mb-0">Timetable</h4>
        @if($enrollment)
        <div class="text-muted small">{{ $enrollment->currentTrimesterLabel() }}</div>
        @endif
    </div>

    @if(!$enrollment)
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">No active enrollment found.</div>
    </div>
    @else

    {{-- Upcoming --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">
                <iconify-icon icon="solar:calendar-bold-duotone" class="me-1 text-primary"></iconify-icon>
                Upcoming Sessions ({{ $upcomingSessions->count() }})
            </h6>

            @forelse($upcomingSessions as $session)
            @php $isToday = $session->starts_at->isToday(); @endphp
            <div class="d-flex align-items-center p-3 mb-2 rounded border {{ $isToday ? 'border-primary bg-primary-subtle' : '' }}">
                <div class="text-center me-4" style="min-width:56px;">
                    <div class="fw-bold" style="font-size:24px;color:#0c314d;line-height:1;">
                        {{ $session->starts_at->format('d') }}
                    </div>
                    <div class="text-muted" style="font-size:12px;text-transform:uppercase;">
                        {{ $session->starts_at->format('M') }}
                    </div>
                    <div class="text-muted" style="font-size:11px;">
                        {{ $session->starts_at->format('D') }}
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">{{ $session->intakeModule->module->title ?? 'N/A' }}</div>
                    <div class="text-muted small">
                        <iconify-icon icon="solar:clock-circle-bold-duotone"></iconify-icon>
                        {{ $session->starts_at->format('H:i') }}
                        @if($session->ends_at) – {{ $session->ends_at->format('H:i') }} @endif
                        @if($session->room)
                            &nbsp;·&nbsp;
                            <iconify-icon icon="solar:map-point-bold-duotone"></iconify-icon>
                            {{ $session->room }}
                        @endif
                    </div>
                    @if($session->intakeModule->lecturers->isNotEmpty())
                    <div class="text-muted small">
                        <iconify-icon icon="solar:user-bold-duotone"></iconify-icon>
                        {{ $session->intakeModule->lecturers->map(fn($l) => $l->full_name)->join(', ') }}
                    </div>
                    @endif
                </div>
                @if($isToday)
                <span class="badge bg-primary">Today</span>
                @endif
            </div>
            @empty
            <p class="text-muted text-center py-3">No upcoming sessions scheduled.</p>
            @endforelse
        </div>
    </div>

    {{-- Past sessions --}}
    @if($pastSessions->isNotEmpty())
    <div class="card shadow-sm">
        <div class="card-body">
            <h6 class="fw-bold mb-3 text-muted">Past Sessions</h6>
            @foreach($pastSessions as $session)
            <div class="d-flex align-items-center py-2 border-bottom text-muted">
                <div class="me-3 small" style="min-width:80px;">
                    {{ $session->starts_at->format('d M Y') }}
                </div>
                <div class="small">{{ $session->intakeModule->module->title ?? 'N/A' }}</div>
                @if($session->room)
                <div class="ms-auto small text-muted">{{ $session->room }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @endif
</div>
