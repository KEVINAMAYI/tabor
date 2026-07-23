<?php

use Livewire\Volt\Component;
use App\Models\Enrollment;
use App\Models\IntakeModule;
use App\Models\Announcement;

new class extends Component {

    public $enrollment;
    public $announcements;

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

            $this->announcements = Announcement::whereIn('intake_module_id', $intakeModuleIds)
                ->where(fn($q) => $q->whereNotNull('published_at')->where('published_at', '<=', now()))
                ->with(['intakeModule.module', 'lecturer'])
                ->orderByDesc('is_pinned')
                ->orderByDesc('published_at')
                ->get();
        } else {
            $this->announcements = collect();
        }
    }

}; ?>

<div>
    <div class="mb-4">
        <h4 class="fw-bold mb-0">Announcements</h4>
        @if($enrollment)
        <div class="text-muted small">{{ $enrollment->currentTrimesterLabel() }}</div>
        @endif
    </div>

    @if(!$enrollment)
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">No active enrollment found.</div>
    </div>
    @elseif($announcements->isEmpty())
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <iconify-icon icon="solar:bell-bold-duotone" style="font-size:48px;opacity:.3;"></iconify-icon>
            <div class="mt-2">No announcements yet.</div>
        </div>
    </div>
    @else

    @foreach($announcements as $ann)
    <div class="card shadow-sm mb-3 {{ $ann->is_pinned ? 'border-warning' : '' }}">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                    @if($ann->is_pinned)
                    <iconify-icon icon="solar:pin-bold-duotone" class="text-warning me-1"></iconify-icon>
                    @endif
                    <strong>{{ $ann->title }}</strong>
                    <div class="small text-muted mt-1">
                        <iconify-icon icon="solar:book-2-bold-duotone" class="me-1"></iconify-icon>
                        {{ $ann->intakeModule->module->title ?? 'N/A' }}
                        @if($ann->lecturer)
                            &nbsp;·&nbsp;
                            <iconify-icon icon="solar:user-bold-duotone" class="me-1"></iconify-icon>
                            {{ $ann->lecturer->full_name }}
                        @endif
                    </div>
                </div>
                <div class="text-muted small text-end">
                    {{ $ann->published_at?->format('d M Y') }}<br>
                    <span style="font-size:11px;">{{ $ann->published_at?->format('H:i') }}</span>
                </div>
            </div>
            <div class="mt-2" style="white-space:pre-line;">{{ $ann->body }}</div>
        </div>
    </div>
    @endforeach

    @endif
</div>
