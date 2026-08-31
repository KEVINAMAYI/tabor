<?php

use Livewire\Volt\Component;
use App\Models\Enrollment;
use App\Models\IntakeModule;
use App\Services\LMS\AttendanceService;

new class extends Component {

    public $enrollment;
    public $intakeModules;

    public function mount()
    {
        $student = auth()->user()->student;

        $this->enrollment = $student
            ? Enrollment::where('student_id', $student->id)
                ->whereIn('status', ['approved', 'active'])
                ->with('intake')
                ->latest()
                ->first()
            : null;

        if ($this->enrollment) {
            $this->intakeModules = IntakeModule::whereIn('trimester_id', $this->enrollment->resolvedTrimesterIds())
                ->with(['module', 'assessments', 'lecturers'])
                ->get();
        } else {
            $this->intakeModules = collect();
        }
    }

}; ?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">My Modules</h4>
            @if($enrollment)
            <div class="text-muted small">{{ $enrollment->currentTrimesterLabel() }}</div>
            @endif
        </div>
    </div>

    @if(!$enrollment)
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <iconify-icon icon="solar:book-2-bold-duotone" style="font-size:48px;opacity:.3;"></iconify-icon>
            <div class="mt-2">No active enrollment found.</div>
        </div>
    </div>
    @else
    <div class="row g-3">
        @forelse($intakeModules as $im)
        <div class="col-lg-4 col-md-6">
            <a href="{{ route('student.module.view', $im->id) }}" class="text-decoration-none">
                <div class="card shadow-sm h-100" style="transition:.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div style="width:48px;height:48px;border-radius:12px;background:#0c314d;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;flex-shrink:0;">
                                <iconify-icon icon="solar:book-bold-duotone"></iconify-icon>
                            </div>
                            <div class="ms-3">
                                <div class="fw-bold text-dark">{{ $im->module->title ?? 'Unnamed Module' }}</div>
                                @if($im->module->code ?? false)
                                <div class="text-muted small">{{ $im->module->code }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex gap-3 text-muted small mb-3">
                            <span>
                                <iconify-icon icon="solar:diploma-bold-duotone" class="me-1"></iconify-icon>
                                {{ $im->assessments->count() }} assessments
                            </span>
                        </div>

                        @if($im->lecturers->isNotEmpty())
                        <div class="text-muted small">
                            <iconify-icon icon="solar:user-bold-duotone" class="me-1"></iconify-icon>
                            {{ $im->lecturers->map(fn($l) => $l->full_name)->join(', ') }}
                        </div>
                        @endif
                    </div>
                    <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center">
                        <span class="text-primary small fw-semibold">View Module</span>
                        <iconify-icon icon="solar:arrow-right-linear" class="text-primary"></iconify-icon>
                    </div>
                </div>
            </a>
        </div>
        @empty
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    No modules found for your intake.
                </div>
            </div>
        </div>
        @endforelse
    </div>
    @endif
</div>
