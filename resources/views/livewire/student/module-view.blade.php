<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\IntakeModule;
use App\Models\Enrollment;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\Material;
use App\Services\LMS\GradeBookService;
use App\Services\LMS\AttendanceService;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public IntakeModule $intakeModule;
    public string $activeTab = 'materials';

    public $enrollment;
    public $materials;
    public $assessments;
    public $announcements;
    public $gradeData;
    public $attendanceStats;

    // submission
    public ?int $submittingAssessmentId = null;
    public $submissionFile;
    public string $submissionNote = '';

    public function mount(IntakeModule $intakeModule)
    {
        $this->intakeModule = $intakeModule->load(['module', 'trimester', 'lecturers']);
        $this->activeTab    = request('tab', 'materials');

        $student = auth()->user()->student;
        $this->enrollment = $student
            ? Enrollment::where('student_id', $student->id)
                ->forTrimester($intakeModule->trimester_id)
                ->first()
            : null;

        $this->loadData();
    }

    protected function loadData(): void
    {
        $this->materials     = Material::where('intake_module_id', $this->intakeModule->id)->latest()->get();
        $this->assessments   = Assessment::where('intake_module_id', $this->intakeModule->id)->latest()->get();
        $this->announcements = $this->intakeModule->announcements;

        if ($this->enrollment) {
            $this->gradeData      = app(GradeBookService::class)->computeForEnrollment($this->enrollment, $this->intakeModule);
            $this->attendanceStats = app(AttendanceService::class)->statsForEnrollment($this->enrollment, $this->intakeModule);
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->submittingAssessmentId = null;
    }

    public function startSubmission(int $assessmentId): void
    {
        $this->submittingAssessmentId = $assessmentId;
        $this->submissionFile = null;
        $this->submissionNote = '';
    }

    public function submitAssessment(): void
    {
        $this->validate([
            'submissionFile' => 'nullable|file|max:20480',
            'submissionNote' => 'nullable|string|max:2000',
        ]);

        if (!$this->enrollment) return;

        $path = $this->submissionFile
            ? $this->submissionFile->store('submissions/' . $this->enrollment->id, 'local')
            : null;

        AssessmentSubmission::updateOrCreate(
            [
                'assessment_id' => $this->submittingAssessmentId,
                'enrollment_id' => $this->enrollment->id,
            ],
            [
                'file_path'    => $path ?? null,
                'submitted_at' => now(),
                'status'       => 'submitted',
            ]
        );

        $this->submittingAssessmentId = null;
        $this->submissionFile = null;
        $this->submissionNote = '';
        $this->loadData();
        $this->dispatch('toast', type: 'success', message: 'Submission saved!');
    }

}; ?>

@push('styles')
<style>
.nav-tabs .nav-link { color: #555; font-weight: 500; }
.nav-tabs .nav-link.active { color: #0c314d; border-bottom: 2px solid #0c314d; font-weight: 600; }
</style>
@endpush

<div>
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('student.modules.index') }}" class="text-muted small">
                <iconify-icon icon="solar:arrow-left-linear"></iconify-icon> My Modules
            </a>
            <h4 class="fw-bold mb-0 mt-1">{{ $intakeModule->module->title ?? 'Module' }}</h4>
            @if($intakeModule->lecturers->isNotEmpty())
            <div class="text-muted small">
                <iconify-icon icon="solar:user-bold-duotone" class="me-1"></iconify-icon>
                {{ $intakeModule->lecturers->map(fn($l) => $l->full_name)->join(', ') }}
            </div>
            @endif
        </div>
        @if($attendanceStats)
        <div class="text-end">
            <div class="fw-bold {{ $attendanceStats['flag'] ? 'text-danger' : 'text-success' }}">
                {{ $attendanceStats['percentage'] }}%
            </div>
            <div class="text-muted small">Attendance</div>
        </div>
        @endif
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-4">
        @foreach(['materials' => 'Materials', 'assessments' => 'Assessments', 'grades' => 'My Grades', 'announcements' => 'Announcements'] as $key => $label)
        <li class="nav-item">
            <button class="nav-link {{ $activeTab === $key ? 'active' : '' }}"
                    wire:click="setTab('{{ $key }}')">{{ $label }}</button>
        </li>
        @endforeach
    </ul>

    {{-- MATERIALS --}}
    @if($activeTab === 'materials')
    <div class="row g-3">
        @forelse($materials as $m)
        <div class="col-lg-6">
            <div class="d-flex align-items-center p-3 rounded border">
                <div style="width:44px;height:44px;border-radius:10px;background:#e9ecef;display:flex;align-items:center;justify-content:center;font-size:22px;color:#0c314d;flex-shrink:0;">
                    <iconify-icon icon="solar:file-bold-duotone"></iconify-icon>
                </div>
                <div class="ms-3 flex-grow-1">
                    <div class="fw-semibold">{{ $m->title }}</div>
                    <div class="text-muted small">
                        {{ $m->original_name ?? '' }}
                        · {{ $m->created_at->format('d M Y') }}
                    </div>
                </div>
                <a href="{{ Storage::url($m->file_path) }}" target="_blank"
                   class="btn btn-sm btn-outline-primary ms-2">
                    <iconify-icon icon="solar:download-bold-duotone"></iconify-icon>
                </a>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5 text-muted">No materials uploaded yet.</div>
            </div>
        </div>
        @endforelse
    </div>
    @endif

    {{-- ASSESSMENTS --}}
    @if($activeTab === 'assessments')
    @if($submittingAssessmentId)
    @php $targetAssessment = $assessments->firstWhere('id', $submittingAssessmentId); @endphp
    <div class="card shadow-sm mb-3 border-primary">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <h6 class="fw-bold mb-0">Submit: {{ $targetAssessment?->title }}</h6>
                <button wire:click="$set('submittingAssessmentId', null)" class="btn btn-sm btn-outline-secondary">Cancel</button>
            </div>
            @if($targetAssessment?->instructions)
            <div class="alert alert-info small mb-3">{{ $targetAssessment->instructions }}</div>
            @endif
            <div class="mb-3">
                <label class="form-label">Upload File (optional)</label>
                <input type="file" class="form-control" wire:model="submissionFile">
            </div>
            <button class="btn btn-success" wire:click="submitAssessment">
                <iconify-icon icon="solar:upload-bold-duotone"></iconify-icon> Submit
            </button>
        </div>
    </div>
    @endif

    @forelse($assessments as $assessment)
    @php
        $mySubmission = $enrollment
            ? \App\Models\AssessmentSubmission::where('assessment_id', $assessment->id)
                ->where('enrollment_id', $enrollment->id)
                ->first()
            : null;
    @endphp
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="badge {{ $assessment->type === 'CAT' ? 'bg-info' : ($assessment->type === 'Exam' ? 'bg-danger' : 'bg-secondary') }} me-2">
                        {{ $assessment->type }}
                    </span>
                    <strong>{{ $assessment->title }}</strong>
                </div>
                <div class="text-muted small text-end">
                    Max: {{ $assessment->max_marks }}
                    @if($assessment->due_on)
                    <br>Due: {{ $assessment->due_on->format('d M Y') }}
                    @endif
                </div>
            </div>

            @if($assessment->instructions)
            <div class="text-muted small mt-2">{{ $assessment->instructions }}</div>
            @endif

            <div class="mt-3 d-flex align-items-center gap-3">
                @if($mySubmission?->graded_at)
                    <span class="badge bg-success">
                        Graded: {{ $mySubmission->mark }}/{{ $assessment->max_marks }}
                    </span>
                    @if($mySubmission->feedback)
                    <span class="text-muted small">{{ $mySubmission->feedback }}</span>
                    @endif
                @elseif($mySubmission?->submitted_at)
                    <span class="badge bg-warning text-dark">Submitted — awaiting grade</span>
                    <button wire:click="startSubmission({{ $assessment->id }})"
                            class="btn btn-sm btn-outline-secondary">Resubmit</button>
                @else
                    <span class="badge bg-light text-dark border">Not submitted</span>
                    @if($enrollment)
                    <button wire:click="startSubmission({{ $assessment->id }})"
                            class="btn btn-sm btn-primary">Submit</button>
                    @endif
                @endif

                @if($assessment->file_path)
                <a href="{{ Storage::url($assessment->file_path) }}" target="_blank"
                   class="btn btn-sm btn-outline-info">
                    <iconify-icon icon="solar:download-bold-duotone"></iconify-icon> Question Paper
                </a>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">No assessments posted yet.</div>
    </div>
    @endforelse
    @endif

    {{-- GRADES --}}
    @if($activeTab === 'grades')
    @if($gradeData && $gradeData['has_grade'])
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm text-center p-4">
                <div style="font-size:64px;font-weight:700;color:#0c314d;line-height:1;">{{ $gradeData['grade_letter'] }}</div>
                <div class="h5 fw-bold text-muted">{{ $gradeData['grade_label'] }}</div>
                <div class="h2 fw-bold mt-2">{{ $gradeData['final_score'] }}<span class="text-muted fs-5">/100</span></div>
                <div class="text-muted small mt-2">
                    CAT {{ $gradeData['cat_weight'] }}% · Exam {{ $gradeData['exam_weight'] }}%
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Score Breakdown</h6>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>CAT Score ({{ $gradeData['cat_weight'] }}%)</span>
                            <span class="fw-bold">{{ $gradeData['cat_score'] }}</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-info" style="width:{{ ($gradeData['cat_score'] / $gradeData['cat_weight']) * 100 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Exam Score ({{ $gradeData['exam_weight'] }}%)</span>
                            <span class="fw-bold">{{ $gradeData['exam_score'] }}</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-danger" style="width:{{ ($gradeData['exam_score'] / $gradeData['exam_weight']) * 100 }}%"></div>
                        </div>
                    </div>

                    @if($gradeData['cat_submissions']->isNotEmpty())
                    <h6 class="fw-semibold mb-2 small text-muted">CAT Results</h6>
                    @foreach($gradeData['cat_submissions'] as $s)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
                        <span>{{ $s->assessment->title }}</span>
                        <span class="fw-bold">{{ $s->mark }}/{{ $s->assessment->max_marks }}</span>
                    </div>
                    @endforeach
                    @endif

                    @if($gradeData['exam_submission'])
                    <div class="d-flex justify-content-between align-items-center pt-2 small">
                        <span>{{ $gradeData['exam_submission']->assessment->title }}</span>
                        <span class="fw-bold">{{ $gradeData['exam_submission']->mark }}/{{ $gradeData['exam_submission']->assessment->max_marks }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <iconify-icon icon="solar:diploma-bold-duotone" style="font-size:48px;opacity:.3;"></iconify-icon>
            <div class="mt-2">No grades available yet for this module.</div>
        </div>
    </div>
    @endif
    @endif

    {{-- ANNOUNCEMENTS --}}
    @if($activeTab === 'announcements')
    @forelse($announcements as $ann)
    <div class="card shadow-sm mb-3 {{ $ann->is_pinned ? 'border-warning' : '' }}">
        <div class="card-body">
            <div class="d-flex align-items-center mb-2">
                @if($ann->is_pinned)
                <iconify-icon icon="solar:pin-bold-duotone" class="text-warning me-2"></iconify-icon>
                @endif
                <strong>{{ $ann->title }}</strong>
                <span class="text-muted small ms-auto">{{ $ann->published_at?->format('d M Y H:i') }}</span>
            </div>
            <div style="white-space:pre-line;">{{ $ann->body }}</div>
        </div>
    </div>
    @empty
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">No announcements yet.</div>
    </div>
    @endforelse
    @endif
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
