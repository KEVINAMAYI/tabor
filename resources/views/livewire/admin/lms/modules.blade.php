<?php

use Livewire\Volt\Component;
use App\Models\Trimester;
use App\Models\IntakeModule;
use App\Models\IntakeModuleLecturer;
use App\Models\Lecturer;

new class extends Component {

    public string $filterTrimester = '';
    public $trimesters;
    public $lecturers;

    // Assign lecturer modal state
    public ?int $assigningIntakeModuleId = null;
    public string $assigningModuleTitle  = '';
    public string $selectedLecturerId    = '';

    public function mount()
    {
        $this->trimesters = Trimester::with('academicYear')->orderByDesc('start_date')->get();
        $this->lecturers  = Lecturer::orderBy('first_name')->get();
    }

    public function intakeModules()
    {
        return IntakeModule::query()
            ->with(['module.course', 'trimester.academicYear', 'lecturers', 'assessments', 'sessions', 'materials'])
            ->when($this->filterTrimester, fn($q) => $q->where('trimester_id', $this->filterTrimester))
            ->get()
            ->groupBy('trimester_id')
            ->sortByDesc(fn($modules) => $modules->first()->trimester?->start_date);
    }

    public function openAssign(int $intakeModuleId, string $moduleTitle): void
    {
        $this->assigningIntakeModuleId = $intakeModuleId;
        $this->assigningModuleTitle    = $moduleTitle;
        $this->selectedLecturerId      = '';
    }

    public function assignLecturer(): void
    {
        $this->validate(['selectedLecturerId' => 'required|exists:lecturers,id']);

        IntakeModuleLecturer::firstOrCreate([
            'intake_module_id' => $this->assigningIntakeModuleId,
            'lecturer_id'      => $this->selectedLecturerId,
        ]);

        $this->assigningIntakeModuleId = null;
        $this->selectedLecturerId      = '';
        $this->dispatch('toast', type: 'success', message: 'Lecturer assigned.');
    }

    public function removeLecturer(int $intakeModuleId, int $lecturerId): void
    {
        IntakeModuleLecturer::where('intake_module_id', $intakeModuleId)
            ->where('lecturer_id', $lecturerId)
            ->delete();

        $this->dispatch('toast', type: 'success', message: 'Lecturer removed.');
    }

}; ?>

@push('styles')
<style>
.module-card { border-left: 4px solid #0c314d; }
.stat-pill { font-size: 11px; padding: 2px 8px; border-radius: 999px; }
</style>
@endpush

<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">LMS Module Management</h4>
            <div class="text-muted small">Manage lecturer assignments, view module stats</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('exams.index') }}" class="btn btn-outline-secondary btn-sm">
                <iconify-icon icon="solar:diploma-bold-duotone"></iconify-icon> Exams
            </a>
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm">
                <iconify-icon icon="solar:clipboard-check-bold-duotone"></iconify-icon> Attendance
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="col-form-label small">Filter by Trimester:</label>
                </div>
                <div class="col-lg-3">
                    <select class="form-select form-select-sm" wire:model.live="filterTrimester">
                        <option value="">All Trimesters</option>
                        @foreach($trimesters as $trimester)
                        <option value="{{ $trimester->id }}">{{ $trimester->name }} {{ $trimester->academicYear?->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Assign Lecturer Modal --}}
    @if($assigningIntakeModuleId)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.4);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Lecturer — {{ $assigningModuleTitle }}</h5>
                    <button wire:click="$set('assigningIntakeModuleId', null)" class="btn-close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Select Lecturer</label>
                    <select class="form-select" wire:model="selectedLecturerId">
                        <option value="">Choose lecturer...</option>
                        @foreach($lecturers as $lec)
                        <option value="{{ $lec->id }}">{{ $lec->full_name }}</option>
                        @endforeach
                    </select>
                    @error('selectedLecturerId') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="modal-footer">
                    <button wire:click="$set('assigningIntakeModuleId', null)" class="btn btn-outline-secondary">Cancel</button>
                    <button wire:click="assignLecturer" class="btn btn-primary">Assign</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @php $grouped = $this->intakeModules(); @endphp

    @forelse($grouped as $trimesterId => $modules)
    @php $trimester = $modules->first()->trimester; @endphp

    <div class="mb-5">
        {{-- Trimester header --}}
        <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width:10px;height:32px;background:#0c314d;border-radius:3px;flex-shrink:0;"></div>
            <div>
                <h5 class="fw-bold mb-0">{{ $trimester->name ?? 'Trimester #'.$trimesterId }} {{ $trimester?->academicYear?->name }}</h5>
                <div class="text-muted small">{{ $modules->count() }} modules</div>
            </div>
        </div>

        <div class="row g-3">
            @foreach($modules as $im)
            <div class="col-lg-6">
                <div class="card shadow-sm h-100 module-card">
                    <div class="card-body">
                        {{-- Module name + course --}}
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-bold">{{ $im->module->title ?? 'Unknown Module' }}</div>
                                <div class="text-muted small">{{ $im->module->course->title ?? '' }}</div>
                            </div>
                            <div class="d-flex gap-1">
                                <span class="badge bg-light text-dark stat-pill border">
                                    <iconify-icon icon="solar:book-2-bold-duotone"></iconify-icon>
                                    {{ $im->materials->count() }} materials
                                </span>
                                <span class="badge bg-light text-dark stat-pill border">
                                    <iconify-icon icon="solar:diploma-bold-duotone"></iconify-icon>
                                    {{ $im->assessments->count() }} assessments
                                </span>
                                <span class="badge bg-light text-dark stat-pill border">
                                    <iconify-icon icon="solar:calendar-bold-duotone"></iconify-icon>
                                    {{ $im->sessions->count() }} sessions
                                </span>
                            </div>
                        </div>

                        {{-- Assigned Lecturers --}}
                        <div class="mb-2">
                            <div class="text-muted small mb-1">Assigned Lecturers</div>
                            @if($im->lecturers->isEmpty())
                                <span class="badge bg-warning text-dark">No lecturer assigned</span>
                            @else
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($im->lecturers as $lec)
                                    <div class="d-flex align-items-center gap-1 badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                        <iconify-icon icon="solar:user-bold-duotone"></iconify-icon>
                                        {{ $lec->full_name }}
                                        <button wire:click="removeLecturer({{ $im->id }}, {{ $lec->id }})"
                                                wire:confirm="Remove {{ $lec->full_name }} from this module?"
                                                class="border-0 bg-transparent p-0 ms-1 text-danger"
                                                style="line-height:1;font-size:14px;">
                                            &times;
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex gap-2 mt-3">
                            <button wire:click="openAssign({{ $im->id }}, '{{ addslashes($im->module->title ?? '') }}')"
                                    class="btn btn-sm btn-outline-primary">
                                <iconify-icon icon="solar:user-plus-bold-duotone"></iconify-icon>
                                Assign Lecturer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <iconify-icon icon="solar:book-2-bold-duotone" style="font-size:48px;opacity:.3;"></iconify-icon>
            <div class="mt-2">No intake modules found. Add modules to an intake first.</div>
            <a href="{{ route('intakes.index') }}" class="btn btn-primary mt-3">Go to Intakes</a>
        </div>
    </div>
    @endforelse

</div>

@push('scripts')
<script>
window.addEventListener('toast', (e) => {
    const type = e.detail[0]?.type ?? 'success';
    const msg  = e.detail[0]?.message ?? '';
    Swal.fire({ toast: true, position: 'top-end', icon: type, title: msg,
        showConfirmButton: false, timer: 3000 });
});
</script>
@endpush
