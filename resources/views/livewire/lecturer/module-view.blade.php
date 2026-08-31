<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\IntakeModule;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\Announcement;
use App\Models\Material;
use App\Models\ModuleSession;
use App\Models\Enrollment;
use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public IntakeModule $intakeModule;
    public string $activeTab = 'overview';

    // Students
    public $students;

    // Materials
    public $materials;
    public $materialFile;
    public string $materialTitle = '';
    public string $materialDescription = '';

    // Assessments
    public $assessments;
    // create assessment form
    public string $aType     = 'CAT';
    public string $aTitle    = '';
    public string $aDueOn    = '';
    public int    $aMaxMarks = 100;
    public string $aInstructions = '';
    public $aFile;
    // grading
    public ?int $gradingSubmissionId = null;
    public string $gradeMark     = '';
    public string $gradeFeedback = '';

    // Sessions / Attendance
    public $sessions;
    public string $sessionStartsAt = '';
    public string $sessionEndsAt   = '';
    public string $sessionRoom     = '';
    public string $sessionTopic    = '';
    public ?int $markingSessionId = null;
    public array $attendanceMap = [];  // enrollment_id => status

    // Announcements
    public $announcements;
    public string $annTitle  = '';
    public string $annBody   = '';
    public bool   $annPinned = false;

    public function mount(IntakeModule $intakeModule)
    {
        $this->intakeModule = $intakeModule->load(['module', 'trimester']);
        $this->activeTab    = request('tab', 'overview');
        $this->loadAll();
    }

    protected function loadAll(): void
    {
        $im = $this->intakeModule;

        $this->students = Enrollment::forTrimester($im->trimester_id)
            ->with('student.user')
            ->get();

        $this->materials     = Material::where('intake_module_id', $im->id)->latest()->get();
        $this->assessments   = Assessment::where('intake_module_id', $im->id)->latest()->get();
        $this->sessions      = ModuleSession::where('intake_module_id', $im->id)->orderByDesc('starts_at')->get();
        $this->announcements = Announcement::where('intake_module_id', $im->id)
            ->orderByDesc('is_pinned')->orderByDesc('created_at')->get();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->markingSessionId   = null;
        $this->gradingSubmissionId = null;
    }

    /* ------------------------------------------------------------------ Materials */

    public function uploadMaterial(): void
    {
        $this->validate([
            'materialFile'        => 'required|file|max:51200',
            'materialTitle'       => 'required|string|max:255',
            'materialDescription' => 'nullable|string|max:1000',
        ]);

        $path = $this->materialFile->store('materials/' . $this->intakeModule->id, 'local');

        Material::create([
            'intake_module_id' => $this->intakeModule->id,
            'title'            => $this->materialTitle,
            'file_path'        => $path,
            'original_name'    => $this->materialFile->getClientOriginalName(),
            'mime'             => $this->materialFile->getMimeType(),
            'type'             => 'document',
            'uploaded_by'      => auth()->id(),
        ]);

        $this->materialFile = null;
        $this->materialTitle = '';
        $this->materialDescription = '';

        $this->materials = Material::where('intake_module_id', $this->intakeModule->id)->latest()->get();
        $this->dispatch('toast', type: 'success', message: 'Material uploaded.');
    }

    public function deleteMaterial(int $id): void
    {
        $m = Material::findOrFail($id);
        Storage::disk('local')->delete($m->file_path);
        $m->delete();
        $this->materials = Material::where('intake_module_id', $this->intakeModule->id)->latest()->get();
        $this->dispatch('toast', type: 'success', message: 'Material deleted.');
    }

    /* ------------------------------------------------------------------ Assessments */

    public function createAssessment(): void
    {
        $this->validate([
            'aType'         => 'required|in:CAT,Exam,Assignment,Quiz',
            'aTitle'        => 'required|string|max:255',
            'aDueOn'        => 'nullable|date',
            'aMaxMarks'     => 'required|integer|min:1|max:999',
            'aInstructions' => 'nullable|string',
            'aFile'         => 'nullable|file|max:10240',
        ]);

        $path = $this->aFile ? $this->aFile->store('assessments/' . $this->intakeModule->id, 'local') : null;

        Assessment::create([
            'intake_module_id' => $this->intakeModule->id,
            'type'             => $this->aType,
            'title'            => $this->aTitle,
            'due_on'           => $this->aDueOn ?: null,
            'max_marks'        => $this->aMaxMarks,
            'instructions'     => $this->aInstructions,
            'file_path'        => $path,
            'original_name'    => $path ? $this->aFile->getClientOriginalName() : null,
        ]);

        $this->aType = 'CAT'; $this->aTitle = ''; $this->aDueOn = '';
        $this->aMaxMarks = 100; $this->aInstructions = ''; $this->aFile = null;

        $this->assessments = Assessment::where('intake_module_id', $this->intakeModule->id)->latest()->get();
        $this->dispatch('toast', type: 'success', message: 'Assessment created.');
    }

    public function startGrading(int $submissionId): void
    {
        $this->gradingSubmissionId = $submissionId;
        $sub = AssessmentSubmission::find($submissionId);
        $this->gradeMark     = $sub?->mark ?? '';
        $this->gradeFeedback = $sub?->feedback ?? '';
    }

    public function saveGrade(): void
    {
        $this->validate([
            'gradeMark'     => 'required|numeric|min:0',
            'gradeFeedback' => 'nullable|string|max:2000',
        ]);

        $sub = AssessmentSubmission::findOrFail($this->gradingSubmissionId);
        $sub->update([
            'mark'      => $this->gradeMark,
            'feedback'  => $this->gradeFeedback,
            'status'    => 'graded',
            'graded_at' => now(),
        ]);

        $this->gradingSubmissionId = null;
        $this->gradeMark = '';
        $this->gradeFeedback = '';
        $this->dispatch('toast', type: 'success', message: 'Grade saved.');
    }

    /* ------------------------------------------------------------------ Sessions */

    public function createSession(): void
    {
        $this->validate([
            'sessionStartsAt' => 'required|date',
            'sessionEndsAt'   => 'nullable|date|after:sessionStartsAt',
            'sessionRoom'     => 'nullable|string|max:255',
            'sessionTopic'    => 'nullable|string|max:255',
        ]);

        $session = ModuleSession::create([
            'intake_module_id' => $this->intakeModule->id,
            'starts_at'        => $this->sessionStartsAt,
            'ends_at'          => $this->sessionEndsAt ?: null,
            'room'             => $this->sessionRoom ?: null,
            'topic'            => $this->sessionTopic ?: null,
        ]);

        // Create pending attendance records for all enrolled students
        foreach ($this->students as $enrollment) {
            Attendance::firstOrCreate([
                'module_session_id' => $session->id,
                'enrollment_id'     => $enrollment->id,
            ], ['status' => 'absent']);
        }

        $this->sessionStartsAt = ''; $this->sessionEndsAt = '';
        $this->sessionRoom = ''; $this->sessionTopic = '';

        $this->sessions = ModuleSession::where('intake_module_id', $this->intakeModule->id)->orderByDesc('starts_at')->get();
        $this->dispatch('toast', type: 'success', message: 'Session created.');
    }

    public function startMarkingAttendance(int $sessionId): void
    {
        $this->markingSessionId = $sessionId;
        $records = Attendance::where('module_session_id', $sessionId)->get()->keyBy('enrollment_id');

        $this->attendanceMap = [];
        foreach ($this->students as $enrollment) {
            $this->attendanceMap[$enrollment->id] = $records->get($enrollment->id)?->status ?? 'absent';
        }
    }

    public function saveAttendance(): void
    {
        foreach ($this->attendanceMap as $enrollmentId => $status) {
            Attendance::updateOrCreate(
                ['module_session_id' => $this->markingSessionId, 'enrollment_id' => $enrollmentId],
                ['status' => $status]
            );
        }

        $this->markingSessionId = null;
        $this->attendanceMap    = [];
        $this->dispatch('toast', type: 'success', message: 'Attendance saved.');
    }

    /* ------------------------------------------------------------------ Announcements */

    public function postAnnouncement(): void
    {
        $this->validate([
            'annTitle'  => 'required|string|max:255',
            'annBody'   => 'required|string',
            'annPinned' => 'boolean',
        ]);

        Announcement::create([
            'intake_module_id' => $this->intakeModule->id,
            'lecturer_id'      => auth()->user()->lecturer?->id,
            'title'            => $this->annTitle,
            'body'             => $this->annBody,
            'is_pinned'        => $this->annPinned,
            'published_at'     => now(),
        ]);

        $this->annTitle = ''; $this->annBody = ''; $this->annPinned = false;
        $this->announcements = Announcement::where('intake_module_id', $this->intakeModule->id)
            ->orderByDesc('is_pinned')->orderByDesc('created_at')->get();
        $this->dispatch('toast', type: 'success', message: 'Announcement posted.');
    }

    public function deleteAnnouncement(int $id): void
    {
        Announcement::findOrFail($id)->delete();
        $this->announcements = Announcement::where('intake_module_id', $this->intakeModule->id)
            ->orderByDesc('is_pinned')->orderByDesc('created_at')->get();
        $this->dispatch('toast', type: 'success', message: 'Announcement deleted.');
    }

}; ?>

@push('styles')
<style>
.nav-tabs .nav-link { color: #555; font-weight: 500; }
.nav-tabs .nav-link.active { color: #0c314d; border-bottom: 2px solid #0c314d; font-weight: 600; }
.tab-card { border: none; border-radius: 12px; }
</style>
@endpush

<div>
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('lecturer.dashboard') }}" class="text-muted small">
                <iconify-icon icon="solar:arrow-left-linear"></iconify-icon> My Modules
            </a>
            <h4 class="fw-bold mb-0 mt-1">{{ $intakeModule->module->title ?? 'Module' }}</h4>
            <span class="text-muted small">{{ $intakeModule->trimester?->name }} {{ $intakeModule->trimester?->academicYear?->name }}</span>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-primary-subtle text-primary px-3 py-2">
                {{ $students->count() }} Students
            </span>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-4">
        @foreach(['overview' => 'Overview', 'materials' => 'Materials', 'assessments' => 'Assessments', 'sessions' => 'Sessions & Attendance', 'announcements' => 'Announcements'] as $key => $label)
        <li class="nav-item">
            <button class="nav-link {{ $activeTab === $key ? 'active' : '' }}"
                    wire:click="setTab('{{ $key }}')">
                {{ $label }}
            </button>
        </li>
        @endforeach
    </ul>

    {{-- ====================================================== OVERVIEW --}}
    @if($activeTab === 'overview')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Module Info</h6>
                    <div class="mb-2">
                        <div class="text-muted small">Module</div>
                        <div class="fw-semibold">{{ $intakeModule->module->title ?? '—' }}</div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small">Trimester</div>
                        <div class="fw-semibold">{{ $intakeModule->trimester?->name ?? '—' }} {{ $intakeModule->trimester?->academicYear?->name }}</div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small">Students Enrolled</div>
                        <div class="fw-semibold">{{ $students->count() }}</div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small">Total Sessions</div>
                        <div class="fw-semibold">{{ $sessions->count() }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Assessments</div>
                        <div class="fw-semibold">{{ $assessments->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Quick Actions</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <button wire:click="setTab('materials')"
                                    class="btn btn-outline-primary w-100 d-flex align-items-center gap-2">
                                <iconify-icon icon="solar:upload-bold-duotone"></iconify-icon>
                                Upload Material
                            </button>
                        </div>
                        <div class="col-6">
                            <button wire:click="setTab('assessments')"
                                    class="btn btn-outline-success w-100 d-flex align-items-center gap-2">
                                <iconify-icon icon="solar:clipboard-add-bold-duotone"></iconify-icon>
                                Create Assessment
                            </button>
                        </div>
                        <div class="col-6">
                            <button wire:click="setTab('sessions')"
                                    class="btn btn-outline-warning w-100 d-flex align-items-center gap-2">
                                <iconify-icon icon="solar:calendar-add-bold-duotone"></iconify-icon>
                                Add Session
                            </button>
                        </div>
                        <div class="col-6">
                            <button wire:click="setTab('announcements')"
                                    class="btn btn-outline-secondary w-100 d-flex align-items-center gap-2">
                                <iconify-icon icon="solar:bell-bold-duotone"></iconify-icon>
                                Post Announcement
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ====================================================== MATERIALS --}}
    @if($activeTab === 'materials')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Upload Material</h6>
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" wire:model="materialTitle" placeholder="e.g. Week 1 Notes">
                        @error('materialTitle') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" wire:model="materialFile">
                        @error('materialFile') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <button class="btn btn-primary w-100" wire:click="uploadMaterial"
                            wire:loading.attr="disabled">
                        <span wire:loading wire:target="uploadMaterial" class="spinner-border spinner-border-sm me-1"></span>
                        Upload
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Uploaded Materials ({{ $materials->count() }})</h6>
                    @forelse($materials as $m)
                    <div class="d-flex align-items-center p-3 mb-2 rounded border">
                        <div style="width:40px;height:40px;border-radius:8px;background:#e9ecef;display:flex;align-items:center;justify-content:center;font-size:18px;color:#0c314d;flex-shrink:0;">
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
                           class="btn btn-sm btn-outline-primary me-2">
                            <iconify-icon icon="solar:download-bold-duotone"></iconify-icon>
                        </a>
                        <button wire:click="deleteMaterial({{ $m->id }})"
                                wire:confirm="Delete this material?"
                                class="btn btn-sm btn-outline-danger">
                            <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                        </button>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4">No materials uploaded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ====================================================== ASSESSMENTS --}}
    @if($activeTab === 'assessments')
    <div class="row g-3">
        {{-- Create form --}}
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Create Assessment</h6>
                    <div class="mb-2">
                        <label class="form-label">Type</label>
                        <select class="form-select" wire:model="aType">
                            <option value="CAT">CAT (30%)</option>
                            <option value="Exam">End-Term Exam (70%)</option>
                            <option value="Assignment">Assignment</option>
                            <option value="Quiz">Quiz</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" wire:model="aTitle">
                        @error('aTitle') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" wire:model="aDueOn">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Max Marks</label>
                            <input type="number" class="form-control" wire:model="aMaxMarks" min="1">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Instructions</label>
                        <textarea class="form-control" wire:model="aInstructions" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attach File (optional)</label>
                        <input type="file" class="form-control" wire:model="aFile">
                    </div>
                    <button class="btn btn-success w-100" wire:click="createAssessment">
                        <iconify-icon icon="solar:add-circle-bold-duotone"></iconify-icon> Create
                    </button>
                </div>
            </div>
        </div>

        {{-- Assessment list + grading --}}
        <div class="col-lg-8">
            @if($gradingSubmissionId)
            {{-- Grading panel --}}
            @php $sub = $assessments->flatMap->submissions->firstWhere('id', $gradingSubmissionId) ?? \App\Models\AssessmentSubmission::with('enrollment.student.user', 'assessment')->find($gradingSubmissionId); @endphp
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">
                            Grade: {{ $sub?->enrollment?->student?->user?->name }}
                            — {{ $sub?->assessment?->title }}
                        </h6>
                        <button wire:click="$set('gradingSubmissionId', null)" class="btn btn-sm btn-outline-secondary">
                            Cancel
                        </button>
                    </div>
                    @if($sub?->file_path)
                    <div class="mb-3">
                        <a href="{{ Storage::url($sub->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon> View Submission
                        </a>
                    </div>
                    @endif
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label">Mark (out of {{ $sub?->assessment?->max_marks }})</label>
                            <input type="number" class="form-control" wire:model="gradeMark" min="0" max="{{ $sub?->assessment?->max_marks }}">
                            @error('gradeMark') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-8">
                            <label class="form-label">Feedback</label>
                            <textarea class="form-control" wire:model="gradeFeedback" rows="2"></textarea>
                        </div>
                    </div>
                    <button class="btn btn-success mt-3" wire:click="saveGrade">
                        <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon> Save Grade
                    </button>
                </div>
            </div>
            @endif

            @forelse($assessments as $assessment)
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <span class="badge {{ $assessment->type === 'CAT' ? 'bg-info' : ($assessment->type === 'Exam' ? 'bg-danger' : 'bg-secondary') }} me-2">
                                {{ $assessment->type }}
                            </span>
                            <strong>{{ $assessment->title }}</strong>
                        </div>
                        <div class="text-muted small">
                            Max: {{ $assessment->max_marks }}
                            @if($assessment->due_on)
                                · Due {{ $assessment->due_on->format('d M Y') }}
                            @endif
                        </div>
                    </div>

                    @php
                        $subs = \App\Models\AssessmentSubmission::where('assessment_id', $assessment->id)
                            ->with('enrollment.student.user')
                            ->get();
                        $gradedCount   = $subs->whereNotNull('graded_at')->count();
                        $submittedCount = $subs->whereNotNull('submitted_at')->count();
                    @endphp

                    <div class="text-muted small mb-2">
                        {{ $submittedCount }} submitted · {{ $gradedCount }} graded · {{ $students->count() - $submittedCount }} not submitted
                    </div>

                    @if($subs->whereNotNull('submitted_at')->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Submitted</th>
                                    <th>Mark</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subs->whereNotNull('submitted_at') as $s)
                                <tr>
                                    <td>{{ $s->enrollment?->student?->user?->name ?? '—' }}</td>
                                    <td class="small text-muted">{{ $s->submitted_at?->format('d M H:i') }}</td>
                                    <td>{{ $s->mark !== null ? $s->mark . '/' . $assessment->max_marks : '—' }}</td>
                                    <td>
                                        @if($s->graded_at)
                                            <span class="badge bg-success">Graded</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button wire:click="startGrading({{ $s->id }})"
                                                class="btn btn-sm btn-outline-primary">
                                            {{ $s->graded_at ? 'Re-grade' : 'Grade' }}
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="card shadow-sm">
                <div class="card-body text-center py-5 text-muted">No assessments yet. Create one on the left.</div>
            </div>
            @endforelse
        </div>
    </div>
    @endif

    {{-- ====================================================== SESSIONS --}}
    @if($activeTab === 'sessions')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Add Session</h6>
                    <div class="mb-2">
                        <label class="form-label">Start Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" wire:model="sessionStartsAt">
                        @error('sessionStartsAt') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label">End Date & Time</label>
                        <input type="datetime-local" class="form-control" wire:model="sessionEndsAt">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Room / Venue</label>
                        <input type="text" class="form-control" wire:model="sessionRoom" placeholder="e.g. Room 4A / Online">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Topic</label>
                        <input type="text" class="form-control" wire:model="sessionTopic" placeholder="Session topic">
                    </div>
                    <button class="btn btn-primary w-100" wire:click="createSession">
                        <iconify-icon icon="solar:calendar-add-bold-duotone"></iconify-icon> Add Session
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            {{-- Attendance marking panel --}}
            @if($markingSessionId)
            @php $sess = $sessions->firstWhere('id', $markingSessionId); @endphp
            <div class="card shadow-sm mb-3 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">
                            Mark Attendance — {{ $sess?->starts_at->format('d M Y H:i') }}
                        </h6>
                        <button wire:click="$set('markingSessionId', null)" class="btn btn-sm btn-outline-secondary">
                            Cancel
                        </button>
                    </div>
                    @foreach($students as $enrollment)
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div class="fw-semibold">{{ $enrollment->student?->user?->name }}</div>
                        <div class="d-flex gap-2">
                            @foreach(['present' => 'success', 'late' => 'warning', 'absent' => 'danger'] as $status => $color)
                            <button
                                wire:click="$set('attendanceMap.{{ $enrollment->id }}', '{{ $status }}')"
                                class="btn btn-sm {{ ($attendanceMap[$enrollment->id] ?? 'absent') === $status ? 'btn-'.$color : 'btn-outline-'.$color }}">
                                {{ ucfirst($status) }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                    <button class="btn btn-success mt-3 w-100" wire:click="saveAttendance">
                        <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon> Save Attendance
                    </button>
                </div>
            </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Sessions ({{ $sessions->count() }})</h6>
                    @forelse($sessions as $session)
                    <div class="d-flex align-items-center p-3 mb-2 rounded border">
                        <div class="text-center me-3" style="min-width:48px;">
                            <div class="fw-bold" style="font-size:22px;color:#0c314d;">
                                {{ $session->starts_at->format('d') }}
                            </div>
                            <div class="text-muted" style="font-size:11px;">
                                {{ $session->starts_at->format('M Y') }}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">
                                {{ $session->starts_at->format('H:i') }}
                                @if($session->ends_at) – {{ $session->ends_at->format('H:i') }} @endif
                                @if($session->room) <span class="text-muted">· {{ $session->room }}</span> @endif
                            </div>
                            @if($session->topic)
                            <div class="text-muted" style="font-size:11px;">{{ $session->topic }}</div>
                            @endif
                            @php
                                $attCount = \App\Models\Attendance::where('module_session_id', $session->id)->where('status', 'present')->count();
                            @endphp
                            <div class="text-muted" style="font-size:11px;">{{ $attCount }} present</div>
                        </div>
                        <button wire:click="startMarkingAttendance({{ $session->id }})"
                                class="btn btn-sm btn-outline-primary">
                            <iconify-icon icon="solar:clipboard-check-bold-duotone"></iconify-icon>
                            Mark
                        </button>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4">No sessions yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ====================================================== ANNOUNCEMENTS --}}
    @if($activeTab === 'announcements')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Post Announcement</h6>
                    <div class="mb-2">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" wire:model="annTitle">
                        @error('annTitle') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Body <span class="text-danger">*</span></label>
                        <textarea class="form-control" wire:model="annBody" rows="5"></textarea>
                        @error('annBody') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" wire:model="annPinned" id="pin-ann">
                        <label class="form-check-label" for="pin-ann">Pin this announcement</label>
                    </div>
                    <button class="btn btn-primary w-100" wire:click="postAnnouncement">
                        <iconify-icon icon="solar:bell-bold-duotone"></iconify-icon> Post
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Announcements ({{ $announcements->count() }})</h6>
                    @forelse($announcements as $ann)
                    <div class="p-3 mb-2 rounded border {{ $ann->is_pinned ? 'border-warning bg-warning-subtle' : '' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="fw-semibold">
                                @if($ann->is_pinned)
                                    <iconify-icon icon="solar:pin-bold-duotone" class="text-warning me-1"></iconify-icon>
                                @endif
                                {{ $ann->title }}
                            </div>
                            <button wire:click="deleteAnnouncement({{ $ann->id }})"
                                    wire:confirm="Delete this announcement?"
                                    class="btn btn-sm btn-outline-danger">
                                <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                            </button>
                        </div>
                        <div class="mt-1 text-muted small">{{ $ann->published_at?->format('d M Y H:i') }}</div>
                        <div class="mt-2" style="white-space:pre-line;">{{ $ann->body }}</div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4">No announcements yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
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
