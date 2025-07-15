<?php

use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    /* ------------- Public props ------------- */
    public $lecturers = [];
    public $selectAll = false;

    /* Form fields */
    public $first_name, $last_name, $email, $phone_number;
    public $kra_pin, $id_number, $next_of_kin, $alternative_contact;
    public $date_of_birth;   // keep only if column exists

    public $editId = null;
    public $selected = [];
    public $search = '';

    /* ------------- Validation ------------- */
    public function rules()
    {
        return [
            'first_name'           => 'required|string|max:255',
            'last_name'            => 'required|string|max:255',
            'email'                => 'required|email',
            'phone_number'         => 'required|numeric',
            'kra_pin'              => 'nullable|string|max:20|alpha_num',
            'id_number'            => 'nullable|string|max:20',
            'next_of_kin'          => 'nullable|string|max:255',
            'alternative_contact'  => 'nullable|string|max:20',
            'date_of_birth'        => 'nullable|date',
        ];
    }

    /* ------------- Lifecycle ------------- */
    public function mount() { $this->loadLecturers(); }

    #[On('search')] public function search() { $this->loadLecturers(); }

    /* ------------- Fetch records ------------- */
    public function loadLecturers()
    {
        $this->lecturers = Lecturer::with('user')
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhere('kra_pin', 'like', "%{$this->search}%")
                    ->orWhere('id_number', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->get();
    }

    /* ------------- Create ------------- */
    public function addLecturer()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $user = User::create([
                'name'     => $this->first_name.' '.$this->last_name,
                'email'    => $this->email,
                'password' => Hash::make('password'),
            ]);

            Lecturer::create([
                'first_name'          => $this->first_name,
                'last_name'           => $this->last_name,
                'email'               => $this->email,
                'phone'               => $this->phone_number,
                'kra_pin'             => $this->kra_pin,
                'id_number'           => $this->id_number,
                'next_of_kin'         => $this->next_of_kin,
                'alternative_contact' => $this->alternative_contact,
                'dob'                 => $this->date_of_birth,
                'user_id'             => $user->id,
            ]);

            DB::commit();

            $this->resetForm();
            $this->loadLecturers();
            $this->dispatch('hide-lecturer-modal');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding lecturer: '.$e->getMessage());
            session()->flash('error', 'Failed to add lecturer.');
        }
    }

    /* ------------- Edit ------------- */
    public function editLecturer($id)
    {
        $lec = Lecturer::with('user')->findOrFail($id);

        $this->editId             = $lec->id;
        $this->first_name         = $lec->first_name;
        $this->last_name          = $lec->last_name;
        $this->email              = $lec->email;
        $this->phone_number       = $lec->phone;
        $this->kra_pin            = $lec->kra_pin;
        $this->id_number          = $lec->id_number;
        $this->next_of_kin        = $lec->next_of_kin;
        $this->alternative_contact= $lec->alternative_contact;
        $this->date_of_birth      = $lec->dob;

        $this->dispatch('show-lecturer-modal');
    }

    public function updateLecturer()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $lec = Lecturer::with('user')->findOrFail($this->editId);

            $lec->user->update([
                'name'  => $this->first_name.' '.$this->last_name,
                'email' => $this->email,
            ]);

            $lec->update([
                'first_name'          => $this->first_name,
                'last_name'           => $this->last_name,
                'email'               => $this->email,
                'phone'               => $this->phone_number,
                'kra_pin'             => $this->kra_pin,
                'id_number'           => $this->id_number,
                'next_of_kin'         => $this->next_of_kin,
                'alternative_contact' => $this->alternative_contact,
                'dob'                 => $this->date_of_birth,
            ]);

            DB::commit();

            $this->resetForm();
            $this->loadLecturers();
            $this->dispatch('hide-lecturer-modal');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update lecturer: '.$e->getMessage());
            session()->flash('error', 'Update failed.');
        }
    }

    /* ------------- Delete ------------- */
    public function deleteLecturer($id)
    {
        Lecturer::findOrFail($id)->user()->delete();
        $this->loadLecturers();
    }

    public function deleteSelected()
    {
        Lecturer::whereIn('id', $this->selected)->get()
            ->each(fn ($l) => $l->user()->delete());

        $this->selected = []; $this->selectAll = false;
        $this->loadLecturers();
    }

    /* ------------- Utilities ------------- */
    private function resetForm()
    {
        foreach (['first_name','last_name','email','phone_number','kra_pin',
                     'id_number','next_of_kin','alternative_contact','date_of_birth'] as $prop) {
            $this->$prop = null;
        }
        $this->editId = null;
    }

    #[On('select-all')]
    public function selectAll()
    {
        $this->selected = $this->selectAll
            ? $this->lecturers->pluck('id')->map(fn ($id) => (string)$id)->toArray()
            : [];
    }
}; ?>

    <!-- ===============================  HTML  =============================== -->
<div class="row">
    <div class="col-12">
        <div class="widget-content searchable-container list">

            <!-- Search & Add -->
            <div class="card card-body">
                <div class="row">
                    <div class="col-md-4 col-xl-3">
                        <form class="position-relative">
                            <input type="text" class="form-control product-search ps-5"
                                   placeholder="Search Lecturers..." wire:model="search"
                                   wire:keyup.debounce.100ms="$dispatch('search')">
                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                        @if(count($selected))
                            <a href="javascript:void(0)"  class="delete-multiple bg-danger-subtle btn me-2 text-danger"
                               wire:click.prevent="deleteSelected">
                                <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                            </a>
                        @endif
                        <a href="javascript:void(0)" class="btn btn-primary d-flex align-items-center"
                           wire:click="$dispatch('show-lecturer-modal')">
                            <i class="ti ti-users text-white me-1 fs-5"></i> Add Lecturer
                        </a>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="addLecturerModal" tabindex="-1" wire:ignore.self>
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header d-flex align-items-center">
                            <h5 class="modal-title">Lecturer</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form wire:submit.prevent="{{ $editId ? 'updateLecturer' : 'addLecturer' }}">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input type="text" class="form-control" placeholder="First Name"
                                               wire:model.live="first_name">
                                        @error('first_name')<small class="text-danger">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="text" class="form-control" placeholder="Last Name"
                                               wire:model.live="last_name">
                                        @error('last_name')<small class="text-danger">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="email" class="form-control" placeholder="Email"
                                               wire:model.live="email">
                                        @error('email')<small class="text-danger">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="text" class="form-control" placeholder="Phone Number"
                                               wire:model.live="phone_number">
                                        @error('phone_number')<small class="text-danger">{{ $message }}</small>@enderror
                                    </div>

                                    <!-- New details -->
                                    <div class="col-md-6 mb-3">
                                        <input type="text" class="form-control" placeholder="KRA PIN"
                                               wire:model.live="kra_pin">
                                        @error('kra_pin')<small class="text-danger">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="text" class="form-control" placeholder="ID Number"
                                               wire:model.live="id_number">
                                        @error('id_number')<small class="text-danger">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="text" class="form-control" placeholder="Next of Kin"
                                               wire:model.live="next_of_kin">
                                        @error('next_of_kin')<small class="text-danger">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="text" class="form-control" placeholder="Alternative Contact"
                                               wire:model.live="alternative_contact">
                                        @error('alternative_contact')<small class="text-danger">{{ $message }}</small>@enderror
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <input type="date" class="form-control" placeholder="Date of Birth"
                                               wire:model.live="date_of_birth">
                                        @error('date_of_birth')<small class="text-danger">{{ $message }}</small>@enderror
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

            <!-- Table -->
            <div class="card card-body">
                <div class="table-responsive">
                    <table class="table search-table align-middle text-nowrap">
                        <thead class="header-item">
                        <tr>
                            <th style="width:42px;">
                                <input type="checkbox" class="form-check-input"
                                       wire:model="selectAll" wire:click="$dispatch('select-all')">
                            </th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>KRA PIN</th>
                            <th>ID No.</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($lecturers as $lec)
                            <tr class="search-items">
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input"
                                           wire:model="selected" value="{{ (string) $lec->id }}">
                                </td>
                                <td>{{ $lec->first_name }} {{ $lec->last_name }}</td>
                                <td>{{ $lec->email }}</td>
                                <td>{{ $lec->phone }}</td>
                                <td>{{ $lec->kra_pin ?? '—' }}</td>
                                <td>{{ $lec->id_number ?? '—' }}</td>
                                <td>
                                    <div class="action-btn">
                                        <a href="javascript:void(0)"  wire:click="editLecturer({{ $lec->id }})" class="text-primary">
                                            <i class="ti ti-pencil fs-5"></i>
                                        </a>
                                        <a href="javascript:void(0)"  wire:click="deleteLecturer({{ $lec->id }})" class="text-dark ms-2">
                                            <i class="ti ti-trash fs-5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center">No lecturers found.</td></tr>
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
        window.addEventListener('show-lecturer-modal', () => {
            new bootstrap.Modal(document.getElementById('addLecturerModal')).show();
        });
        window.addEventListener('hide-lecturer-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('addLecturerModal'))?.hide();
        });
    </script>
@endpush
