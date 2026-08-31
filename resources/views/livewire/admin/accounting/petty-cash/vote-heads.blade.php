<?php

use App\Models\ChartOfAccount;
use App\Models\SubVoteHead;
use App\Models\VoteHead;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;

new class extends Component {
    public $selectedVoteHeadId = null;

    public $voteHeadId = null;
    public $vh_code = '';
    public $vh_name = '';
    public $vh_expense_account_id = null;
    public $vh_is_active = true;

    public $subVoteHeadId = null;
    public $svh_code = '';
    public $svh_name = '';
    public $svh_is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-vote-heads'), 403);
    }

    public function with(): array
    {
        $voteHeads = VoteHead::with('expenseAccount')->orderBy('name')->get();

        if (!$this->selectedVoteHeadId && $voteHeads->count()) {
            $this->selectedVoteHeadId = $voteHeads->first()->id;
        }

        $subVoteHeads = $this->selectedVoteHeadId
            ? SubVoteHead::where('vote_head_id', $this->selectedVoteHeadId)->orderBy('name')->get()
            : collect();

        return [
            'voteHeads' => $voteHeads,
            'subVoteHeads' => $subVoteHeads,
            'expenseAccounts' => ChartOfAccount::where('account_type', 'expense')->active()->orderBy('account_code')->get(),
        ];
    }

    public function selectVoteHead($id): void
    {
        $this->selectedVoteHeadId = $id;
    }

    public function openVoteHeadModal(): void
    {
        abort_unless(auth()->user()?->can('manage-vote-heads'), 403);
        $this->resetVoteHeadForm();
        $this->dispatch('show-vh-modal');
    }

    public function editVoteHead($id): void
    {
        abort_unless(auth()->user()?->can('manage-vote-heads'), 403);
        $vh = VoteHead::findOrFail($id);

        $this->voteHeadId = $vh->id;
        $this->vh_code = $vh->code;
        $this->vh_name = $vh->name;
        $this->vh_expense_account_id = $vh->expense_account_id;
        $this->vh_is_active = $vh->is_active;

        $this->dispatch('show-vh-modal');
    }

    public function saveVoteHead(): void
    {
        abort_unless(auth()->user()?->can('manage-vote-heads'), 403);

        $this->validate([
            'vh_code' => 'required|string|max:50',
            'vh_name' => 'required|string|max:255',
            'vh_expense_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        try {
            $vh = VoteHead::updateOrCreate(
                ['id' => $this->voteHeadId],
                [
                    'code' => strtoupper($this->vh_code),
                    'name' => $this->vh_name,
                    'expense_account_id' => $this->vh_expense_account_id,
                    'is_active' => (bool) $this->vh_is_active,
                ],
            );

            $this->selectedVoteHeadId = $vh->id;
            $this->resetVoteHeadForm();
            $this->dispatch('hide-vh-modal');

            LivewireAlert::text('Vote head saved.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Vote head save failed: ' . $e->getMessage());
            LivewireAlert::text('Failed to save vote head — check the code is unique.')->error()->toast()->position('top-end')->show();
        }
    }

    public function openSubVoteHeadModal(): void
    {
        abort_unless(auth()->user()?->can('manage-vote-heads'), 403);
        $this->resetSubVoteHeadForm();
        $this->dispatch('show-svh-modal');
    }

    public function editSubVoteHead($id): void
    {
        abort_unless(auth()->user()?->can('manage-vote-heads'), 403);
        $svh = SubVoteHead::findOrFail($id);

        $this->subVoteHeadId = $svh->id;
        $this->svh_code = $svh->code;
        $this->svh_name = $svh->name;
        $this->svh_is_active = $svh->is_active;

        $this->dispatch('show-svh-modal');
    }

    public function saveSubVoteHead(): void
    {
        abort_unless(auth()->user()?->can('manage-vote-heads'), 403);

        $this->validate([
            'svh_code' => 'required|string|max:50',
            'svh_name' => 'required|string|max:255',
        ]);

        try {
            SubVoteHead::updateOrCreate(
                ['id' => $this->subVoteHeadId],
                [
                    'vote_head_id' => $this->selectedVoteHeadId,
                    'code' => strtoupper($this->svh_code),
                    'name' => $this->svh_name,
                    'is_active' => (bool) $this->svh_is_active,
                ],
            );

            $this->resetSubVoteHeadForm();
            $this->dispatch('hide-svh-modal');

            LivewireAlert::text('Sub vote head saved.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Sub vote head save failed: ' . $e->getMessage());
            LivewireAlert::text('Failed to save sub vote head — check the code is unique within this vote head.')->error()->toast()->position('top-end')->show();
        }
    }

    protected function resetVoteHeadForm(): void
    {
        $this->voteHeadId = null;
        $this->vh_code = '';
        $this->vh_name = '';
        $this->vh_expense_account_id = null;
        $this->vh_is_active = true;
    }

    protected function resetSubVoteHeadForm(): void
    {
        $this->subVoteHeadId = null;
        $this->svh_code = '';
        $this->svh_name = '';
        $this->svh_is_active = true;
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Vote Heads &amp; Sub Vote Heads</h4>
            <p class="text-muted mb-0">Spending categories petty cash expenses are coded against. Each maps to a Chart of Accounts expense account.</p>
        </div>
        @can('manage-vote-heads')
            <button class="btn btn-primary btn-sm" wire:click="openVoteHeadModal">
                <i class="ti ti-plus me-1"></i> Add Vote Head
            </button>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <div class="card card-body">
                <h6 class="fw-semibold mb-3">Vote Heads</h6>
                <ul class="list-unstyled mb-0">
                    @forelse ($voteHeads as $vh)
                        <li class="d-flex align-items-center justify-content-between px-2 py-2 rounded mb-1 {{ (int) $selectedVoteHeadId === (int) $vh->id ? 'bg-light' : '' }}"
                            style="cursor:pointer" wire:click="selectVoteHead({{ $vh->id }})">
                            <div>
                                <div class="fw-semibold">{{ $vh->code }} — {{ $vh->name }}</div>
                                <div class="text-muted small">{{ $vh->expenseAccount->account_code }} {{ $vh->expenseAccount->name }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @unless ($vh->is_active)
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endunless
                                @can('manage-vote-heads')
                                    <button class="btn btn-sm btn-outline-secondary" wire:click.stop="editVoteHead({{ $vh->id }})">
                                        <i class="ti ti-pencil"></i>
                                    </button>
                                @endcan
                            </div>
                        </li>
                    @empty
                        <li class="text-center text-muted py-3">No vote heads yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">Sub Vote Heads</h6>
                    @can('manage-vote-heads')
                        <button class="btn btn-sm btn-primary" wire:click="openSubVoteHeadModal" @disabled(!$selectedVoteHeadId)>
                            <i class="ti ti-plus me-1"></i> Add Sub Vote Head
                        </button>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table class="table align-middle text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subVoteHeads as $svh)
                                <tr>
                                    <td>{{ $svh->code }}</td>
                                    <td>{{ $svh->name }}</td>
                                    <td>
                                        @if ($svh->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @can('manage-vote-heads')
                                            <button class="btn btn-sm btn-outline-primary" wire:click="editSubVoteHead({{ $svh->id }})">Edit</button>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No sub vote heads for this vote head.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Vote Head Modal --}}
    <div class="modal fade" id="vhModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $voteHeadId ? 'Edit Vote Head' : 'Add Vote Head' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="saveVoteHead">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Code</label>
                                <input type="text" class="form-control" wire:model="vh_code" placeholder="e.g. ICT">
                                @error('vh_code') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" wire:model="vh_name">
                                @error('vh_name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expense Account</label>
                            <select class="form-select" wire:model="vh_expense_account_id">
                                <option value="">Select account</option>
                                @foreach ($expenseAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} — {{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('vh_expense_account_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="vh_is_active" id="vh_is_active">
                            <label class="form-check-label" for="vh_is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">{{ $voteHeadId ? 'Update' : 'Save' }}</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Sub Vote Head Modal --}}
    <div class="modal fade" id="svhModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $subVoteHeadId ? 'Edit Sub Vote Head' : 'Add Sub Vote Head' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="saveSubVoteHead">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control" wire:model="svh_code">
                            @error('svh_code') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" wire:model="svh_name">
                            @error('svh_name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="svh_is_active" id="svh_is_active">
                            <label class="form-check-label" for="svh_is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">{{ $subVoteHeadId ? 'Update' : 'Save' }}</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        window.addEventListener('show-vh-modal', () => new bootstrap.Modal(document.getElementById('vhModal')).show());
        window.addEventListener('hide-vh-modal', () => bootstrap.Modal.getInstance(document.getElementById('vhModal'))?.hide());
        window.addEventListener('show-svh-modal', () => new bootstrap.Modal(document.getElementById('svhModal')).show());
        window.addEventListener('hide-svh-modal', () => bootstrap.Modal.getInstance(document.getElementById('svhModal'))?.hide());
    </script>
@endscript
