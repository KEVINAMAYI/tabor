<?php

use Livewire\Volt\Component;
use App\Models\Enrollment;
use App\Models\IntakeModule;
use App\Services\LMS\GradeBookService;

new class extends Component {

    public $enrollment;
    public $grades;

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
            $this->grades = app(GradeBookService::class)->computeAllForEnrollment($this->enrollment);
        } else {
            $this->grades = collect();
        }
    }

}; ?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">My Grades</h4>
            @if($enrollment)
            <div class="text-muted small">{{ $enrollment->currentTrimesterLabel() }}</div>
            @endif
        </div>
    </div>

    @if(!$enrollment)
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">No active enrollment found.</div>
    </div>
    @else

    {{-- Summary row --}}
    @php
        $withGrades = $grades->where('has_grade', true);
        $avgScore   = $withGrades->isNotEmpty() ? round($withGrades->avg('final_score'), 1) : null;
        $gradedCount = $withGrades->count();
    @endphp

    @if($avgScore !== null)
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm text-center p-3">
                <div class="h2 fw-bold text-primary mb-0">{{ $avgScore }}</div>
                <div class="text-muted small">Average Score</div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm text-center p-3">
                <div class="h2 fw-bold mb-0">{{ $grades->count() }}</div>
                <div class="text-muted small">Total Modules</div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm text-center p-3">
                <div class="h2 fw-bold text-success mb-0">{{ $gradedCount }}</div>
                <div class="text-muted small">With Results</div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm text-center p-3">
                <div class="h2 fw-bold text-warning mb-0">{{ $grades->count() - $gradedCount }}</div>
                <div class="text-muted small">Pending Results</div>
            </div>
        </div>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Module</th>
                            <th class="text-center">CAT</th>
                            <th class="text-center">Exam</th>
                            <th class="text-center">Final</th>
                            <th class="text-center">Grade</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grades as $g)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $g['module']->title ?? '—' }}</div>
                            </td>
                            <td class="text-center">
                                @if($g['has_grade'])
                                    {{ number_format($g['cat_score'], 1) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($g['has_grade'])
                                    {{ number_format($g['exam_score'], 1) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center fw-bold">
                                @if($g['has_grade'])
                                    {{ number_format($g['final_score'], 1) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($g['has_grade'])
                                <span class="badge fs-6 px-3 py-1
                                    {{ $g['grade_letter'] === 'A' ? 'bg-success' :
                                       ($g['grade_letter'] === 'B' ? 'bg-primary' :
                                       ($g['grade_letter'] === 'C' ? 'bg-info text-dark' :
                                       ($g['grade_letter'] === 'D' ? 'bg-warning text-dark' : 'bg-danger'))) }}">
                                    {{ $g['grade_letter'] }}
                                </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($g['has_grade'])
                                <span class="text-muted small">{{ $g['grade_label'] }}</span>
                                @else
                                <span class="badge bg-light text-muted">No Results</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No modules found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Grade Scale Reference --}}
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3 small text-muted">Grade Scale</h6>
            <div class="d-flex gap-3 flex-wrap">
                @foreach([['A','≥70','Distinction','success'], ['B','≥60','Credit','primary'], ['C','≥50','Pass','info'], ['D','≥40','Supplementary','warning'], ['E','<40','Fail','danger']] as [$l,$r,$n,$c])
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-{{ $c }}">{{ $l }}</span>
                    <span class="small text-muted">{{ $r }} · {{ $n }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    @endif
</div>
