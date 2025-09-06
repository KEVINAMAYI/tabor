<?php

use App\Exports\ClassGroupExport;
use App\Models\ClassGroup;
use App\Models\Intake;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {

    use WithPagination;


    public $name, $intake_id;

    public $intakes = [];

    public $editId = null;

    public $selected = [];

    public $selectAll = false;

    public $search = '';

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'intake_id' => 'required|exists:intakes,id',
        ];
    }

    public function mount()
    {
        $this->intakes = Intake::latest()->get();
    }

    #[On('search')]
    public function search()
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function with()
    {
        $classGroups = ClassGroup::with('intake')
            ->when(!empty($this->search), fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        return [
            'classGroups' => $classGroups,
        ];
    }

    public function addClassGroup()
    {
        $this->validate();

        try {
            ClassGroup::create([
                'name' => $this->name,
                'intake_id' => $this->intake_id,
            ]);

            $this->resetForm();
            $this->resetPage();
            $this->dispatch('hide-class-group-modal');

            LivewireAlert::text('Class group added successfully.!')
                ->success()
                ->toast()
                ->position('top-end')
                ->show();

        } catch (\Exception $e) {
            Log::error('Error adding class group: ' . $e->getMessage());

            LivewireAlert::text('Failed to add Class group.!')
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }
    }

    public function editClassGroup($id)
    {
        $group = ClassGroup::findOrFail($id);

        $this->editId = $group->id;
        $this->name = $group->name;
        $this->intake_id = $group->intake_id;

        $this->dispatch('show-class-group-modal');
    }

    public function updateClassGroup()
    {
        $this->validate();

        try {
            $group = ClassGroup::findOrFail($this->editId);

            $group->update([
                'name' => $this->name,
                'intake_id' => $this->intake_id,
            ]);

            $this->resetForm();
            $this->resetPage();
            $this->dispatch('hide-class-group-modal');

            LivewireAlert::text('Class group updated successfully.!')
                ->success()
                ->toast()
                ->position('top-end')
                ->show();

        } catch (\Exception $e) {
            Log::error('Error updating class group: ' . $e->getMessage());

            LivewireAlert::text('Failed to update Class group.!')
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }
    }

    public function deleteClassGroup($id)
    {
        ClassGroup::findOrFail($id)->delete();
        $this->resetPage();

        LivewireAlert::text('Class group deleted successfully.!')
            ->success()
            ->toast()
            ->position('top-end')
            ->show();
    }

    public function deleteSelected()
    {
        ClassGroup::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();

        LivewireAlert::text('Class groups deleted successfully.!')
            ->success()
            ->toast()
            ->position('top-end')
            ->show();
    }

    private function resetForm()
    {
        $this->name = null;
        $this->intake_id = null;
        $this->editId = null;
        $this->search = null;
    }

    #[On('select-all')]
    public function selectAll()
    {
        if ($this->selectAll) {

            $currentPageClassGroupIds = ClassGroup::with('intake')
                ->when(!empty($this->search), fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->latest()
                ->paginate(10)
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();

            $this->selected = $currentPageClassGroupIds;
        } else {
            $this->selected = [];
        }
    }

    public function exportExcel()
    {
        return Excel::download(new ClassGroupExport(), 'class-groups.xlsx');
    }


}; ?>


@push('styles')
    <style>
        .pagination {
            margin-left: 10px;
        }
    </style>
@endpush

<div class="row">
    <div class="col-12">
        <div class="widget-content searchable-container list">
            <div class="card card-body">
                <div class="row">
                    <div class="col-md-4 col-xl-3">
                        <form class="position-relative">
                            <input wire:keyup . debounce.100ms="$dispatch('search')"
                                   type="text"
                                   class="form-control product-search ps-5"
                                   placeholder="Search Class Groups..."
                                   wire:model="search"/>
                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div
                        class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                        @if (count($selected) > 0)
                            <div class="action-btn">
                                <a href="javascript:void(0)" wire:click.prevent="deleteSelected"
                                   class="delete-multiple bg-danger-subtle btn me-2 text-danger">
                                    <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                                </a>
                            </div>
                        @endif

                        <a href="javascript:void(0)" wire:click="$dispatch('show-class-group-modal')"
                           class="btn btn-primary d-flex align-items-center">
                            <i class="ti ti-building text-white me-1 fs-5"></i> Add Class Group
                        </a>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="addClassGroupModal" tabindex="-1" role="dialog"
                 aria-labelledby="addClassGroupModalTitle" aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex align-items-center">
                            <h5 class="modal-title">Class Group</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form wire:submit.prevent="{{ $editId ? 'updateClassGroup' : 'addClassGroup' }}">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input type="text" wire:model.live="name" class="form-control"
                                               placeholder="Class Group Name"/>
                                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <select wire:model.live="intake_id" class="form-control">
                                            <option value="">Select Intake</option>
                                            @foreach ($intakes as $intake)
                                                <option value="{{ $intake->id }}">{{ $intake->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('intake_id') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="d-flex gap-1 m-0">
                                    <button type="submit" class="btn btn-success">
                                        {{ $editId ? 'Save' : 'Add' }}
                                    </button>
                                    <button type="button" class="btn bg-danger-subtle text-danger"
                                            data-bs-dismiss="modal">Discard
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Class Groups Table -->
            <div class="card card-body">
                <div class="table-responsive">

                    <!-- Top Bar Inside the Card -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 px-2">
                        <!-- Title -->
                        <h6 class="mb-0 fw-semibold text-primary d-flex align-items-center">
                            <iconify-icon icon="mdi:account-group-outline" class="me-2"
                                          style="font-size: 20px;"></iconify-icon>
                            ClassGroups List
                        </h6>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 flex-wrap">

                            <!-- Export Excel Button -->
                            <button wire:click="exportExcel"
                                    class="btn btn-outline-success btn-sm d-flex align-items-center px-3 py-1 rounded">
                                <iconify-icon icon="mdi:file-excel-outline" class="me-1"
                                              style="font-size: 18px;"></iconify-icon>
                                Excel
                            </button>

                            <!-- Export PDF Button -->
                            <button wire:click="exportPdf"
                                    class="btn btn-outline-danger btn-sm d-flex align-items-center px-3 py-1 rounded">
                                <iconify-icon icon="mdi:file-pdf-box" class="me-1"
                                              style="font-size: 18px;"></iconify-icon>
                                PDF
                            </button>
                        </div>
                    </div>

                    <table class="table search-table align-middle text-nowrap">
                        <thead class="header-item">
                        <tr>
                            <th>
                                <div class="form-check text-center">
                                    <input wire:click="$dispatch('select-all')" type="checkbox" class="form-check-input"
                                           wire:model="selectAll"/>
                                </div>
                            </th>
                            <th>Name</th>
                            <th>Intake</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($classGroups as $group)
                            <tr class="search-items">
                                <td>
                                    <div class="form-check text-center">
                                        <input type="checkbox" class="form-check-input"
                                               wire:model="selected" value="{{ (string) $group->id }}"/>
                                    </div>
                                </td>
                                <td>{{ $group->name }}</td>
                                <td>{{ $group->intake->name ?? 'N/A' }}</td>
                                <td>
                                    <div class="action-btn">
                                        <a href="javascript:void(0)" wire:click="editClassGroup({{ $group->id }})"
                                           class="text-primary">
                                            <i class="ti ti-eye fs-5"></i>
                                        </a>
                                        <a href="javascript:void(0)" wire:click="deleteClassGroup({{ $group->id }})"
                                           class="text-dark ms-2">
                                            <i class="ti ti-trash fs-5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No class groups found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    {{-- Add the pagination links here --}}
                    <div class="d-flex justify-content-center mt-4">
                        {{ $classGroups->links() }}
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
    <script>
        window.addEventListener('show-class-group-modal', () => {
            new bootstrap.Modal(document.getElementById('addClassGroupModal')).show();
        });

        window.addEventListener('hide-class-group-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('addClassGroupModal'))?.hide();
        });
    </script>
@endpush






