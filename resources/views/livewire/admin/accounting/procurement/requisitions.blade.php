<?php

use App\Models\PurchaseRequisition;
use App\Models\SubVoteHead;
use App\Models\VoteHead;
use App\Services\ProcurementService;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $statusFilter = '';

    public $r_vote_head_id = null;
    public $r_sub_vote_head_id = null;
    public $r_description = '';
    public $r_estimated_amount = null;
    public $r_needed_by_date = '';

    public $rejectingId = null;
    public $rejectReason = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-purchase-requisitions'), 403);
    }

    public function with(): array
    {
        return [
            'requisitions' => PurchaseRequisition::query()
                ->with(['voteHead', 'subVoteHead', 'requestedBy'])
                ->when(filled($this->statusFilter), fn ($q) => $q->where('status', $this->statusFilter))
                ->orderByDesc('id')
                ->paginate(20),
            'voteHeads' => VoteHead::active()->orderBy('name')->get(),
            'subVoteHeads' => $this->r_vote_head_id
                ? SubVoteHead::where('vote_head_id', $this->r_vote_head_id)->active()->orderBy('name')->get()
                : collect(),
        ];
    }

    public function updatedRVoteHeadId(): void
    {
        $this->r_sub_vote_head_id = null;
    }

    public function openModal(): void
    {
        abort_unless(auth()->user()?->can('create-purchase-requisitions'), 403);
        $this->resetForm();
        $this->dispatch('show-requisition-modal');
    }

    public function submit(): void
    {
        abort_unless(auth()->user()?->can('create-purchase-requisitions'), 403);

        $this->validate([
            'r_vote_head_id' => 'required|exists:vote_heads,id',
            'r_sub_vote_head_id' => 'nullable|exists:sub_vote_heads,id',
            'r_description' => 'required|string|max:1000',
            'r_estimated_amount' => 'required|numeric|min:0.01',
            'r_needed_by_date' => 'nullable|date',
        ]);

        try {
            app(ProcurementService::class)->submitRequisition([
                'vote_head_id' => $this->r_vote_head_id,
                'sub_vote_head_id' => $this->r_sub_vote_head_id,
                'description' => $this->r_description,
                'estimated_amount' => $this->r_estimated_amount,
                'needed_by_date' => $this->r_needed_by_date ?: null,
                'requested_by' => auth()->id(),
            ]);

            $this->resetForm();
            $this->dispatch('hide-requisition-modal');

            LivewireAlert::text('Requisition submitted for approval.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Requisition submission failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function approveDept($id): void
    {
        abort_unless(auth()->user()?->can('approve-purchase-requisitions-department'), 403);

        try {
            app(ProcurementService::class)->approveRequisitionByDepartment(PurchaseRequisition::findOrFail($id), auth()->id());

            LivewireAlert::text('Requisition approved (department stage).')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Department approval failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function approveFinance($id): void
    {
        abort_unless(auth()->user()?->can('approve-purchase-requisitions-finance'), 403);

        try {
            app(ProcurementService::class)->approveRequisitionByFinance(PurchaseRequisition::findOrFail($id), auth()->id());

            LivewireAlert::text('Requisition approved (finance stage). Ready for a Purchase Order.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Finance approval failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function openRejectModal($id): void
    {
        $this->rejectingId = $id;
        $this->rejectReason = '';
        $this->dispatch('show-reject-modal');
    }

    public function reject(): void
    {
        $this->validate(['rejectReason' => 'required|string|max:500']);

        try {
            app(ProcurementService::class)->rejectRequisition(
                PurchaseRequisition::findOrFail($this->rejectingId),
                $this->rejectReason,
                auth()->id()
            );

            $this->dispatch('hide-reject-modal');

            LivewireAlert::text('Requisition rejected.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Requisition rejection failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    protected function resetForm(): void
    {
        $this->r_vote_head_id = null;
        $this->r_sub_vote_head_id = null;
        $this->r_description = '';
        $this->r_estimated_amount = null;
        $this->r_needed_by_date = '';
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Purchase Requisitions</h4>
            <p class="text-muted mb-0">Requisition &rarr; Department Approval &rarr; Finance Approval &rarr; ready for a Purchase Order.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.procurement.purchase-orders') }}" class="btn btn-outline-secondary btn-sm">Purchase Orders</a>
            @can('create-purchase-requisitions')
                <button class="btn btn-primary btn-sm" wire:click="openModal">
                    <i class="ti ti-plus me-1"></i> New Requisition
                </button>
            @endcan
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="">All Statuses</option>
                <option value="submitted">Submitted</option>
                <option value="dept_approved">Dept Approved</option>
                <option value="finance_approved">Finance Approved</option>
                <option value="converted">Converted to PO</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Vote Head</th>
                        <th>Description</th>
                        <th class="text-end">Estimated</th>
                        <th>Requested By</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requisitions as $r)
                        <tr>
                            <td>{{ $r->requisition_number }}</td>
                            <td class="small">
                                {{ $r->voteHead->name }}
                                @if ($r->subVoteHead)
                                    <span class="text-muted">/ {{ $r->subVoteHead->name }}</span>
                                @endif
                            </td>
                            <td class="text-truncate" style="max-width: 220px" title="{{ $r->description }}">{{ $r->description }}</td>
                            <td class="text-end">{{ number_format($r->estimated_amount, 2) }}</td>
                            <td>{{ $r->requestedBy->name ?? '—' }}</td>
                            <td>
                                @php
                                    $statusClasses = match ($r->status) {
                                        'finance_approved', 'converted' => 'bg-success-subtle text-success',
                                        'dept_approved', 'submitted' => 'bg-warning-subtle text-warning',
                                        default => 'bg-danger-subtle text-danger',
                                    };
                                @endphp
                                <span class="badge {{ $statusClasses }}">{{ str_replace('_', ' ', ucfirst($r->status)) }}</span>
                            </td>
                            <td>
                                @can('approve-purchase-requisitions-department')
                                    @if ($r->status === 'submitted')
                                        <button class="btn btn-sm btn-outline-success" wire:click="approveDept({{ $r->id }})">Dept Approve</button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="openRejectModal({{ $r->id }})">Reject</button>
                                    @endif
                                @endcan
                                @can('approve-purchase-requisitions-finance')
                                    @if ($r->status === 'dept_approved')
                                        <button class="btn btn-sm btn-outline-success" wire:click="approveFinance({{ $r->id }})">Finance Approve</button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="openRejectModal({{ $r->id }})">Reject</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No requisitions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $requisitions->links() }}
        </div>
    </div>

    {{-- Submit Requisition Modal --}}
    <div class="modal fade" id="requisitionModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Purchase Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="submit">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Vote Head</label>
                            <select class="form-select" wire:model.live="r_vote_head_id">
                                <option value="">Select vote head</option>
                                @foreach ($voteHeads as $vh)
                                    <option value="{{ $vh->id }}">{{ $vh->name }}</option>
                                @endforeach
                            </select>
                            @error('r_vote_head_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sub Vote Head (optional)</label>
                            <select class="form-select" wire:model="r_sub_vote_head_id" @disabled($subVoteHeads->isEmpty())>
                                <option value="">None</option>
                                @foreach ($subVoteHeads as $svh)
                                    <option value="{{ $svh->id }}">{{ $svh->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" wire:model="r_description" rows="2"></textarea>
                            @error('r_description') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estimated Amount</label>
                                <input type="number" step="0.01" min="0" class="form-control" wire:model="r_estimated_amount">
                                @error('r_estimated_amount') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Needed By</label>
                                <input type="date" class="form-control" wire:model="r_needed_by_date">
                            </div>
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
                    <h5 class="modal-title">Reject Requisition</h5>
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

@script
    <script>
        window.addEventListener('show-requisition-modal', () => new bootstrap.Modal(document.getElementById('requisitionModal')).show());
        window.addEventListener('hide-requisition-modal', () => bootstrap.Modal.getInstance(document.getElementById('requisitionModal'))?.hide());
        window.addEventListener('show-reject-modal', () => new bootstrap.Modal(document.getElementById('rejectModal')).show());
        window.addEventListener('hide-reject-modal', () => bootstrap.Modal.getInstance(document.getElementById('rejectModal'))?.hide());
    </script>
@endscript
