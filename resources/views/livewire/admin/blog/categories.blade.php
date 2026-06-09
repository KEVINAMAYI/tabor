<?php

use App\Models\BlogCategory;
use App\Services\Blog\BlogCategoryService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public bool $editMode = false;

    public ?int $categoryId = null;
    public string $name = '';
    public string $description = '';
    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function with(): array
    {
        return [
            'categories' => BlogCategory::query()
                ->withCount('posts')
                ->when($this->search, function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')->orWhere('description', 'like', '%' . $this->search . '%');
                })
                ->latest()
                ->paginate(10),
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $category = BlogCategory::findOrFail($id);

        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->is_active = $category->is_active;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save(BlogCategoryService $service): void
    {
        $validated = $this->validate();

        try {
            if ($this->editMode && $this->categoryId) {
                $category = BlogCategory::findOrFail($this->categoryId);
                $service->update($category, $validated);

                session()->flash('success', 'Blog category updated successfully.');
            } else {
                $service->create($validated);

                session()->flash('success', 'Blog category created successfully.');
            }

            $this->resetForm();
        } catch (\Throwable $e) {
            Log::error('Blog category save failed', [
                'category_id' => $this->categoryId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Something went wrong while saving the category.');
        }
    }

    public function delete(int $id, BlogCategoryService $service): void
    {
        try {
            $category = BlogCategory::findOrFail($id);

            if ($category->posts()->exists()) {
                session()->flash('error', 'This category has blog posts. Move or delete the posts first.');
                return;
            }

            $service->delete($category);

            session()->flash('success', 'Blog category deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('Blog category delete failed', [
                'category_id' => $id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Something went wrong while deleting the category.');
        }
    }

    public function resetForm(): void
    {
        $this->reset(['showModal', 'editMode', 'categoryId', 'name', 'description', 'is_active']);

        $this->is_active = true;
        $this->resetValidation();
    }
};
?>

<div>
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-0">Blog Categories</h5>
                <small class="text-muted">Manage categories used by blog posts</small>
            </div>

            <button class="btn btn-primary" wire:click="create">
                <i class="ti ti-plus"></i> New Category
            </button>
        </div>

        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Search categories..."
                        wire:model.live.debounce.400ms="search">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Posts</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td>
                                    <strong>{{ $category->name }}</strong>
                                    <div class="text-muted small">{{ $category->slug }}</div>
                                </td>

                                <td>{{ str($category->description)->limit(80) }}</td>

                                <td>{{ $category->posts_count }}</td>

                                <td>
                                    @if ($category->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary"
                                        wire:click="edit({{ $category->id }})">
                                        Edit
                                    </button>

                                    <button class="btn btn-sm btn-outline-danger"
                                        wire:click="delete({{ $category->id }})" wire:confirm="Delete this category?">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No blog categories found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $categories->links() }}
        </div>
    </div>

    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form wire:submit.prevent="save">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                {{ $editMode ? 'Edit Category' : 'Create Category' }}
                            </h5>

                            <button type="button" class="btn-close" wire:click="resetForm"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" class="form-control" wire:model="name">
                                @error('name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" rows="3" wire:model="description"></textarea>
                                @error('description')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="categoryActive"
                                    wire:model="is_active">
                                <label class="form-check-label" for="categoryActive">
                                    Active
                                </label>
                            </div>

                            @error('is_active')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="resetForm">
                                Cancel
                            </button>

                            <button type="submit" class="btn btn-primary">
                                {{ $editMode ? 'Update Category' : 'Create Category' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
