<?php

use Livewire\Volt\Component;
use App\Models\Assessment;
use App\Models\IntakeModule;
use App\Models\Trimester;

new class extends Component {

    public string $search          = '';
    public string $filterType      = '';
    public string $filterTrimester = '';

    public $trimesters;

    public function mount()
    {
        $this->trimesters = Trimester::with('academicYear')->orderByDesc('start_date')->get();
    }

    public function assessments()
    {
        return Assessment::query()
            ->with(['intakeModule.module', 'intakeModule.trimester.academicYear', 'submissions'])
            ->when($this->filterType,      fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterTrimester, fn($q) => $q->whereHas('intakeModule', fn($q2) => $q2->where('trimester_id', $this->filterTrimester)))
            ->when($this->search,          fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->latest()
            ->get();
    }

}; ?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Assessments & Exams</h4>
            <div class="text-muted small">All assessments across all modules</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-lg-5">
                    <input type="text" class="form-control" placeholder="Search by title..."
                           wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-lg-3">
                    <select class="form-select" wire:model.live="filterType">
                        <option value="">All Types</option>
                        <option value="CAT">CAT</option>
                        <option value="Exam">Exam</option>
                        <option value="Assignment">Assignment</option>
                        <option value="Quiz">Quiz</option>
                    </select>
                </div>
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

    @php $assessments = $this->assessments(); @endphp

    {{-- Summary badges --}}
    <div class="d-flex gap-2 mb-3">
        <span class="badge bg-secondary px-3 py-2">{{ $assessments->count() }} total</span>
        <span class="badge bg-info px-3 py-2">{{ $assessments->where('type','CAT')->count() }} CATs</span>
        <span class="badge bg-danger px-3 py-2">{{ $assessments->where('type','Exam')->count() }} Exams</span>
        <span class="badge bg-primary px-3 py-2">{{ $assessments->where('type','Assignment')->count() }} Assignments</span>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Module</th>
                            <th>Trimester</th>
                            <th class="text-center">Max</th>
                            <th class="text-center">Submitted</th>
                            <th class="text-center">Graded</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assessments as $a)
                        @php
                            $subCount    = $a->submissions->whereNotNull('submitted_at')->count();
                            $gradedCount = $a->submissions->whereNotNull('graded_at')->count();
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $a->title }}</div>
                                @if($a->instructions)
                                <div class="text-muted small" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ $a->instructions }}
                                </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge
                                    {{ $a->type === 'CAT' ? 'bg-info' :
                                       ($a->type === 'Exam' ? 'bg-danger' :
                                       ($a->type === 'Assignment' ? 'bg-primary' : 'bg-secondary')) }}">
                                    {{ $a->type }}
                                </span>
                            </td>
                            <td class="small">{{ $a->intakeModule->module->title ?? '—' }}</td>
                            <td class="small text-muted">{{ $a->intakeModule->trimester->name ?? '—' }} {{ $a->intakeModule->trimester?->academicYear?->name }}</td>
                            <td class="text-center">{{ $a->max_marks }}</td>
                            <td class="text-center">
                                <span class="badge {{ $subCount > 0 ? 'bg-primary' : 'bg-light text-muted' }}">
                                    {{ $subCount }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $gradedCount > 0 ? 'bg-success' : 'bg-light text-muted' }}">
                                    {{ $gradedCount }}
                                </span>
                            </td>
                            <td class="small text-muted">
                                {{ $a->due_on?->format('d M Y') ?? '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No assessments found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
