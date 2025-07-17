<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $roles = [];
    public $permissions = [];
    public $selectedPermissions = [];
    public $groupedPermissions = [];

    public $name,
        $editId = null,
        $search = '';

    public function mount()
    {
        if (!auth()->user()->hasPermissionTo('view-roles')) {
            abort(403, 'Unauthorized action.');
        }
        $this->loadRoles();
        // Auth::user()->syncRoles('admin');
        $permissions = Permission::query()->get()->toBase();
        $this->permissions = $permissions;

        $this->groupedPermissions = $this->permissions->groupBy(function ($permission) {
            $parts = explode('-', $permission->name);
            return $parts[1] ?? 'general';
        });
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name' . ($this->editId ? ',' . $this->editId : ''),
            'selectedPermissions' => 'required|array|min:1',
        ];
    }

    #[On('search')]
    public function search()
    {
        $this->loadRoles();
    }

    public function loadRoles()
    {
        $this->roles = Role::when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))->get();
    }

    public function store()
    {
        $this->validate();
        try {
            DB::beginTransaction();

            $role = Role::create(['name' => strtolower($this->name)]);
            $role->syncPermissions($this->selectedPermissions);

            DB::commit();

            $this->resetForm();
            $this->loadRoles();
            $this->dispatch('hide-roles-modal');

            LivewireAlert::text('Role added successfully.!')
                ->success()
                ->toast()
                ->position('top-end')
                ->show();

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error adding role: ' . $th->getMessage());

            LivewireAlert::text('Failed to add role.!')
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }
    }

    public function editRole($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $this->editId = $role->id;
        $this->name = strtolower($role->name);
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->dispatch('show-roles-modal');
    }

    public function updateRole()
    {
        $this->validate();
        try {
            DB::beginTransaction();

            $role = Role::findOrFail($this->editId);
            $role->update(['name' => $this->name]);
            $role->syncPermissions($this->selectedPermissions);

            DB::commit();

            $this->resetForm();
            $this->loadRoles();
            $this->dispatch('hide-roles-modal');

            LivewireAlert::text('Role updated successfully.!')
                ->success()
                ->toast()
                ->position('top-end')
                ->show();

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error updating role: ' . $th->getMessage());

            LivewireAlert::text('Failed to update role.!')
                ->error()
                ->toast()
                ->position('top-end')
                ->show();

        }
    }

    public function deleteRole($id)
    {
        try {
            DB::beginTransaction();

            $role = Role::findOrFail($id);
            $role->delete();

            DB::commit();
            $this->loadRoles();

            LivewireAlert::text('Role deleted successfully.!')
                ->success()
                ->toast()
                ->position('top-end')
                ->show();

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error deleting role: ' . $th->getMessage());

            LivewireAlert::text('Failed to delete role.!')
                ->error()
                ->toast()
                ->position('top-end')
                ->show();

        }
    }

    public function resetForm()
    {
        $this->name = null;
        $this->editId = null;
        $this->selectedPermissions = [];
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
                                   class="form-control product-search ps-5" placeholder="Search..."
                                   wire:model="search"/>
                            <i
                                class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div
                        class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">

                        <a href="javascript:void(0)" wire:click="$dispatch('show-roles-modal');"
                           wire:click="resetForm()" class="btn btn-primary d-flex align-items-center">
                            <i class="ti ti-users text-white me-1 fs-5"></i> Add Role
                        </a>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="rolesModal" tabindex="-1" role="dialog" aria-labelledby="rolesModalTitle"
                 aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex align-items-center">
                            <h5 class="modal-title">{{ $editId ? 'Update' : 'Add' }} Role</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                        </div>
                        <form wire:submit.prevent="{{ $editId ? 'updateRole' : 'store' }}">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <input type="text" wire:model.live="name" class="form-control"
                                           placeholder="Role Name"/>
                                    @error('name')
                                    <small class="text-error">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Permissions</label>
                                    <div class="row">

                                        @foreach ($groupedPermissions as $module => $perms)
                                            <div class="mb-2">
                                                <div class="text-primary mt-3 mb-2 fw-bold">{{ ucfirst($module) }}</div>
                                                <div class="row">
                                                    @foreach ($perms as $perm)
                                                        <div class="col-md-3 mb-1">
                                                            <label class="form-check">
                                                                <input type="checkbox" value="{{ $perm->name }}"
                                                                       wire:model.live="selectedPermissions"
                                                                       class="form-check-input"/>
                                                                {{ ucwords(str_replace('-', ' ', $perm->name)) }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @error('selectedPermissions')
                                    <small class="text-error">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="d-flex gap-6 m-0">
                                    <button type="submit" class="btn btn-success">
                                        {{ $editId ? 'Save' : 'Add' }}
                                    </button>
                                    <button type="button" class="btn bg-error-subtle text-error"
                                            data-bs-dismiss="modal">Discard
                                    </button>
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
                            <th>Name</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>

                        @forelse ($roles as $role)
                            <tr class="search-items">
                                <td>{{ ucfirst($role->name) }}</td>
                                <td>
                                    <div class="action-btn">
                                        <a href="javascript:void(0)" wire:click="editRole({{ $role->id }})"
                                           class="text-primary">
                                            <i class="ti ti-pencil fs-5"></i>
                                        </a>
                                        <a href="javascript:void(0)" wire:click="deleteRole({{ $role->id }})"
                                           class="text-dark ms-2">
                                            <i class="ti ti-trash fs-5 text-error"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No roles found.</td>
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
        window.addEventListener('show-roles-modal', () => {
            new bootstrap.Modal(document.getElementById('rolesModal')).show();
        });

        window.addEventListener('hide-roles-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('rolesModal'))?.hide();
        });
    </script>
@endpush
