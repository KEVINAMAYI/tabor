<?php

use App\Models\Classes;
use App\Models\AcademicYear;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;

new class extends Component {

    public $classes = [];

    public $name, $academic_year_id;

    public $academicYears = [];

    public $editId = null;

    public $selected = [];

    public $selectAll = false;

    public $search = '';

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
        ];
    }

    public function mount()
    {
        $this->academicYears = AcademicYear::latest()->get();
        $this->loadClasses();
    }

    #[On('search')]
    public function search()
    {
        $this->loadClasses();
    }

    public function loadClasses()
    {
        $this->classes = Classes::with('academicYear')
            ->when($this->search, fn($q) => $q->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->get();
    }

    public function addClass()
    {
        $this->validate();

        try {
            Classes::create([
                'name' => $this->name,
                'academic_year_id' => $this->academic_year_id,
            ]);

            $this->resetForm();
            $this->loadClasses();
            $this->dispatch('hide-class-modal');

        } catch (\Exception $e) {
            Log::error('Error adding class: ' . $e->getMessage());
            session()->flash('error', 'Failed to add class.');
        }
    }

    public function editClass($id)
    {
        $class = Classes::findOrFail($id);

        $this->editId = $class->id;
        $this->name = $class->name;
        $this->academic_year_id = $class->academic_year_id;

        $this->dispatch('show-class-modal');
    }

    public function updateClass()
    {
        $this->validate();

        try {
            $class = Classes::findOrFail($this->editId);

            $class->update([
                'name' => $this->name,
                'academic_year_id' => $this->academic_year_id,
            ]);

            $this->resetForm();
            $this->loadClasses();
            $this->dispatch('hide-class-modal');

        } catch (\Exception $e) {
            Log::error('Error updating class: ' . $e->getMessage());
            session()->flash('error', 'Update failed.');
        }
    }

    public function deleteClass($id)
    {
        Classes::findOrFail($id)->delete();
        $this->loadClasses();
    }

    public function deleteSelected()
    {
        Classes::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
        $this->loadClasses();
    }

    private function resetForm()
    {
        $this->name = $this->academic_year_id = null;
        $this->editId = null;
    }

    #[On('select-all')]
    public function selectAll()
    {
        if ($this->selectAll) {
            $this->selected = $this->classes->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selected = [];
        }
    }

}; ?>

<div class="row">
    <div class="col-12">
        <div class="widget-content searchable-container list">
            <div class="card card-body">
                <div class="row">
                    <div class="col-md-4 col-xl-3">
                        <form class="position-relative">
                            <input wire:keyup.debounce.100ms="$dispatch('search')" type="text"
                                   class="form-control product-search ps-5" placeholder="Search Classes..."
                                   wire:model="search"/>
                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                        @if (count($selected) > 0)
                            <div class="action-btn">
                                <a href="javascript:void(0)" wire:click.prevent="deleteSelected"
                                   class="delete-multiple bg-danger-subtle btn me-2 text-danger">
                                    <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                                </a>
                            </div>
                        @endif

                        <a href="javascript:void(0)" wire:click="$dispatch('show-class-modal')"
                           class="btn btn-primary d-flex align-items-center">
                            <i class="ti ti-building text-white me-1 fs-5"></i> Add Class
                        </a>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="addClassModal" tabindex="-1" role="dialog"
                 aria-labelledby="addClassModalTitle" aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex align-items-center">
                            <h5 class="modal-title">Class</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form wire:submit.prevent="{{ $editId ? 'updateClass' : 'addClass' }}">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input type="text" wire:model.live="name" class="form-control" placeholder="Class Name"/>
                                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <select wire:model.live="academic_year_id" class="form-control">
                                            <option value="">Select Academic Year</option>
                                            @foreach ($academicYears as $year)
                                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('academic_year_id') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="d-flex gap-6 m-0">
                                    <button type="submit" class="btn btn-success">
                                        {{ $editId ? 'Save' : 'Add' }}
                                    </button>
                                    <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Discard</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card card-body">
                <div class="table-responsive">
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
                            <th>Academic Year</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($classes as $class)
                            <tr class="search-items">
                                <td>
                                    <div class="form-check text-center">
                                        <input type="checkbox" class="form-check-input"
                                               wire:model="selected" value="{{ (string) $class->id }}"/>
                                    </div>
                                </td>
                                <td>{{ $class->name }}</td>
                                <td>{{ $class->academicYear->name ?? 'N/A' }}</td>
                                <td>
                                    <div class="action-btn">
                                        <a href="javascript:void(0)" wire:click="editClass({{ $class->id }})" class="text-primary">
                                            <i class="ti ti-eye fs-5"></i>
                                        </a>
                                        <a href="javascript:void(0)" wire:click="deleteClass({{ $class->id }})" class="text-dark ms-2">
                                            <i class="ti ti-trash fs-5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No classes found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        window.addEventListener('show-class-modal', () => {
            new bootstrap.Modal(document.getElementById('addClassModal')).show();
        });

        window.addEventListener('hide-class-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('addClassModal'))?.hide();
        });
    </script>
@endpush





