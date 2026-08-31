<?php

use App\Models\PettyCashCustodian;
use App\Models\User;
use App\Services\PettyCashService;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;

new class extends Component {
    public $custodianId = null;
    public $c_user_id = null;
    public $c_title = '';
    public $c_opening_float = null;
    public $c_is_active = true;

    public $floatCustodianId = null;
    public $f_amount = null;
    public $f_type = 'topup';
    public $f_transaction_date = '';
    public $f_notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-petty-cash'), 403);
        $this->f_transaction_date = now()->toDateString();
    }

    public function with(): array
    {
        return [
            'custodians' => PettyCashCustodian::with('user')->orderBy('title')->get(),
            'users' => User::orderBy('name')->get(),
        ];
    }

    public function openCustodianModal(): void
    {
        abort_unless(auth()->user()?->can('manage-petty-cash-custodians'), 403);
        $this->resetCustodianForm();
        $this->dispatch('show-custodian-modal');
    }

    public function editCustodian($id): void
    {
        abort_unless(auth()->user()?->can('manage-petty-cash-custodians'), 403);
        $c = PettyCashCustodian::findOrFail($id);

        $this->custodianId = $c->id;
        $this->c_user_id = $c->user_id;
        $this->c_title = $c->title;
        $this->c_opening_float = $c->opening_float;
        $this->c_is_active = $c->is_active;

        $this->dispatch('show-custodian-modal');
    }

    public function saveCustodian(): void
    {
        abort_unless(auth()->user()?->can('manage-petty-cash-custodians'), 403);

        $this->validate([
            'c_user_id' => 'required|exists:users,id',
            'c_title' => 'nullable|string|max:255',
            'c_opening_float' => 'required|numeric|min:0',
        ]);

        try {
            PettyCashCustodian::updateOrCreate(
                ['id' => $this->custodianId],
                [
                    'user_id' => $this->c_user_id,
                    'title' => $this->c_title,
                    'opening_float' => $this->c_opening_float,
                    'is_active' => (bool) $this->c_is_active,
                ],
            );

            $this->resetCustodianForm();
            $this->dispatch('hide-custodian-modal');

            LivewireAlert::text('Custodian saved.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Petty cash custodian save failed: ' . $e->getMessage());
            LivewireAlert::text('Failed to save custodian — this user may already be a custodian.')->error()->toast()->position('top-end')->show();
        }
    }

    public function openFloatModal($custodianId): void
    {
        abort_unless(auth()->user()?->can('manage-petty-cash-custodians'), 403);
        $this->floatCustodianId = $custodianId;
        $this->f_amount = null;
        $this->f_type = 'topup';
        $this->f_transaction_date = now()->toDateString();
        $this->f_notes = '';
        $this->dispatch('show-float-modal');
    }

    public function issueFloat(): void
    {
        abort_unless(auth()->user()?->can('manage-petty-cash-custodians'), 403);

        $this->validate([
            'f_amount' => 'required|numeric|min:0.01',
            'f_type' => 'required|in:initial,topup,reimbursement',
            'f_transaction_date' => 'required|date',
        ]);

        try {
            app(PettyCashService::class)->issueFloat([
                'custodian_id' => $this->floatCustodianId,
                'amount' => $this->f_amount,
                'type' => $this->f_type,
                'transaction_date' => $this->f_transaction_date,
                'issued_by' => auth()->id(),
                'notes' => $this->f_notes,
            ]);

            $this->dispatch('hide-float-modal');

            LivewireAlert::text('Float issued and posted to the GL.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Petty cash float issuance failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    protected function resetCustodianForm(): void
    {
        $this->custodianId = null;
        $this->c_user_id = null;
        $this->c_title = '';
        $this->c_opening_float = null;
        $this->c_is_active = true;
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Petty Cash Custodians</h4>
            <p class="text-muted mb-0">Staff holding a cash float (Registrar, Procurement Officer, etc.). Issuing or reimbursing a float posts to the GL immediately.</p>
        </div>
        @can('manage-petty-cash-custodians')
            <button class="btn btn-primary btn-sm" wire:click="openCustodianModal">
                <i class="ti ti-plus me-1"></i> Add Custodian
            </button>
        @endcan
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Custodian</th>
                        <th>Title</th>
                        <th class="text-end">Opening Float</th>
                        <th class="text-end">Available Balance</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($custodians as $c)
                        <tr>
                            <td>{{ $c->user->name ?? '—' }}</td>
                            <td>{{ $c->title ?? '—' }}</td>
                            <td class="text-end">{{ number_format($c->opening_float, 2) }}</td>
                            <td class="text-end fw-semibold {{ $c->available_balance < 0 ? 'text-danger' : '' }}">
                                {{ number_format($c->available_balance, 2) }}
                            </td>
                            <td>
                                @if ($c->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @can('manage-petty-cash-custodians')
                                    <button class="btn btn-sm btn-outline-primary" wire:click="openFloatModal({{ $c->id }})">Issue Float</button>
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="editCustodian({{ $c->id }})">Edit</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No custodians yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Custodian Modal --}}
    <div class="modal fade" id="custodianModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $custodianId ? 'Edit Custodian' : 'Add Custodian' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="saveCustodian">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">User</label>
                            <select class="form-select" wire:model="c_user_id">
                                <option value="">Select user</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            @error('c_user_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title / Role</label>
                            <input type="text" class="form-control" wire:model="c_title" placeholder="e.g. Registrar">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Opening Float</label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model="c_opening_float">
                            @error('c_opening_float') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="c_is_active" id="c_is_active">
                            <label class="form-check-label" for="c_is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">{{ $custodianId ? 'Update' : 'Save' }}</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Issue Float Modal --}}
    <div class="modal fade" id="floatModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Issue / Top Up Float</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="issueFloat">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" wire:model="f_type">
                                <option value="initial">Initial Float</option>
                                <option value="topup">Top Up</option>
                                <option value="reimbursement">Reimbursement</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model="f_amount">
                            @error('f_amount') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" wire:model="f_transaction_date">
                            @error('f_transaction_date') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" wire:model="f_notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Issue Float</button>
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
            window.addEventListener('show-custodian-modal', () => new bootstrap.Modal(document.getElementById('custodianModal')).show());
            window.addEventListener('hide-custodian-modal', () => bootstrap.Modal.getInstance(document.getElementById('custodianModal'))?.hide());
            window.addEventListener('show-float-modal', () => new bootstrap.Modal(document.getElementById('floatModal')).show());
            window.addEventListener('hide-float-modal', () => bootstrap.Modal.getInstance(document.getElementById('floatModal'))?.hide());
        });
    </script>
@endpush
