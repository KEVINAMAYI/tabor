<?php

use App\Models\StudentFeeItem;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\FeeDefinition;
use App\Models\Trimester;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $studentFilter = '';
    public $statusFilter = '';
    public $enrollmentFilter = '';
    public $perPage = 10;

    public $feeItemId = null;

    public $student_id = '';
    public $enrollment_id = '';
    public $enrollment_progression_id = '';
    public $fee_definition_id = '';
    public $trimester_id = '';
    public $description = '';
    public $amount = '';
    public $charge_date = '';
    public $due_date = '';
    public $status = 'pending';

    public bool $isEditing = false;

    protected string $paginationTheme = 'bootstrap';

    public function rules()
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'enrollment_id' => ['nullable', 'exists:enrollments,id'],
            'enrollment_progression_id' => ['nullable', 'exists:enrollment_progressions,id'],
            'fee_definition_id' => ['nullable', 'exists:fee_definitions,id'],
            'trimester_id' => ['nullable', 'exists:trimesters,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'charge_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:pending,partial,paid,overdue,cancelled'],
        ];
    }

    public function mount(): void
    {
        $this->charge_date = now()->toDateString();
        $this->due_date = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStudentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedEnrollmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedStudentId($value): void
    {
        $this->enrollment_id = '';
        $this->enrollment_progression_id = '';
    }

    public function updatedEnrollmentId($value): void
    {
        $this->enrollment_progression_id = '';

        $enrollment = Enrollment::with('assignedStartTrimester')->find($value);

        if ($enrollment) {
            $this->student_id = $enrollment->student_id;
            $this->trimester_id = $enrollment->assigned_start_trimester_id;
        }
    }

    public function updatedEnrollmentProgressionId($value): void
    {
        $progression = EnrollmentProgression::find($value);

        if ($progression) {
            $this->enrollment_id = $progression->enrollment_id;
            $this->student_id = $progression->student_id;
            $this->trimester_id = $progression->trimester_id;
        }
    }

    public function updatedFeeDefinitionId($value): void
    {
        $definition = FeeDefinition::find($value);

        if ($definition) {
            $this->description = $definition->name;
            $this->amount = $this->amount ?: $definition->default_amount;
        }
    }

    public function with(): array
    {
        $query = StudentFeeItem::query()
            ->with(['student', 'enrollment.course', 'progression.trimester.academicYear', 'trimester.academicYear', 'feeDefinition', 'allocations'])

            /*
    |--------------------------------------------------------------------------
    | Hide Future Progression Fee Items
    |--------------------------------------------------------------------------
    |
    | Include:
    | - student-level fee items with no enrollment progression
    | - fee items whose progression sequence is <= the current active
    |   progression sequence for that same enrollment
    |
    */

            ->where(function ($q) {
                $q->whereNull('enrollment_progression_id')->orWhereExists(function ($sub) {
                    $sub->selectRaw(1)
                        ->from('enrollment_progressions as item_ep')
                        ->join('enrollment_progressions as current_ep', function ($join) {
                            $join->on('current_ep.enrollment_id', '=', 'item_ep.enrollment_id')->where('current_ep.status', '=', 'active');
                        })
                        ->whereColumn('item_ep.id', 'student_fee_items.enrollment_progression_id')
                        ->whereColumn('item_ep.enrollment_id', 'student_fee_items.enrollment_id')
                        ->whereColumn('item_ep.trimester_sequence', '<=', 'current_ep.trimester_sequence');
                });
            })

            ->when(filled($this->search), function ($q) {
                $q->where(function ($sub) {
                    $sub->where('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('student', function ($studentQuery) {
                            $studentQuery
                                ->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                ->orWhere('admission_number', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('enrollment.course', function ($courseQuery) {
                            $courseQuery->where('title', 'like', '%' . $this->search . '%')->orWhere('code', 'like', '%' . $this->search . '%');
                        });
                });
            })

            ->when(filled($this->studentFilter), fn($q) => $q->where('student_id', $this->studentFilter))
            ->when(filled($this->statusFilter), fn($q) => $q->where('status', $this->statusFilter))
            ->when(filled($this->enrollmentFilter), fn($q) => $q->where('enrollment_id', $this->enrollmentFilter));

        $summaryQuery = clone $query;

        return [
            'feeItems' => $query->latest('charge_date')->latest('id')->paginate($this->perPage),

            'students' => Student::query()->orderBy('first_name')->orderBy('last_name')->get(),

            'enrollments' => Enrollment::query()
                ->with(['student', 'course'])
                ->when(filled($this->student_id), fn($q) => $q->where('student_id', $this->student_id))
                ->latest()
                ->get(),

            'filterEnrollments' => Enrollment::query()
                ->with(['student', 'course'])
                ->latest()
                ->get(),

            'progressions' => EnrollmentProgression::query()
                ->with(['trimester.academicYear', 'enrollment.course'])
                ->when(filled($this->enrollment_id), fn($q) => $q->where('enrollment_id', $this->enrollment_id))
                ->orderBy('trimester_sequence')
                ->get(),

            'feeDefinitions' => FeeDefinition::query()->where('active', true)->orderBy('name')->get(),

            'trimesters' => Trimester::query()->with('academicYear')->orderBy('start_date')->get(),

            'summaryCharges' => (clone $summaryQuery)->sum('amount'),
            'summaryPaid' => (clone $summaryQuery)->sum('amount_paid'),
            'summaryBalance' => (clone $summaryQuery)->sum('balance'),
            'summaryCount' => (clone $summaryQuery)->count(),
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->dispatch('show-fee-item-modal');
    }

    public function editFeeItem(int $id): void
    {
        $item = StudentFeeItem::with('allocations')->findOrFail($id);

        $this->feeItemId = $item->id;
        $this->student_id = $item->student_id;
        $this->enrollment_id = $item->enrollment_id;
        $this->enrollment_progression_id = $item->enrollment_progression_id;
        $this->fee_definition_id = $item->fee_definition_id;
        $this->trimester_id = $item->trimester_id;
        $this->description = $item->description;
        $this->amount = $item->amount;
        $this->charge_date = optional($item->charge_date)->format('Y-m-d');
        $this->due_date = optional($item->due_date)->format('Y-m-d');
        $this->status = $item->status;

        $this->isEditing = true;

        $this->dispatch('show-fee-item-modal');
    }

    public function saveFeeItem(): void
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $amount = (float) $this->amount;

            if ($this->isEditing) {
                $item = StudentFeeItem::with('allocations')->findOrFail($this->feeItemId);

                $allocated = (float) $item->allocations()->sum('amount_allocated');

                if ($amount < $allocated) {
                    throw new \RuntimeException('Amount cannot be less than already allocated payments.');
                }

                $amountPaid = $allocated;
                $balance = max(0, $amount - $amountPaid);

                $item->update([
                    'student_id' => $this->student_id,
                    'enrollment_id' => $this->enrollment_id ?: null,
                    'enrollment_progression_id' => $this->enrollment_progression_id ?: null,
                    'fee_definition_id' => $this->fee_definition_id ?: null,
                    'trimester_id' => $this->trimester_id ?: null,
                    'description' => $this->description,
                    'amount' => $amount,
                    'amount_paid' => $amountPaid,
                    'balance' => $balance,
                    'charge_date' => $this->charge_date,
                    'due_date' => $this->due_date ?: null,
                    'status' => $this->resolvedStatus($balance, $amountPaid),
                ]);
            } else {
                StudentFeeItem::create([
                    'student_id' => $this->student_id,
                    'enrollment_id' => $this->enrollment_id ?: null,
                    'enrollment_progression_id' => $this->enrollment_progression_id ?: null,
                    'course_fee_plan_id' => null,
                    'fee_definition_id' => $this->fee_definition_id ?: null,
                    'trimester_id' => $this->trimester_id ?: null,
                    'description' => $this->description,
                    'amount' => $amount,
                    'amount_paid' => 0,
                    'balance' => $amount,
                    'charge_date' => $this->charge_date,
                    'due_date' => $this->due_date ?: null,
                    'status' => $amount > 0 ? 'pending' : 'paid',
                ]);
            }

            DB::commit();

            $this->dispatch('hide-fee-item-modal');
            $this->resetForm();

            LivewireAlert::text($this->isEditing ? 'Fee item updated successfully.' : 'Fee item created successfully.')
                ->success()
                ->toast()
                ->position('top-end')
                ->show();
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Failed saving student fee item', [
                'message' => $th->getMessage(),
                'fee_item_id' => $this->feeItemId,
            ]);

            LivewireAlert::text($th->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function deleteFeeItem(int $id): void
    {
        try {
            DB::beginTransaction();

            $item = StudentFeeItem::with('allocations')->findOrFail($id);

            if ($item->allocations()->exists() || (float) $item->amount_paid > 0) {
                throw new \RuntimeException('Cannot delete a fee item that already has payment allocations.');
            }

            $item->delete();

            DB::commit();

            LivewireAlert::text('Fee item deleted successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            DB::rollBack();

            LivewireAlert::text($th->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    protected function resolvedStatus(float $balance, float $amountPaid): string
    {
        if ($balance <= 0) {
            return 'paid';
        }

        if ($amountPaid > 0) {
            return 'partial';
        }

        return $this->status === 'overdue' ? 'overdue' : 'pending';
    }

    protected function resetForm(): void
    {
        $this->feeItemId = null;
        $this->student_id = '';
        $this->enrollment_id = '';
        $this->enrollment_progression_id = '';
        $this->fee_definition_id = '';
        $this->trimester_id = '';
        $this->description = '';
        $this->amount = '';
        $this->charge_date = now()->toDateString();
        $this->due_date = now()->toDateString();
        $this->status = 'pending';
        $this->isEditing = false;
    }
};

?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h4 class="fw-semibold mb-1">Student Fee Items</h4>
                <p class="text-muted small mb-0">
                    Manage posted student charges. Paid items are protected from unsafe deletion.
                </p>
            </div>

            <button type="button" class="btn btn-primary rounded-3" wire:click="openCreateModal">
                <i class="ti ti-plus me-1"></i> Add Fee Item
            </button>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="p-3 rounded-4 bg-primary-subtle">
                    <div class="small text-primary mb-1">Items</div>
                    <div class="fs-5 fw-bold text-primary">{{ number_format($summaryCount) }}</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 rounded-4 bg-light">
                    <div class="small text-muted mb-1">Charges</div>
                    <div class="fs-5 fw-bold">KES {{ number_format($summaryCharges, 2) }}</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 rounded-4 bg-success-subtle">
                    <div class="small text-success mb-1">Paid</div>
                    <div class="fs-5 fw-bold text-success">KES {{ number_format($summaryPaid, 2) }}</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 rounded-4 bg-danger-subtle">
                    <div class="small text-danger mb-1">Balance</div>
                    <div class="fs-5 fw-bold text-danger">KES {{ number_format($summaryBalance, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="card border bg-light-subtle mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Search</label>
                        <input type="text" class="form-control" wire:model.live.debounce.400ms="search"
                            placeholder="Student, course, fee...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small text-muted">Student</label>
                        <select class="form-select" wire:model.live="studentFilter">
                            <option value="">All students</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">
                                    {{ $student->first_name }} {{ $student->last_name }}
                                    @if ($student->admission_number)
                                        - {{ $student->admission_number }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small text-muted">Enrollment</label>
                        <select class="form-select" wire:model.live="enrollmentFilter">
                            <option value="">All enrollments</option>
                            @foreach ($filterEnrollments as $enrollment)
                                <option value="{{ $enrollment->id }}">
                                    {{ $enrollment->student?->first_name }} {{ $enrollment->student?->last_name }}
                                    - {{ $enrollment->course?->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted">Status</label>
                        <select class="form-select" wire:model.live="statusFilter">
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label small text-muted">Show</label>
                        <select wire:model.live="perPage" class="form-select">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Fee</th>
                        <th>Progression</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($feeItems as $item)
                        @php
                            $statusClass = match ($item->status) {
                                'paid' => 'bg-success-subtle text-success',
                                'partial' => 'bg-info-subtle text-info',
                                'overdue' => 'bg-danger-subtle text-danger',
                                'cancelled' => 'bg-secondary-subtle text-secondary',
                                default => 'bg-warning-subtle text-warning',
                            };

                            $hasAllocations = $item->allocations->count() > 0;
                        @endphp

                        <tr>
                            <td>{{ $loop->iteration + ($feeItems->currentPage() - 1) * $feeItems->perPage() }}</td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $item->student?->first_name }} {{ $item->student?->last_name }}
                                </div>
                                <div class="small text-muted">
                                    {{ $item->student?->admission_number ?? '—' }}
                                </div>
                            </td>

                            <td>
                                {{ $item->enrollment?->course?->title ?? '—' }}
                                @if ($item->enrollment?->course?->level)
                                    <div class="small text-muted">{{ $item->enrollment->course->level }}</div>
                                @endif
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $item->description }}</div>
                                <div class="small text-muted">
                                    {{ $item->feeDefinition?->name ?? 'Manual fee item' }}
                                </div>
                            </td>

                            <td>
                                @if ($item->progression)
                                    T{{ $item->progression->trimester_sequence }}
                                   {{--  -
                                    {{ $item->progression->trimester?->name ?? '—' }} --}}
                                @else
                                    {{ $item->trimester?->name ?? '—' }}
                                @endif
                            </td>

                            <td class="text-end fw-semibold">
                                KES {{ number_format($item->amount, 2) }}
                            </td>

                            <td class="text-end text-success">
                                KES {{ number_format($item->amount_paid, 2) }}
                            </td>

                            <td class="text-end {{ $item->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                KES {{ number_format($item->balance, 2) }}
                            </td>

                            <td>
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>

                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-3"
                                    wire:click="editFeeItem({{ $item->id }})">
                                    Edit
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-danger rounded-3"
                                    wire:click="deleteFeeItem({{ $item->id }})"
                                    onclick="return confirm('Delete this fee item? This is only allowed if it has no allocations.')"
                                    @disabled($hasAllocations || $item->amount_paid > 0)>
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="ti ti-receipt fs-1 d-block mb-2"></i>
                                No student fee items found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="d-flex justify-content-start align-items-center mt-3">
                {{ $feeItems->links() }}
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="feeItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">
                            {{ $isEditing ? 'Edit Fee Item' : 'Add Fee Item' }}
                        </h5>
                        <small class="text-muted">
                            Manual changes should only be used for corrections or special charges.
                        </small>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form wire:submit.prevent="saveFeeItem">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Student</label>
                                <select class="form-select" wire:model.live="student_id">
                                    <option value="">Select student</option>
                                    @foreach ($students as $student)
                                        <option value="{{ $student->id }}">
                                            {{ $student->first_name }} {{ $student->last_name }}
                                            @if ($student->admission_number)
                                                - {{ $student->admission_number }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('student_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Enrollment</label>
                                <select class="form-select" wire:model.live="enrollment_id">
                                    <option value="">No enrollment / student-level fee</option>
                                    @foreach ($enrollments as $enrollment)
                                        <option value="{{ $enrollment->id }}">
                                            {{ $enrollment->course?->title ?? 'Course' }}
                                            @if ($enrollment->course?->level)
                                                - {{ $enrollment->course->level }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('enrollment_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Progression</label>
                                <select class="form-select" wire:model.live="enrollment_progression_id">
                                    <option value="">No progression</option>
                                    @foreach ($progressions as $progression)
                                        <option value="{{ $progression->id }}">
                                            T{{ $progression->trimester_sequence }}
                                            - {{ $progression->trimester?->name }}
                                            {{ $progression->trimester?->academicYear?->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('enrollment_progression_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Trimester</label>
                                <select class="form-select" wire:model="trimester_id">
                                    <option value="">No trimester</option>
                                    @foreach ($trimesters as $trimester)
                                        <option value="{{ $trimester->id }}">
                                            {{ $trimester->name }} {{ $trimester->academicYear?->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('trimester_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fee Definition</label>
                                <select class="form-select" wire:model.live="fee_definition_id">
                                    <option value="">Manual fee / no definition</option>
                                    @foreach ($feeDefinitions as $definition)
                                        <option value="{{ $definition->id }}">
                                            {{ $definition->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('fee_definition_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" wire:model="description">
                                @error('description')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" min="0" class="form-control"
                                    wire:model="amount">
                                @error('amount')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Charge Date</label>
                                <input type="date" class="form-control" wire:model="charge_date">
                                @error('charge_date')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" class="form-control" wire:model="due_date">
                                @error('due_date')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            @if ($isEditing)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" wire:model="status">
                                        <option value="pending">Pending</option>
                                        <option value="partial">Partial</option>
                                        <option value="paid">Paid</option>
                                        <option value="overdue">Overdue</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    @error('status')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            @endif
                        </div>

                        @if ($isEditing)
                            <div class="alert alert-warning border-0 rounded-3 mb-0">
                                If payments already exist, the amount cannot be reduced below the allocated amount.
                            </div>
                        @else
                            <div class="alert alert-info border-0 rounded-3 mb-0">
                                New manual fee items will be unpaid until payment allocation is posted.
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary rounded-3">
                            {{ $isEditing ? 'Update Fee Item' : 'Create Fee Item' }}
                        </button>

                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function feeItemModalInstance() {
            const el = document.getElementById('feeItemModal');
            if (!el) return null;

            return bootstrap.Modal.getOrCreateInstance(el);
        }

        window.addEventListener('show-fee-item-modal', () => feeItemModalInstance()?.show());
        window.addEventListener('hide-fee-item-modal', () => feeItemModalInstance()?.hide());
    </script>
@endpush
