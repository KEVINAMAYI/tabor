<?php

use App\Models\Team;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {

    use WithPagination, WithFileUploads;

    public $name, $title, $description, $image, $editId = null;
    public $search = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'title' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'image' => 'nullable|image|max:2048',
    ];


    #[On('search')]
    public function getTeamsProperty()
    {
        return Team::where('name', 'like', "%{$this->search}%")
            ->orWhere('title', 'like', "%{$this->search}%")
            ->orderBy('id', 'desc')
            ->paginate(10);
    }

    public function add()
    {
        $this->validate();

        $imagePath = $this->image
            ? $this->image->store('team', 'public')
            : null;

        Team::create([
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $imagePath
        ]);

        $this->resetForm();
        $this->dispatch('hide-team-modal');
    }

    public function edit($id)
    {
        $team = Team::findOrFail($id);

        $this->editId = $team->id;
        $this->name = $team->name;
        $this->title = $team->title;
        $this->description = $team->description;

        $this->dispatch('show-team-modal');
    }

    public function update()
    {
        $this->validate();

        $team = Team::findOrFail($this->editId);

        $imagePath = $team->image;

        if ($this->image) {
            $imagePath = $this->image->store('team', 'public');
        }

        $team->update([
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $imagePath
        ]);

        $this->resetForm();
        $this->dispatch('hide-team-modal');
    }

    public function delete($id)
    {
        Team::findOrFail($id)->delete();
    }

    private function resetForm()
    {
        $this->name = $this->title = $this->description = '';
        $this->image = null;
        $this->editId = null;
    }

};
?>


<div>


    <div class="card card-body">
        <div class="row">
            <div class="col-md-4 col-xl-3">
                <form class="position-relative">
                    <input wire:keyup.debounce.100ms="$dispatch('search')" type="text"
                           class="form-control product-search ps-5" placeholder="Search Team Member..."
                           wire:model="search"/>
                    <i
                        class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                </form>
            </div>
            <div
                class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">

                <div class="action-btn">
                </div>

                <a href="javascript:void(0)" wire:click="$dispatch('show-team-modal')"
                   class="btn btn-primary d-flex align-items-center">
                    <i class="ti ti-users text-white me-1 fs-5"></i> Add Team Member
                </a>
            </div>
        </div>
    </div>


    <div class="card card-body">

        <!-- Table Responsive -->
        <div class="table-responsive">

            <!-- Top Bar Inside the Card -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 px-2">
                <!-- Per Page Selector -->
                <div class="d-flex align-items-center">
                    <label for="perPage" class="form-label me-2">Show</label>
                    <select wire:model.live="perPage" id="perPage" class="form-select form-select-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="ms-2">entries</span>
                </div>

                <!-- Title -->
                <h6 class="mb-0 fw-semibold text-primary d-flex align-items-center">
                    <iconify-icon icon="mdi:account-group" class="me-2" style="font-size: 20px;"></iconify-icon>
                    Team Members
                </h6>

            </div>

            <!-- Team Members Table -->
            <table class="table search-table align-middle text-nowrap">
                <thead class="header-item">
                <tr>
                    <th class="text-center align-middle" style="width: 50px;">#</th>
                    <th>Name</th>
                    <th>Role / Title</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($this->teams as $index => $member)
                    <tr class="search-items align-middle" style="border-bottom: 1px solid #e5e7eb;">
                        <!-- Index -->
                        <td class="text-center">{{ $index + 1 }}</td>

                        <!-- Name -->
                        <td class="fw-semibold">{{ $member->name }}</td>

                        <!-- Role / Title -->
                        <td>{{ $member->title ?? '-' }}</td>

                        <td class="text-muted small description-cell" title="{{ $member->description }}">
                            {{ $member->description ?? '-' }}
                        </td>

                        <!-- Image -->
                        <td>
                            @if($member->image)
                                <img src="{{ asset('storage/' . $member->image) }}" alt="{{ $member->name }}"
                                     class="rounded-circle" width="50" height="50">
                            @else
                                <span class="text-muted">No Image</span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td>
                            <div class="dropdown dropstart">
                                <a href="javascript:void(0)" class="link" id="team-actions-{{ $member->id }}"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-6" style="color: #0e334f;"></i>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="team-actions-{{ $member->id }}">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2"
                                           href="javascript:void(0)" wire:click="edit({{ $member->id }})">
                                            <iconify-icon icon="mdi:pencil-outline"
                                                          class="text-warning w-4 h-4"></iconify-icon>
                                            <span>Edit</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                           href="javascript:void(0)" wire:click="delete({{ $member->id }})">
                                            <iconify-icon icon="mdi:delete-outline"
                                                          class="text-danger w-4 h-4"></iconify-icon>
                                            <span>Delete</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No team members found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>

        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $this->teams->links() }}
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="teamModal" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content p-3">
                <form wire:submit.prevent="{{ $editId ? 'update' : 'add' }}">
                    <h5 class="modal-title mb-3">
                        {{ $editId ? 'Edit Team Member' : 'Add Team Member' }}
                    </h5>

                    <!-- Name -->
                    <div class="mb-3">
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               placeholder="Full Name" wire:model="name">
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Title -->
                    <div class="mb-3">
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               placeholder="Title" wire:model="title">
                        @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                    <textarea class="form-control @error('description') is-invalid @enderror" rows="5"
                              placeholder="Description" wire:model="description"></textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Image -->
                    <div class="mb-3">
                        <input type="file" class="form-control @error('image') is-invalid @enderror" wire:model="image">
                        @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Buttons aligned to the right -->
                    <div class="text-end mt-3">
                        <button class="btn btn-success me-2">
                            {{ $editId ? 'Update' : 'Save' }}
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
    window.addEventListener('show-team-modal', () => {
        new bootstrap.Modal(document.getElementById('teamModal')).show();
    });

    window.addEventListener('hide-team-modal', () => {
        bootstrap.Modal.getInstance(document.getElementById('teamModal'))?.hide();
    });
</script>

