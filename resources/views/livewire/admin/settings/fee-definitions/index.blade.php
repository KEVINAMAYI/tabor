<?php

use App\Models\FeeCategory;
use App\Models\FeeDefinition;
use Livewire\Volt\Component;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $feeDefinitionId = null;
    public $fee_category_id = '';
    public $name = '';
    public $scope = 'student';
    public $applies_once = 0;
    public $mandatory = 1;
    public $default_amount = '';
    public $effective_from = '';
    public $effective_to = '';
    public $active = 1;

    public function openFeeDefinitionModal()
    {
        $this->resetForm();
        $this->dispatch('show-fee-definition-modal');
    }

    public function editFeeDefinition($id)
    {
        $fee = FeeDefinition::findOrFail($id);

        $this->feeDefinitionId = $fee->id;
        $this->fee_category_id = $fee->fee_category_id;
        $this->name = $fee->name;
        $this->scope = $fee->scope;
        $this->applies_once =  $fee->applies_once;
        $this->mandatory = $fee->mandatory;
        $this->default_amount = $fee->default_amount;
        $this->effective_from = optional($fee->effective_from)->format('Y-m-d');
        $this->effective_to = optional($fee->effective_to)->format('Y-m-d');
        $this->active = $fee->active;

        $this->dispatch('show-fee-definition-modal');
    }

    public function saveFeeDefinition()
    {
        $this->validate([
            'fee_category_id' => ['required', 'exists:fee_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'in:student,enrollment,trimester'],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);
        // dd($this->mandatory);

        FeeDefinition::updateOrCreate(
            ['id' => $this->feeDefinitionId],
            [
                'fee_category_id' => $this->fee_category_id,
                'name' => $this->name,
                'slug' => Str::slug($this->name),
                'scope' => $this->scope,
                'applies_once' => (bool) $this->applies_once,
                'mandatory' => (bool) $this->mandatory,
                'default_amount' => $this->default_amount,
                'effective_from' => $this->effective_from ?: null,
                'effective_to' => $this->effective_to ?: null,
                'active' => (bool) $this->active,
            ],
        );

        $this->dispatch('hide-fee-definition-modal');
        $this->resetForm();

        LivewireAlert::text('Fee definition saved successfully.')->success()->toast()->position('top-end')->show();
    }

    public function deleteFeeDefinition($id)
    {
        FeeDefinition::findOrFail($id)->delete();

        LivewireAlert::text('Fee definition deleted successfully.')->success()->toast()->position('top-end')->show();
    }

    protected function resetForm()
    {
        $this->feeDefinitionId = null;
        $this->fee_category_id = '';
        $this->name = '';
        $this->scope = 'student';
        $this->applies_once = false;
        $this->mandatory = true;
        $this->default_amount = '';
        $this->effective_from = '';
        $this->effective_to = '';
        $this->active = true;
    }

    public function with()
    {
        return [
            'feeDefinitions' => FeeDefinition::with('category')->latest()->get(),
            'feeCategories' => FeeCategory::orderBy('name')->get(),
        ];
    }
};

?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-semibold mb-1">Fee Definitions</h4>
                <p class="text-muted small mb-0">Master fee setup for student, enrollment, and trimester charges.</p>
            </div>

            <button class="btn btn-primary rounded-3" wire:click="openFeeDefinitionModal">
                <i class="ti ti-plus me-1"></i> Add Fee
            </button>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Scope</th>
                        <th>Default Amount</th>
                        <th>Once?</th>
                        <th>Mandatory</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feeDefinitions as $fee)
                        <tr>
                            <td class="fw-semibold">{{ $fee->name }}</td>
                            <td>{{ $fee->category->name ?? '—' }}</td>
                            <td>{{ ucfirst($fee->scope) }}</td>
                            <td>KES {{ number_format($fee->default_amount, 2) }}</td>
                            <td>{{ $fee->applies_once ? 'Yes' : 'No' }}</td>
                            <td>{{ $fee->mandatory ? 'Yes' : 'No' }}</td>
                            <td>{{ $fee->active ? 'Yes' : 'No' }}</td>
                            <td class="text-end">
                                <button class="btn btn-light btn-sm"
                                    wire:click="editFeeDefinition({{ $fee->id }})">Edit</button>
                                <button class="btn btn-light btn-sm text-danger" onclick=""
                                    wire:click="deleteFeeDefinition({{ $fee->id }})">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No fee definitions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="feeDefinitionModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $feeDefinitionId ? 'Edit Fee Definition' : 'Add Fee Definition' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form wire:submit.prevent="saveFeeDefinition">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" wire:model="fee_category_id">
                                    <option value="">Select category</option>
                                    @foreach ($feeCategories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('fee_category_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" wire:model="name">
                                @error('name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Scope</label>
                                <select class="form-select" wire:model="scope">
                                    <option value="student">Student</option>
                                    <option value="enrollment">Enrollment</option>
                                    <option value="trimester">Trimester</option>
                                </select>
                                @error('scope')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Default Amount</label>
                                <input type="number" step="0.01" class="form-control" wire:model="default_amount">
                                @error('default_amount')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Active</label>
                                <select class="form-select" wire:model="active">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Applies Once</label>
                                <select class="form-select" wire:model="applies_once">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Mandatory</label>
                                <select class="form-select" wire:model="mandatory">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Effective From</label>
                                <input type="date" class="form-control" wire:model="effective_from">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Effective To</label>
                                <input type="date" class="form-control" wire:model="effective_to">
                            </div>
                        </div>
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
        function feeDefinitionModal() {
            const el = document.getElementById('feeDefinitionModal');
            if (!el) return null;
            return bootstrap.Modal.getOrCreateInstance(el);
        }

        window.addEventListener('show-fee-definition-modal', () => feeDefinitionModal()?.show());
        window.addEventListener('hide-fee-definition-modal', () => feeDefinitionModal()?.hide());
    </script>
@endpush
