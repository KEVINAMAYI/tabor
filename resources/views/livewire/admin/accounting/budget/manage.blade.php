<?php

use App\Models\Budget;
use App\Models\FinancialYear;
use App\Models\SubVoteHead;
use App\Models\VoteHead;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;

new class extends Component {
    public $selectedYearId = null;

    public $budgetId = null;
    public $b_vote_head_id = null;
    public $b_sub_vote_head_id = null;
    public $b_budgeted_amount = null;
    public $b_notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-budgets'), 403);
    }

    public function with(): array
    {
        $years = FinancialYear::orderByDesc('start_date')->get();

        if (!$this->selectedYearId && $years->count()) {
            $this->selectedYearId = $years->first()->id;
        }

        $budgets = $this->selectedYearId
            ? Budget::where('financial_year_id', $this->selectedYearId)->with(['voteHead', 'subVoteHead'])->get()
                ->sortBy(fn ($b) => $b->voteHead->name . ($b->subVoteHead->name ?? ''))
            : collect();

        return [
            'years' => $years,
            'budgets' => $budgets,
            'voteHeads' => VoteHead::active()->orderBy('name')->get(),
            'subVoteHeads' => $this->b_vote_head_id
                ? SubVoteHead::where('vote_head_id', $this->b_vote_head_id)->active()->orderBy('name')->get()
                : collect(),
        ];
    }

    public function selectYear($id): void
    {
        $this->selectedYearId = $id;
    }

    public function updatedBVoteHeadId(): void
    {
        $this->b_sub_vote_head_id = null;
    }

    public function openBudgetModal(): void
    {
        abort_unless(auth()->user()?->can('manage-budgets'), 403);
        $this->resetBudgetForm();
        $this->dispatch('show-budget-modal');
    }

    public function editBudget($id): void
    {
        abort_unless(auth()->user()?->can('manage-budgets'), 403);
        $budget = Budget::findOrFail($id);

        $this->budgetId = $budget->id;
        $this->b_vote_head_id = $budget->vote_head_id;
        $this->b_sub_vote_head_id = $budget->sub_vote_head_id;
        $this->b_budgeted_amount = $budget->budgeted_amount;
        $this->b_notes = $budget->notes;

        $this->dispatch('show-budget-modal');
    }

    public function saveBudget(): void
    {
        abort_unless(auth()->user()?->can('manage-budgets'), 403);

        $this->validate([
            'b_vote_head_id' => 'required|exists:vote_heads,id',
            'b_sub_vote_head_id' => 'nullable|exists:sub_vote_heads,id',
            'b_budgeted_amount' => 'required|numeric|min:0',
        ]);

        try {
            Budget::updateOrCreate(
                [
                    'id' => $this->budgetId,
                    'financial_year_id' => $this->selectedYearId,
                    'vote_head_id' => $this->b_vote_head_id,
                    'sub_vote_head_id' => $this->b_sub_vote_head_id,
                ],
                [
                    'budgeted_amount' => $this->b_budgeted_amount,
                    'notes' => $this->b_notes,
                    'created_by' => auth()->id(),
                ],
            );

            $this->resetBudgetForm();
            $this->dispatch('hide-budget-modal');

            LivewireAlert::text('Budget saved.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Budget save failed: ' . $e->getMessage());
            LivewireAlert::text('Failed to save budget — this vote head/sub vote head combination may already have a budget for this year.')->error()->toast()->position('top-end')->show();
        }
    }

    protected function resetBudgetForm(): void
    {
        $this->budgetId = null;
        $this->b_vote_head_id = null;
        $this->b_sub_vote_head_id = null;
        $this->b_budgeted_amount = null;
        $this->b_notes = '';
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Budget Management</h4>
            <p class="text-muted mb-0">Set annual budgeted amounts per vote head. Actual spend is tracked automatically from posted GL entries.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.budget.report') }}" class="btn btn-outline-secondary btn-sm">Budget vs Actual</a>
            @can('manage-budgets')
                <button class="btn btn-primary btn-sm" wire:click="openBudgetModal" @disabled(!$selectedYearId)>
                    <i class="ti ti-plus me-1"></i> Add Budget Line
                </button>
            @endcan
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <select class="form-select" wire:model.live="selectedYearId">
                @foreach ($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Vote Head</th>
                        <th>Sub Vote Head</th>
                        <th class="text-end">Budgeted Amount</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($budgets as $budget)
                        <tr>
                            <td>{{ $budget->voteHead->name }}</td>
                            <td>{{ $budget->subVoteHead->name ?? '— (whole vote head)' }}</td>
                            <td class="text-end">{{ number_format($budget->budgeted_amount, 2) }}</td>
                            <td class="small text-muted">{{ $budget->notes }}</td>
                            <td>
                                @can('manage-budgets')
                                    <button class="btn btn-sm btn-outline-primary" wire:click="editBudget({{ $budget->id }})">Edit</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">No budget lines for this financial year yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Budget Modal --}}
    <div class="modal fade" id="budgetModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $budgetId ? 'Edit Budget Line' : 'Add Budget Line' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="saveBudget">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Vote Head</label>
                            <select class="form-select" wire:model.live="b_vote_head_id" @disabled($budgetId)>
                                <option value="">Select vote head</option>
                                @foreach ($voteHeads as $vh)
                                    <option value="{{ $vh->id }}">{{ $vh->name }}</option>
                                @endforeach
                            </select>
                            @error('b_vote_head_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sub Vote Head (optional)</label>
                            <select class="form-select" wire:model="b_sub_vote_head_id" @disabled($budgetId || $subVoteHeads->isEmpty())>
                                <option value="">Whole vote head</option>
                                @foreach ($subVoteHeads as $svh)
                                    <option value="{{ $svh->id }}">{{ $svh->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Leave as "Whole vote head" to budget the entire category; pick a sub vote head to budget just that line item.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Budgeted Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model="b_budgeted_amount">
                            @error('b_budgeted_amount') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" wire:model="b_notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">{{ $budgetId ? 'Update' : 'Save' }}</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.addEventListener('show-budget-modal', () => new bootstrap.Modal(document.getElementById('budgetModal')).show());
            window.addEventListener('hide-budget-modal', () => bootstrap.Modal.getInstance(document.getElementById('budgetModal'))?.hide());
        });
    </script>
@endpush
