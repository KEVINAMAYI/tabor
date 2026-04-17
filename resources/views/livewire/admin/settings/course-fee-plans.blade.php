<?php

use App\Models\Course;
use App\Models\FeeDefinition;
use App\Models\CourseFeePlan;
use Livewire\Volt\Component;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $courseFeePlanId = null;

    public $course_id = '';
    public $fee_definition_id = '';
    public $charge_timing = 'on_enrollment';
    public $trimester_sequence = '';
    public $amount = '';
    public $mandatory = true;

    public $courseFilter = '';
    public $timingFilter = '';

    public function openCourseFeePlanModal()
    {
        $this->resetForm();
        $this->dispatch('show-course-fee-plan-modal');
    }

    public function editCourseFeePlan($id)
    {
        $plan = CourseFeePlan::findOrFail($id);

        $this->courseFeePlanId = $plan->id;
        $this->course_id = $plan->course_id;
        $this->fee_definition_id = $plan->fee_definition_id;
        $this->charge_timing = $plan->charge_timing;
        $this->trimester_sequence = $plan->trimester_sequence;
        $this->amount = $plan->amount;
        $this->mandatory = $plan->mandatory;

        $this->dispatch('show-course-fee-plan-modal');
    }

    public function saveCourseFeePlan()
    {
        $this->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'fee_definition_id' => ['required', 'exists:fee_definitions,id'],
            'charge_timing' => ['required', 'in:on_enrollment,every_trimester,specific_trimester,on_completion'],
            'trimester_sequence' => ['nullable', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        if ($this->charge_timing === 'specific_trimester' && blank($this->trimester_sequence)) {
            $this->addError('trimester_sequence', 'Trimester sequence is required for specific trimester charges.');
            return;
        }

        if ($this->charge_timing !== 'specific_trimester') {
            $this->trimester_sequence = null;
        }

        CourseFeePlan::updateOrCreate(
            ['id' => $this->courseFeePlanId],
            [
                'course_id' => $this->course_id,
                'fee_definition_id' => $this->fee_definition_id,
                'charge_timing' => $this->charge_timing,
                'trimester_sequence' => $this->trimester_sequence ?: null,
                'amount' => $this->amount,
                'mandatory' => (bool) $this->mandatory,
            ],
        );

        $this->dispatch('hide-course-fee-plan-modal');
        $this->resetForm();

        LivewireAlert::text('Course fee plan saved successfully.')->success()->toast()->position('top-end')->show();
    }

    public function deleteCourseFeePlan($id)
    {
        CourseFeePlan::findOrFail($id)->delete();

        LivewireAlert::text('Course fee plan deleted successfully.')->success()->toast()->position('top-end')->show();
    }

    public function updatedChargeTiming()
    {
        if ($this->charge_timing !== 'specific_trimester') {
            $this->trimester_sequence = '';
        }
    }

    protected function resetForm()
    {
        $this->courseFeePlanId = null;
        $this->course_id = '';
        $this->fee_definition_id = '';
        $this->charge_timing = 'on_enrollment';
        $this->trimester_sequence = '';
        $this->amount = '';
        $this->mandatory = true;
    }

    public function with()
    {
        $plans = CourseFeePlan::query()
            ->with(['course', 'feeDefinition'])
            ->when(filled($this->courseFilter), fn($q) => $q->where('course_id', $this->courseFilter))
            ->when(filled($this->timingFilter), fn($q) => $q->where('charge_timing', $this->timingFilter))
            ->latest()
            ->get();

        return [
            'plans' => $plans,
            'courses' => Course::where('active', true)->orderBy('title')->get(),
            'feeDefinitions' => FeeDefinition::where('active', true)->orderBy('name')->get(),
        ];
    }
};

?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h4 class="fw-semibold mb-1">Course Fee Plans</h4>
                <p class="text-muted small mb-0">
                    Define how each fee applies to a course, including trimester-specific amounts.
                </p>
            </div>

            <button class="btn btn-primary rounded-3" wire:click="openCourseFeePlanModal">
                <i class="ti ti-plus me-1"></i> Add Course Fee Plan
            </button>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label small text-muted">Filter by Course</label>
                <select class="form-select" wire:model.live="courseFilter">
                    <option value="">All courses</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->title }} ({{ $course->code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small text-muted">Filter by Timing</label>
                <select class="form-select" wire:model.live="timingFilter">
                    <option value="">All timings</option>
                    <option value="on_enrollment">On Enrollment</option>
                    <option value="every_trimester">Every Trimester</option>
                    <option value="specific_trimester">Specific Trimester</option>
                    <option value="on_completion">On Completion</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Fee</th>
                        <th>Timing</th>
                        <th>Trimester</th>
                        <th>Amount</th>
                        <th>Mandatory</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td class="fw-semibold">{{ $plan->course->title }} ({{ $plan->course->code }})</td>
                            <td>{{ $plan->feeDefinition->name ?? '—' }}</td>
                            <td>
                                @php
                                    $timingLabel = match ($plan->charge_timing) {
                                        'on_enrollment' => 'On Enrollment',
                                        'every_trimester' => 'Every Trimester',
                                        'specific_trimester' => 'Specific Trimester',
                                        'on_completion' => 'On Completion',
                                        default => ucfirst($plan->charge_timing),
                                    };
                                @endphp
                                {{ $timingLabel }}
                            </td>
                            <td>
                                {{ $plan->trimester_sequence ? 'T' . $plan->trimester_sequence : '—' }}
                            </td>
                            <td>KES {{ number_format($plan->amount, 2) }}</td>
                            <td>{{ $plan->mandatory ? 'Yes' : 'No' }}</td>
                            <td class="text-end">
                                <button class="btn btn-light btn-sm"
                                    wire:click="editCourseFeePlan({{ $plan->id }})">
                                    Edit
                                </button>
                                <button class="btn btn-light btn-sm text-danger"
                                    wire:click="deleteCourseFeePlan({{ $plan->id }})"
                                    onclick="return confirm('Are you sure you want to delete this course fee plan?') || event.stopImmediatePropagation();">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No course fee plans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal --}}
    <div class="modal fade" id="courseFeePlanModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $courseFeePlanId ? 'Edit Course Fee Plan' : 'Add Course Fee Plan' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form wire:submit.prevent="saveCourseFeePlan">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course</label>
                                <select class="form-select" wire:model="course_id">
                                    <option value="">Select course</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}">
                                            {{ $course->title }} ({{ $course->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('course_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fee Definition</label>
                                <select class="form-select" wire:model="fee_definition_id">
                                    <option value="">Select fee</option>
                                    @foreach ($feeDefinitions as $fee)
                                        <option value="{{ $fee->id }}">
                                            {{ $fee->name }} ({{ ucfirst($fee->scope) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('fee_definition_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Charge Timing</label>
                                <select class="form-select" wire:model.live="charge_timing">
                                    <option value="on_enrollment">On Enrollment</option>
                                    <option value="every_trimester">Every Trimester</option>
                                    <option value="specific_trimester">Specific Trimester</option>
                                    <option value="on_completion">On Completion</option>
                                </select>
                                @error('charge_timing')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Trimester Sequence</label>
                                <input type="number" min="1" class="form-control"
                                    wire:model="trimester_sequence" @disabled($charge_timing !== 'specific_trimester')>
                                @error('trimester_sequence')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                                <small class="text-muted">
                                    Required only for specific trimester charges.
                                </small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" min="0" class="form-control"
                                    wire:model="amount">
                                @error('amount')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mandatory</label>
                            <select class="form-select" wire:model="mandatory">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        {{-- <div class="alert alert-light border rounded-3 mb-0">
                            Use multiple rows for fees that vary by trimester.
                            Example: Exam Fee can be added as T1, T2, T3 with different amounts.
                        </div> --}}
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-primary rounded-3">Save</button>
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function courseFeePlanModal() {
            const el = document.getElementById('courseFeePlanModal');
            if (!el) return null;
            return bootstrap.Modal.getOrCreateInstance(el);
        }

        window.addEventListener('show-course-fee-plan-modal', () => courseFeePlanModal()?.show());
        window.addEventListener('hide-course-fee-plan-modal', () => courseFeePlanModal()?.hide());
    </script>
@endpush
