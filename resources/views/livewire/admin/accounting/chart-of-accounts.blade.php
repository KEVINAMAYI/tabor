<?php

use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;

new class extends Component {
    public $search = '';
    public $typeFilter = '';

    public $accountId = null;
    public $account_code = '';
    public $name = '';
    public $account_type = 'expense';
    public $normal_balance = 'dr';
    public $parent_account_id = null;
    public $is_active = true;
    public $description = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-chart-of-accounts'), 403);
    }

    public function with(): array
    {
        return [
            'accounts' => ChartOfAccount::query()
                ->when(filled($this->search), fn ($q) => $q->where(function ($q2) {
                    $q2->where('name', 'like', "%{$this->search}%")
                        ->orWhere('account_code', 'like', "%{$this->search}%");
                }))
                ->when(filled($this->typeFilter), fn ($q) => $q->where('account_type', $this->typeFilter))
                ->orderBy('account_code')
                ->get(),
            'parentOptions' => ChartOfAccount::orderBy('account_code')->get(),
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->dispatch('show-account-modal');
    }

    public function edit($id): void
    {
        abort_unless(auth()->user()?->can('edit-chart-of-accounts'), 403);

        $account = ChartOfAccount::findOrFail($id);

        $this->accountId = $account->id;
        $this->account_code = $account->account_code;
        $this->name = $account->name;
        $this->account_type = $account->account_type;
        $this->normal_balance = $account->normal_balance;
        $this->parent_account_id = $account->parent_account_id;
        $this->is_active = $account->is_active;
        $this->description = $account->description;

        $this->dispatch('show-account-modal');
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can($this->accountId ? 'edit-chart-of-accounts' : 'create-chart-of-accounts'), 403);

        $this->validate([
            'account_code' => 'required|string|max:50|unique:chart_of_accounts,account_code,' . $this->accountId,
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'normal_balance' => 'required|in:dr,cr',
            'parent_account_id' => 'nullable|exists:chart_of_accounts,id',
            'description' => 'nullable|string',
        ]);

        try {
            ChartOfAccount::updateOrCreate(
                ['id' => $this->accountId],
                [
                    'account_code' => $this->account_code,
                    'name' => $this->name,
                    'account_type' => $this->account_type,
                    'normal_balance' => $this->normal_balance,
                    'parent_account_id' => $this->parent_account_id ?: null,
                    'is_active' => (bool) $this->is_active,
                    'description' => $this->description,
                ],
            );

            $this->resetForm();
            $this->dispatch('hide-account-modal');

            LivewireAlert::text('Account saved successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Chart of account save failed: ' . $e->getMessage());

            LivewireAlert::text('Failed to save account.')->error()->toast()->position('top-end')->show();
        }
    }

    public function toggleActive($id): void
    {
        abort_unless(auth()->user()?->can('edit-chart-of-accounts'), 403);

        $account = ChartOfAccount::findOrFail($id);
        $account->update(['is_active' => !$account->is_active]);
    }

    protected function resetForm(): void
    {
        $this->accountId = null;
        $this->account_code = '';
        $this->name = '';
        $this->account_type = 'expense';
        $this->normal_balance = 'dr';
        $this->parent_account_id = null;
        $this->is_active = true;
        $this->description = '';
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Chart of Accounts</h4>
            <p class="text-muted mb-0">The full set of accounts every journal entry posts against.</p>
        </div>
        @can('create-chart-of-accounts')
            <button class="btn btn-primary btn-sm" wire:click="openCreateModal">
                <i class="ti ti-plus me-1"></i> Add Account
            </button>
        @endcan
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" class="form-control" placeholder="Search by name or code" wire:model.live.debounce.300ms="search">
        </div>
        <div class="col-md-3">
            <select class="form-select" wire:model.live="typeFilter">
                <option value="">All Types</option>
                <option value="asset">Asset</option>
                <option value="liability">Liability</option>
                <option value="equity">Equity</option>
                <option value="revenue">Revenue</option>
                <option value="expense">Expense</option>
            </select>
        </div>
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Normal Balance</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td class="fw-semibold">{{ $account->account_code }}</td>
                            <td>{{ $account->name }}</td>
                            <td><span class="badge bg-light text-dark text-capitalize">{{ $account->account_type }}</span></td>
                            <td class="text-uppercase">{{ $account->normal_balance }}</td>
                            <td>
                                @if ($account->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-light text-muted">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @can('edit-chart-of-accounts')
                                    <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $account->id }})">Edit</button>
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="toggleActive({{ $account->id }})">
                                        {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="accountModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $accountId ? 'Edit Account' : 'Add Account' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="save">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Account Code</label>
                                <input type="text" class="form-control" wire:model="account_code">
                                @error('account_code') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" wire:model="name">
                                @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Type</label>
                                <select class="form-select" wire:model="account_type">
                                    <option value="asset">Asset</option>
                                    <option value="liability">Liability</option>
                                    <option value="equity">Equity</option>
                                    <option value="revenue">Revenue</option>
                                    <option value="expense">Expense</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Normal Balance</label>
                                <select class="form-select" wire:model="normal_balance">
                                    <option value="dr">Debit (DR)</option>
                                    <option value="cr">Credit (CR)</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Parent Account</label>
                                <select class="form-select" wire:model="parent_account_id">
                                    <option value="">None</option>
                                    @foreach ($parentOptions as $option)
                                        @if ($option->id !== $accountId)
                                            <option value="{{ $option->id }}">{{ $option->account_code }} — {{ $option->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" wire:model="description" rows="2"></textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="is_active" id="is_active">
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">{{ $accountId ? 'Update' : 'Save' }}</button>
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
            window.addEventListener('show-account-modal', () => {
                new bootstrap.Modal(document.getElementById('accountModal')).show();
            });
            window.addEventListener('hide-account-modal', () => {
                bootstrap.Modal.getInstance(document.getElementById('accountModal'))?.hide();
            });
        });
    </script>
@endpush
