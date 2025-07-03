<?php

use App\Models\Intake;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    /* ───────── Public props ─────────────────────────── */
    public $intakes = [];
    public $selectAll = false;
    public $name, $starts_at, $ends_at;
    public $editId = null;
    public $selected = [];
    public $search = '';

    /* ───────── Validation rules ─────────────────────── */
    public function rules()
    {
        return [
            'name'      => 'required|string|max:255',
            'starts_at' => 'required|date',
            'ends_at'   => 'nullable|date|after_or_equal:starts_at',
        ];
    }

    /* ───────── Lifecycle ────────────────────────────── */
    public function mount()  { $this->loadIntakes(); }

    #[On('search')] public function search()  { $this->loadIntakes(); }

    /* ───────── Load records ─────────────────────────── */
    public function loadIntakes()
    {
        $this->intakes = Intake::when($this->search, fn ($q) =>
        $q->where('name', 'like', "%{$this->search}%"))
            ->latest()->get();
    }

    /* ───────── Create ──────────────────────────────── */
    public function addIntake()
    {
        $this->validate();

        try {
            Intake::create([
                'name'      => $this->name,
                'starts_at' => $this->starts_at,
                'ends_at'   => $this->ends_at,
            ]);

            $this->resetForm();
            $this->loadIntakes();
            $this->dispatch('hide-intake-modal');
        } catch (\Exception $e) {
            Log::error('Error adding intake: '.$e->getMessage());
            session()->flash('error', 'Failed to add intake.');
        }
    }

    /* ───────── Edit ─────────────────────────────────── */
    public function editIntake($id)
    {
        $intake        = Intake::findOrFail($id);
        $this->editId  = $intake->id;
        $this->name    = $intake->name;
        $this->starts_at = $intake->starts_at->format('Y-m-d');
        $this->ends_at   = optional($intake->ends_at)->format('Y-m-d');

        $this->dispatch('show-intake-modal');
    }

    public function updateIntake()
    {
        $this->validate();

        try {
            $intake = Intake::findOrFail($this->editId);
            $intake->update([
                'name'      => $this->name,
                'starts_at' => $this->starts_at,
                'ends_at'   => $this->ends_at,
            ]);

            $this->resetForm();
            $this->loadIntakes();
            $this->dispatch('hide-intake-modal');
        } catch (\Exception $e) {
            Log::error('Failed to update intake: '.$e->getMessage());
            session()->flash('error', 'Update failed. Please try again.');
        }
    }

    /* ───────── Delete ───────────────────────────────── */
    public function deleteIntake($id)
    {
        Intake::findOrFail($id)->delete();
        $this->loadIntakes();
    }

    public function deleteSelected()
    {
        Intake::whereIn('id', $this->selected)->delete();
        $this->selected = [];  $this->selectAll = false;
        $this->loadIntakes();
    }

    /* ───────── Helpers ─────────────────────────────── */
    private function resetForm()
    {
        $this->name = $this->starts_at = $this->ends_at = null;
        $this->editId = null;
    }

    #[On('select-all')] public function selectAll()
    {
        $this->selected = $this->selectAll
            ? $this->intakes->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

}; ?>

    <!-- ====================  Blade / HTML  ==================== -->
<div class="row">
    <div class="col-12">
        <div class="widget-content searchable-container list">

            <!-- ─── Search + Add button bar ───────────────────── -->
            <div class="card card-body">
                <div class="row">
                    <div class="col-md-4 col-xl-3">
                        <form class="position-relative">
                            <input type="text" class="form-control product-search ps-5"
                                   placeholder="Search Intakes..." wire:model="search"
                                   wire:keyup.debounce.100ms="$dispatch('search')">
                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                        @if (count($selected))
                            <a href="#" class="delete-multiple bg-danger-subtle btn me-2 text-danger"
                               wire:click.prevent="deleteSelected">
                                <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                            </a>
                        @endif
                        <a href="javascript:void(0)" class="btn btn-primary d-flex align-items-center"
                           wire:click="$dispatch('show-intake-modal')">
                            <i class="ti ti-calendar-plus text-white me-1 fs-5"></i> Add Intake
                        </a>
                    </div>
                </div>
            </div>

            <!-- ─── Add / Edit Modal ──────────────────────────── -->
            <div class="modal fade" id="addIntakeModal" tabindex="-1" wire:ignore.self>
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Intake</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form wire:submit.prevent="{{ $editId ? 'updateIntake' : 'addIntake' }}">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <input type="text" class="form-control" placeholder="Name (e.g. Jan 2026)"
                                           wire:model.live="name">
                                    @error('name') <small class="text-danger">{{ $message }}</small>@enderror
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small">Starts At</label>
                                        <input type="date" class="form-control" wire:model.live="starts_at">
                                        @error('starts_at') <small class="text-danger">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small">Ends At</label>
                                        <input type="date" class="form-control" wire:model.live="ends_at">
                                        @error('ends_at') <small class="text-danger">{{ $message }}</small>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer d-flex gap-6">
                                <button class="btn btn-success" type="submit">
                                    {{ $editId ? 'Save' : 'Add' }}
                                </button>
                                <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Discard</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ─── Intakes table ─────────────────────────────── -->
            <div class="card card-body">
                <div class="table-responsive">
                    <table class="table search-table align-middle text-nowrap">
                        <thead class="header-item">
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input text-center"
                                       wire:click="$dispatch('select-all')" wire:model="selectAll">
                            </th>
                            <th>Name</th>
                            <th>Starts</th>
                            <th>Ends</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($intakes as $intake)
                            <tr class="search-items">
                                <td>
                                    <div class="form-check text-center">
                                    <input type="checkbox" class="form-check-input"
                                           wire:model="selected" value="{{ (string)$intake->id }}">
                                    </div>
                                </td>
                                <td>{{ $intake->name }}</td>
                                <td>{{ $intake->starts_at->format('j M Y') }}</td>
                                <td>{{ optional($intake->ends_at)->format('j M Y') ?? '—' }}</td>
                                <td>
                                    <div class="action-btn">
                                        <a href="{{ route('intakes.view',$intake->id) }}"
                                           class="text-primary">
                                            <i class="ti ti-eye fs-5"></i>
                                        </a>
                                        <a href="javascript:void(0)" wire:click="editIntake({{ $intake->id }})" class="text-primary">
                                            <i class="ti ti-pencil fs-5"></i>
                                        </a>
                                        <a href="javascript:void(0)" wire:click="deleteIntake({{ $intake->id }})" class="text-dark ms-2">
                                            <i class="ti ti-trash fs-5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">No intakes found.</td></tr>
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
        window.addEventListener('show-intake-modal', () => {
            new bootstrap.Modal(document.getElementById('addIntakeModal')).show();
        });
        window.addEventListener('hide-intake-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('addIntakeModal'))?.hide();
        });
    </script>
@endpush
