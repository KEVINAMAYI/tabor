<?php

use App\Models\Supplier;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $supplierId = null;
    public $s_name = '';
    public $s_contact_person = '';
    public $s_phone = '';
    public $s_email = '';
    public $s_address = '';
    public $s_kra_pin = '';
    public $s_payment_terms = '';
    public $s_is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-suppliers'), 403);
    }

    public function with(): array
    {
        return [
            'suppliers' => Supplier::orderBy('name')->paginate(20),
        ];
    }

    public function openModal(): void
    {
        abort_unless(auth()->user()?->can('manage-suppliers'), 403);
        $this->resetForm();
        $this->dispatch('show-supplier-modal');
    }

    public function edit($id): void
    {
        abort_unless(auth()->user()?->can('manage-suppliers'), 403);
        $s = Supplier::findOrFail($id);

        $this->supplierId = $s->id;
        $this->s_name = $s->name;
        $this->s_contact_person = $s->contact_person;
        $this->s_phone = $s->phone;
        $this->s_email = $s->email;
        $this->s_address = $s->address;
        $this->s_kra_pin = $s->kra_pin;
        $this->s_payment_terms = $s->payment_terms;
        $this->s_is_active = $s->is_active;

        $this->dispatch('show-supplier-modal');
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('manage-suppliers'), 403);

        $this->validate([
            's_name' => 'required|string|max:255',
            's_email' => 'nullable|email|max:255',
            's_phone' => 'nullable|string|max:50',
        ]);

        try {
            Supplier::updateOrCreate(
                ['id' => $this->supplierId],
                [
                    'name' => $this->s_name,
                    'contact_person' => $this->s_contact_person,
                    'phone' => $this->s_phone,
                    'email' => $this->s_email,
                    'address' => $this->s_address,
                    'kra_pin' => $this->s_kra_pin,
                    'payment_terms' => $this->s_payment_terms,
                    'is_active' => (bool) $this->s_is_active,
                ],
            );

            $this->resetForm();
            $this->dispatch('hide-supplier-modal');

            LivewireAlert::text('Supplier saved.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Supplier save failed: ' . $e->getMessage());
            LivewireAlert::text('Failed to save supplier.')->error()->toast()->position('top-end')->show();
        }
    }

    protected function resetForm(): void
    {
        $this->supplierId = null;
        $this->s_name = '';
        $this->s_contact_person = '';
        $this->s_phone = '';
        $this->s_email = '';
        $this->s_address = '';
        $this->s_kra_pin = '';
        $this->s_payment_terms = '';
        $this->s_is_active = true;
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Suppliers</h4>
            <p class="text-muted mb-0">Supplier master data used across purchase orders and invoices.</p>
        </div>
        @can('manage-suppliers')
            <button class="btn btn-primary btn-sm" wire:click="openModal">
                <i class="ti ti-plus me-1"></i> Add Supplier
            </button>
        @endcan
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>KRA PIN</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $s)
                        <tr>
                            <td>{{ $s->name }}</td>
                            <td>{{ $s->contact_person ?? '—' }}</td>
                            <td>{{ $s->phone ?? '—' }}</td>
                            <td>{{ $s->email ?? '—' }}</td>
                            <td>{{ $s->kra_pin ?? '—' }}</td>
                            <td>
                                @if ($s->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @can('manage-suppliers')
                                    <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $s->id }})">Edit</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No suppliers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $suppliers->links() }}
        </div>
    </div>

    <div class="modal fade" id="supplierModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $supplierId ? 'Edit Supplier' : 'Add Supplier' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="save">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" wire:model="s_name">
                                @error('s_name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" wire:model="s_contact_person">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" wire:model="s_phone">
                                @error('s_phone') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" wire:model="s_email">
                                @error('s_email') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">KRA PIN</label>
                                <input type="text" class="form-control" wire:model="s_kra_pin">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Terms</label>
                                <input type="text" class="form-control" wire:model="s_payment_terms" placeholder="e.g. Net 30">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" wire:model="s_address" rows="2"></textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="s_is_active" id="s_is_active">
                            <label class="form-check-label" for="s_is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">{{ $supplierId ? 'Update' : 'Save' }}</button>
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
            window.addEventListener('show-supplier-modal', () => new bootstrap.Modal(document.getElementById('supplierModal')).show());
            window.addEventListener('hide-supplier-modal', () => bootstrap.Modal.getInstance(document.getElementById('supplierModal'))?.hide());
        });
    </script>
@endpush
