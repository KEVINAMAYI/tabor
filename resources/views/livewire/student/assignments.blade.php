<?php

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {

    use WithFileUploads;

    public $assignments = [];
    public $answerFile;
    public $selectedAssessmentId; // we’ll tie submission, not just assessment

    public function mount()
    {
        $student = auth()->user()->student;

        if (!$student) {
            // No student record → just set assignments to an empty collection
            $this->assignments = collect();
            return;
        }

        $this->assignments = AssessmentSubmission::with([
            'assessment.intakeModule.module.course',
            'assessment.intakeModule.module.defaultLecturer',
            'enrollment',
        ])
            ->whereHas('enrollment', fn($q) => $q->where('student_id', $student->id))
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($submission) {
                $a = $submission->assessment;

                $submission->course_name = $a->intakeModule->module->course->title ?? 'N/A';
                $submission->module_name = $a->intakeModule->module->title ?? 'N/A';
                $submission->lecturer_name = $a->intakeModule->module->defaultLecturer
                    ? $a->intakeModule->module->defaultLecturer->first_name . ' ' . $a->intakeModule->module->defaultLecturer->last_name
                    : 'N/A';
                $submission->due_on = $a->due_on;

                return $submission;
            });
    }


    public function submitAssessment()
    {

        $this->validate([
            'answerFile' => 'required|file|max:10240',
        ]);

        $submission = AssessmentSubmission::findOrFail($this->selectedAssessmentId);

        $path = $this->answerFile->store('submissions', 'public');

        $submission->update([
            'file_path' => $path,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->reset('answerFile');

        $this->dispatch('submission-success');
        LivewireAlert::text('Assessment submitted successfully!')
            ->success()->toast()->position('top-end')->show();
    }

}; ?>

@push('styles')
    <style>
        .assignments-table thead {
            background-color: #20425b;
            color: #fff;
        }

        .assignments-table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .assignments-table td {
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .btn-outline-primary {
            border-color: #20425b;
            color: #20425b;
        }

        .btn-outline-primary:hover {
            background-color: #20425b;
            color: #fff;
        }

        .btn-outline-success {
            border-color: #f8a952;
            color: #f8a952;
        }

        .btn-outline-success:hover {
            background-color: #f8a952;
            color: #fff;
        }

        .badge-warning-custom {
            background-color: #f8a952;
            color: #20425b;
            font-weight: 600;
        }

        .badge-primary-custom {
            background-color: #20425b;
            color: #fff;
            font-weight: 600;
        }
    </style>
@endpush

<div class="row">
    <div class="col-12">
        <div class="widget-content searchable-container list">

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header" style="background-color:#20425b; color:#fff;">
                    <h5 style="color:white;" class="mb-0">📚 Assignments & Submissions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table assignments-table table-hover align-middle text-nowrap mb-0">
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Due Date</th>
                                <th>Max Marks</th>
                                <th>Status</th>
                                <th>Submitted At</th>
                                <th class="text-center">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($assignments as $submission)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $submission->assessment->title }} <br>
                                        <small class="text-muted">{{ $submission->module_name }}</small><br>
                                        <strong style="color:#f8a952">{{ $submission->course_name }}</strong>
                                    </td>
                                    <td>{{ ucfirst($submission->assessment->type) }}</td>
                                    <td>
                                        {{ $submission->assessment->due_on
                                            ? \Carbon\Carbon::parse($submission->assessment->due_on)->format('d M Y')
                                            : '-' }}
                                    </td>
                                    <td>{{ $submission->assessment->max_marks }}</td>
                                    <td>
                                        @switch($submission->status)
                                            @case('pending')
                                                <span class="badge badge-warning-custom">Pending</span>
                                                @break
                                            @case('submitted')
                                                <span class="badge badge-primary-custom">Submitted</span>
                                                @break
                                            @case('graded')
                                                <span class="badge bg-success">Graded</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">N/A</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        {{ $submission->submitted_at
                                            ? $submission->submitted_at->format('d M Y H:i')
                                            : '-' }}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            {{-- View file --}}
                                            @if($submission->file_path)
                                                <a href="{{ asset('storage/' . $submission->file_path) }}"
                                                   target="_blank"
                                                   class="btn btn-sm btn-outline-success"
                                                   title="View">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                                <a href="{{ asset('storage/' . $submission->file_path) }}" download
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Download">
                                                    <i class="ti ti-download"></i>
                                                </a>
                                            @endif

                                            {{-- Submit / Resubmit --}}
                                            @if(in_array($submission->status, ['pending', 'submitted']))
                                                <button class="btn btn-sm btn-outline-primary ms-2"
                                                        data-bs-toggle="modal"
                                                        wire:click="$set('selectedAssessmentId', {{ $submission->id }})"
                                                        data-bs-target="#submitAssessmentModal"
                                                        onclick="document.getElementById('modalAssessmentTitle').innerText = '{{ $submission->assessment->title }}'">
                                                    <i class="ti ti-upload"></i>
                                                    {{ $submission->status === 'pending' ? 'Submit' : 'Resubmit' }}
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No assignments found.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div wire:ignore class="modal fade" id="submitAssessmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form wire:submit.prevent="submitAssessment" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Submit Assessment: <span id="modalAssessmentTitle"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" wire:model="selectedAssessmentId">
                        <div class="mb-3">
                            <label for="answerFile" class="form-label">Upload Answer</label>
                            <input type="file" class="form-control" wire:model="answerFile" id="answerFile" required>
                            @error('answerFile') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check"></i> Submit
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
    <script src="assets/js/apps/contact.js"></script>

    <script>
        window.addEventListener('submission-success', () => {
            bootstrap.Modal.getInstance(document.getElementById('submitAssessmentModal'))?.hide();
        });
    </script>
@endpush




