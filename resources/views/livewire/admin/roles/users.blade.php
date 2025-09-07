<?php

use Spatie\Permission\Models\Role;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    use WithFileUploads, WithPagination;

    public $selectAll = false;

    public $first_name, $last_name, $email, $phone_number, $active, $dob, $role, $address, $kra_pin, $id_number, $next_of_kin, $next_of_kin_contact, $date_of_employment;

    public $editId = null;

    public $selected = [];

    public $search = '';

    public function rules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->editId,
            'phone_number' => 'required|string|max:20|unique:users,phone_number,' . $this->editId,
            'dob' => 'required|date',
            'role' => 'required|string|max:255',
            'id_number' => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
            'kra_pin' => 'nullable|string|max:20',
            'next_of_kin' => 'nullable|string|max:255',
            'next_of_kin_contact' => 'nullable|string',
            'date_of_employment' => 'nullable|date',
        ];
    }

    public function mount()
    {
        abort_if(!auth()->user()->hasPermissionTo('view-users'), 403, 'Unauthorized action.');
    }
    #[On('search')]
    public function search()
    {
        $this->resetPage();
    }

    public function with()
    {
        $users = User::whereHas('roles', fn($query) => $query->whereNotIn('name', ['student', 'lecturer', 'super-admin']))
            ->when(
                !empty($this->search),
                fn($q) => $q->where(function ($query) {
                    $search = $this->search;
                    $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('id_number', 'like', "%{$search}%");
                }),
            )
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        // dd($users->toArray());

        $roles = Role::whereNotIn('name', ['student', 'lecturer', 'super-admin'])->get();

        return [
            'users' => $users,
        ];
    }

    public function addUser()
    {
        // dd($this->all());
        $this->validate();

        try {
            DB::beginTransaction();

            $user = User::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'name' => $this->first_name . ' ' . $this->last_name,
                'email' => $this->email,
                'phone_number' => trim($this->phone_number),
                'password' => Hash::make(trim($this->phone_number)),
                'address' => $this->address,
                'kra_pin' => $this->kra_pin,
                'dob' => $this->dob,
                'id_number' => $this->id_number,
                'next_of_kin' => $this->next_of_kin,
                'next_of_kin_contact' => $this->next_of_kin_contact,
                'date_of_employment' => $this->date_of_employment,
                'active' => true,
            ]);

            // Assign the role to the user
            $user->assignRole($this->role);

            DB::commit();

            $this->resetForm();
            $this->resetPage();
            $this->dispatch('hide-user-modal');

            LivewireAlert::text('User added successfully.!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
            Log::error('Error adding user: ' . $e->getMessage());

            LivewireAlert::text('Failed to add user.!')->error()->toast()->position('top-end')->show();
        }
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);

        $this->editId = $user->id;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->phone_number = $user->phone_number;
        $this->dob = $user->dob;
        $this->address = $user->address;
        $this->kra_pin = $user->kra_pin;
        $this->id_number = $user->id_number;
        $this->next_of_kin = $user->next_of_kin;
        $this->next_of_kin_contact = $user->next_of_kin_contact;
        $this->date_of_employment = $user->date_of_employment;
        $this->active = $user->active;
        $this->role = $user->roles->first()->name ?? null;

        $this->dispatch('show-user-modal');
    }

    public function updateUser()
    {
        // dd($this->all());
        $this->validate();

        try {
            DB::beginTransaction();

            $user = User::findOrFail($this->editId);

            $user->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone_number' => trim($this->phone_number),
                'dob' => $this->dob,
                'address' => $this->address,
                'kra_pin' => $this->kra_pin,
                'id_number' => $this->id_number,
                'next_of_kin' => $this->next_of_kin,
                'next_of_kin_contact' => $this->next_of_kin_contact,
                'date_of_employment' => $this->date_of_employment,
                'active' => $this->active,
            ]);

            DB::commit();

            $this->resetForm();
            $this->dispatch('hide-user-modal');

            LivewireAlert::text('User updated successfully.!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update user: ' . $e->getMessage());

            LivewireAlert::text('Failed to update user.!')->error()->toast()->position('top-end')->show();
        }
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        $this->resetPage();

        LivewireAlert::text('User deleted successfully.!')->success()->toast()->position('top-end')->show();
    }

    public function deleteSelected()
    {
        $users = User::whereIn('id', $this->selected)->get();
        foreach ($users as $user) {
            $user->delete();
        }

        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();

        LivewireAlert::text('Users deleted successfully.!')->success()->toast()->position('top-end')->show();
    }

    private function resetForm()
    {
        $this->first_name = null;
        $this->last_name = null;
        $this->email = null;
        $this->phone_number = null;
        $this->dob = null;
        $this->address = null;
        $this->kra_pin = null;
        $this->id_number = null;
        $this->next_of_kin = null;
        $this->next_of_kin_contact = null;
        $this->date_of_employment = null;
        $this->active = null;
        $this->role = null;
    }

    #[On('select-all')]
    public function selectAll()
    {
        if ($this->selectAll) {
            $currentPageUserIds = User::when(
                !empty($this->search),
                fn($q) => $q->where(function ($query) {
                    $query
                        ->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone_number', 'like', "%{$this->search}%")
                        ->orWhere('id_number', 'like', "%{$this->search}%");
                }),
            )
                ->latest()
                ->paginate(10)
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();

            $this->selected = $currentPageUserIds;
        } else {
            $this->selected = [];
        }
    }
}; ?>

@push('styles')
    <style>
        .pagination {
            margin-left: 10px;
        }

        /* Base table hover and checkbox style */
        .custom-user-table tbody tr:hover {
            background-color: #fff6ee;
        }

        .custom-user-table .form-check-input:checked {
            background-color: #f69121;
            border-color: #f69121;
        }

        /* Action icon styling */
        .action-btn .action-icon {
            color: #446076;
            transition: color 0.2s ease;
        }

        .action-btn .action-icon:hover {
            color: #f69121;
        }

        /* Dropdown items */
        .dropdown-menu .dropdown-item {
            color: #446076;
            transition: background 0.2s ease;
        }

        .dropdown-menu .dropdown-item:hover {
            background-color: #fff6ee;
            color: #f69121;
        }

        /* Status badge styles */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-align: center;
        }

        .status-badge.active {
            background-color: #e6f4ea;
            color: #28a745;
        }

        .status-badge.inactive {
            background-color: #fce8e6;
            color: #dc3545;
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
                            <input wire:keyup.debounce.100ms="$dispatch('search')" type="text"
                                class="form-control product-search ps-5" placeholder="Search Students..."
                                wire:model="search" />
                            <i
                                class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div
                        class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">

                        @if (count($selected) > 0)
                            @can('delete-students')
                                <div class="action-btn">
                                    <a href="javascript:void(0)" wire:click.prevent="deleteSelected"
                                        class="delete-multiple bg-danger-subtle btn me-2 text-danger">
                                        <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                                    </a>
                                </div>
                            @endcan
                        @endif
                        @can('create-users')
                            <a href="javascript:void(0)" wire:click="$dispatch('show-user-modal')"
                                class="btn btn-primary d-flex align-items-center">
                                <i class="ti ti-users text-white me-1 fs-5"></i> Create User
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="card card-body">
                <div class="table-responsive">
                    <table class="table search-table align-middle text-nowrap custom-user-table">
                        <thead class="header-item">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>ID Number</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($users as $user)
                            <tr class="search-items">
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td style="color: #446076; font-weight: 500;">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone_number }}</td>
                                <td>{{ $user->id_number }}</td>
                                <td>{{ ucfirst(str_replace('-', ' ', $user->roles[0]->name)) }}</td>
                                <td>
                                    @if ($user->active)
                                        <span class="status-badge active">Active</span>
                                    @else
                                        <span class="status-badge inactive">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-btn dropdown">
                                        <a href="#" class="action-icon"
                                           id="userActions{{ $user->id }}"
                                           data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ti ti-dots-vertical fs-5"></i>
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="userActions{{ $user->id }}">
                                            @can('edit-users')
                                                <li>
                                                    <a href="javascript:void(0)" wire:click="editUser({{ $user->id }})"
                                                       class="dropdown-item">
                                                        <i class="ti ti-pencil fs-5 me-2"></i> Edit
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('delete-users')
                                                <li>
                                                    <a href="javascript:void(0)" wire:click="deleteUser({{ $user->id }})"
                                                       class="dropdown-item">
                                                        <i class="ti ti-trash fs-5 me-2"></i> Delete
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No users found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                </div>

                {{-- Add the pagination links here --}}
                <div class="d-flex justify-content-center mt-4">
                    {{ $users->links() }}
                </div>

            </div>

            <!-- Modal -->
            <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalTitle"
                aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex align-items-center">
                            <h5 class="modal-title">Add User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form wire:submit.prevent="{{ $editId ? 'updateUser' : 'addUser' }}">
                            <div class="modal-body">
                                <div class="row">
                                    <!-- First Name -->
                                    <div class="col-md-4 mb-3">
                                        <label for="first_name" class="form-label">First Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" wire:model.live="first_name" id="first_name"
                                            class="form-control" />
                                        @error('first_name')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Last Name -->
                                    <div class="col-md-4 mb-3">
                                        <label for="last_name" class="form-label">Last Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" wire:model.live="last_name" id="last_name"
                                            class="form-control" />
                                        @error('last_name')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Email -->
                                    <div class="col-md-4 mb-3">
                                        <label for="email" class="form-label">Email Address <span
                                                class="text-danger">*</span></label>
                                        <input type="email" wire:model.live="email" id="email"
                                            class="form-control" />
                                        @error('email')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Phone Number -->
                                    <div class="col-md-4 mb-3">
                                        <label for="phone_number" class="form-label">Phone Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text" wire:model.live="phone_number" id="phone_number"
                                            class="form-control" />
                                        @error('phone_number')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- ID Number -->
                                    <div class="col-md-4 mb-3">
                                        <label for="id_number" class="form-label">ID/Passport Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text" wire:model.live="id_number" id="id_number"
                                            class="form-control" />
                                        @error('id_number')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Date of Birth -->
                                    <div class="col-md-4 mb-3">
                                        <label for="dob" class="form-label">Date of Birth <span
                                                class="text-danger">*</span></label>
                                        <input type="date" wire:model.live="dob" id="dob"
                                            class="form-control" />
                                        @error('dob')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Role -->
                                    <div class="col-md-4 mb-3">
                                        <label for="role" class="form-label">Role <span
                                                class="text-danger">*</span></label>
                                        <select wire:model.live="role" id="role" class="form-select">
                                            <option value="">Select Role</option>
                                            @foreach (Spatie\Permission\Models\Role::whereNotIn('name', ['student', 'lecturer', 'super-admin'])->get() as $role)
                                                <option value="{{ $role->name }}">
                                                    {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('role')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Address -->
                                    <div class="col-md-4 mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" wire:model.live="address" id="address"
                                            class="form-control" />
                                        @error('address')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Date of Employment -->
                                    <div class="col-md-4 mb-3">
                                        <label for="date_of_employment" class="form-label">Employment Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" wire:model.live="date_of_employment"
                                            id="date_of_employment" class="form-control" />
                                        @error('date_of_employment')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- KRA Pin -->
                                    <div class="col-md-4 mb-3">
                                        <label for="kra_pin" class="form-label">KRA Pin</label>
                                        <input type="text" wire:model.live="kra_pin" id="kra_pin"
                                            class="form-control" />
                                        @error('kra_pin')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Next of Kin -->
                                    <div class="col-md-4 mb-3">
                                        <label for="next_of_kin" class="form-label">Next of Kin</label>
                                        <input type="text" wire:model.live="next_of_kin" id="next_of_kin"
                                            class="form-control" />
                                        @error('next_of_kin')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Next of Kin Contact -->
                                    <div class="col-md-4 mb-3">
                                        <label for="next_of_kin_contact" class="form-label">Next of Kin
                                            Contact</label>
                                        <input type="text" wire:model.live="next_of_kin_contact"
                                            id="next_of_kin_contact" class="form-control" />
                                        @error('next_of_kin_contact')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="status" class="form-label">Status <span
                                                class="text-danger">*</span></label>
                                        <select wire:model.live="active" id="active" class="form-select">
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                        @error('active')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                </div>

                                <!-- Modal Footer -->
                                <div class="modal-footer">
                                    <div class="d-flex gap-1 m-0">
                                        <button type="button" class="btn btn-danger bg-error-subtle"
                                            data-bs-dismiss="modal">Discard</button>
                                        <button type="submit" class="btn btn-success">
                                            {{ $editId ? 'Save' : 'Add' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        window.addEventListener('show-user-modal', () => {
            new bootstrap.Modal(document.getElementById('addUserModal')).show();
        });

        window.addEventListener('hide-user-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('addUserModal'))?.hide();
        });
    </script>
@endpush
