<?php

use App\Models\PettyCashCustodian;
use App\Models\PettyCashExpense;
use App\Models\SubVoteHead;
use App\Models\VoteHead;
use App\Services\PettyCashService;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithFileUploads;
    use WithPagination;

    public $statusFilter = 'pending';

    public $e_custodian_id = null;
    public $e_vote_head_id = null;
    public $e_sub_vote_head_id = null;
    public $e_supplier_name = '';
    public $e_description = '';
    public $e_amount = null;
    public $e_expense_date = '';
    public $e_receipt = null;

    public $rejectingId = null;
    public $rejectReason = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-petty-cash'), 403);
        $this->e_expense_date = now()->toDateString();
    }

    public function with(): array
    {
        return [
            'expenses' => PettyCashExpense::query()
                ->with(['custodian.user', 'voteHead', 'subVoteHead'])
                ->when(filled($this->statusFilter), fn ($q) => $q->where('status', $this->statusFilter))
                ->orderByDesc('expense_date')
                ->orderByDesc('id')
                ->paginate(20),
            'custodians' => PettyCashCustodian::active()->with('user')->orderBy('title')->get(),
            'voteHeads' => VoteHead::active()->orderBy('name')->get(),
            'subVoteHeads' => $this->e_vote_head_id
                ? SubVoteHead::where('vote_head_id', $this->e_vote_head_id)->active()->orderBy('name')->get()
                : collect(),
        ];
    }

    public function updatedEVoteHeadId(): void
    {
        $this->e_sub_vote_head_id = null;
    }

    public function openExpenseModal(): void
    {
        abort_unless(auth()->user()?->can('create-petty-cash-expense'), 403);
        $this->resetExpenseForm();
        $this->dispatch('show-expense-modal');
    }

    public function submitExpense(): void
    {
        abort_unless(auth()->user()?->can('create-petty-cash-expense'), 403);

        $this->validate([
            'e_custodian_id' => 'required|exists:petty_cash_custodians,id',
            'e_vote_head_id' => 'required|exists:vote_heads,id',
            'e_sub_vote_head_id' => 'nullable|exists:sub_vote_heads,id',
            'e_supplier_name' => 'nullable|string|max:255',
            'e_description' => 'required|string|max:1000',
            'e_amount' => 'required|numeric|min:0.01',
            'e_expense_date' => 'required|date',
            'e_receipt' => 'nullable|file|max:5120',
        ]);

        try {
            $receiptPath = null;
            $receiptOriginalName = null;

            if ($this->e_receipt) {
                $receiptPath = $this->e_receipt->store('petty-cash-receipts', 'public');
                $receiptOriginalName = $this->e_receipt->getClientOriginalName();
            }

            app(PettyCashService::class)->submitExpense([
                'custodian_id' => $this->e_custodian_id,
                'vote_head_id' => $this->e_vote_head_id,
                'sub_vote_head_id' => $this->e_sub_vote_head_id,
                'supplier_name' => $this->e_supplier_name,
                'description' => $this->e_description,
                'amount' => $this->e_amount,
                'expense_date' => $this->e_expense_date,
                'receipt_path' => $receiptPath,
                'receipt_original_name' => $receiptOriginalName,
                'submitted_by' => auth()->id(),
            ]);

            $this->resetExpenseForm();
            $this->dispatch('hide-expense-modal');

            LivewireAlert::text('Expense submitted for approval.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Petty cash expense submission failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function approve($id): void
    {
        abort_unless(auth()->user()?->can('approve-petty-cash-expense'), 403);

        try {
            app(PettyCashService::class)->approveExpense(PettyCashExpense::findOrFail($id), auth()->id());

            LivewireAlert::text('Expense approved and posted to the GL.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Petty cash expense approval failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function openRejectModal($id): void
    {
        abort_unless(auth()->user()?->can('approve-petty-cash-expense'), 403);
        $this->rejectingId = $id;
        $this->rejectReason = '';
        $this->dispatch('show-reject-modal');
    }

    public function reject(): void
    {
        abort_unless(auth()->user()?->can('approve-petty-cash-expense'), 403);

        $this->validate(['rejectReason' => 'required|string|max:500']);

        try {
            app(PettyCashService::class)->rejectExpense(
                PettyCashExpense::findOrFail($this->rejectingId),
                $this->rejectReason,
                auth()->id()
            );

            $this->dispatch('hide-reject-modal');

            LivewireAlert::text('Expense rejected.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Petty cash expense rejection failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    protected function resetExpenseForm(): void
    {
        $this->e_custodian_id = null;
        $this->e_vote_head_id = null;
        $this->e_sub_vote_head_id = null;
        $this->e_supplier_name = '';
        $this->e_description = '';
        $this->e_amount = null;
        $this->e_expense_date = now()->toDateString();
        $this->e_receipt = null;
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Petty Cash Expenses</h4>
            <p class="text-muted mb-0">Custodian-submitted, vote-head-coded expenses. Approving posts the entry to the GL.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.petty-cash.custodians') }}" class="btn btn-outline-secondary btn-sm">Custodians</a>
            @can('create-petty-cash-expense')
                <button class="btn btn-primary btn-sm" wire:click="openExpenseModal">
                    <i class="ti ti-plus me-1"></i> Submit Expense
                </button>
            @endcan
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Custodian</th>
                        <th>Vote Head</th>
                        <th>Supplier</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th>Receipt</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_date->format('d M Y') }}</td>
                            <td>{{ $expense->custodian->title ?? $expense->custodian->user->name ?? '—' }}</td>
                            <td class="small">
                                {{ $expense->voteHead->name }}
                                @if ($expense->subVoteHead)
                                    <span class="text-muted">/ {{ $expense->subVoteHead->name }}</span>
                                @endif
                            </td>
                            <td>{{ $expense->supplier_name ?? '—' }}</td>
                            <td class="text-truncate" style="max-width: 220px" title="{{ $expense->description }}">{{ $expense->description }}</td>
                            <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                            <td>
                                @if ($expense->receipt_url)
                                    <a href="{{ $expense->receipt_url }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="ti ti-paperclip"></i></a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusClasses = match ($expense->status) {
                                        'approved' => 'bg-success-subtle text-success',
                                        'pending' => 'bg-warning-subtle text-warning',
                                        default => 'bg-danger-subtle text-danger',
                                    };
                                @endphp
                                <span class="badge {{ $statusClasses }}">{{ ucfirst($expense->status) }}</span>
                            </td>
                            <td>
                                @can('approve-petty-cash-expense')
                                    @if ($expense->status === 'pending')
                                        <button class="btn btn-sm btn-outline-success" wire:click="approve({{ $expense->id }})">Approve</button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="openRejectModal({{ $expense->id }})">Reject</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">No expenses found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $expenses->links() }}
        </div>
    </div>

    {{-- Submit Expense Modal --}}
    <div class="modal fade" id="expenseModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Petty Cash Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="submitExpense">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Custodian</label>
                                <select class="form-select" wire:model="e_custodian_id">
                                    <option value="">Select custodian</option>
                                    @foreach ($custodians as $c)
                                        <option value="{{ $c->id }}">{{ $c->title ?? $c->user->name }} (Balance: {{ number_format($c->available_balance, 2) }})</option>
                                    @endforeach
                                </select>
                                @error('e_custodian_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expense Date</label>
                                <input type="date" class="form-control" wire:model="e_expense_date">
                                @error('e_expense_date') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vote Head</label>
                                <select class="form-select" wire:model.live="e_vote_head_id">
                                    <option value="">Select vote head</option>
                                    @foreach ($voteHeads as $vh)
                                        <option value="{{ $vh->id }}">{{ $vh->name }}</option>
                                    @endforeach
                                </select>
                                @error('e_vote_head_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sub Vote Head</label>
                                <select class="form-select" wire:model="e_sub_vote_head_id" @disabled($subVoteHeads->isEmpty())>
                                    <option value="">None</option>
                                    @foreach ($subVoteHeads as $svh)
                                        <option value="{{ $svh->id }}">{{ $svh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" class="form-control" wire:model="e_supplier_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" min="0" class="form-control" wire:model="e_amount">
                                @error('e_amount') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" wire:model="e_description" rows="2"></textarea>
                            @error('e_description') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Receipt</label>
                            <input type="file" class="form-control" wire:model="e_receipt">
                            @error('e_receipt') <small class="text-danger">{{ $message }}</small> @enderror
                            <div wire:loading wire:target="e_receipt" class="small text-muted mt-1">Uploading…</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="reject">
                    <div class="modal-body">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" wire:model="rejectReason" rows="3"></textarea>
                        @error('rejectReason') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger">Reject</button>
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
            window.addEventListener('show-expense-modal', () => new bootstrap.Modal(document.getElementById('expenseModal')).show());
            window.addEventListener('hide-expense-modal', () => bootstrap.Modal.getInstance(document.getElementById('expenseModal'))?.hide());
            window.addEventListener('show-reject-modal', () => new bootstrap.Modal(document.getElementById('rejectModal')).show());
            window.addEventListener('hide-reject-modal', () => bootstrap.Modal.getInstance(document.getElementById('rejectModal'))?.hide());
        });
    </script>
@endpush
