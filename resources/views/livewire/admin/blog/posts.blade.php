<?php

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\Blog\BlogPostService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, WithFileUploads;

    public string $search = '';
    public string $statusFilter = '';
    public string $categoryFilter = '';

    public bool $showModal = false;
    public bool $editMode = false;
    public bool $previewMode = false;

    public ?int $postId = null;
    public ?int $blog_category_id = null;

    public string $title = '';
    public string $slug = '';
    public string $excerpt = '';
    public string $content = '';
    public string $status = 'draft';
    public string $published_at = '';
    public string $meta_title = '';
    public string $meta_description = '';

    public $featured_image;
    public ?string $existing_featured_image = null;

    protected function rules(): array
    {
        return [
            'blog_category_id' => ['nullable', 'exists:blog_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('blog_posts', 'slug')->ignore($this->postId)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'featured_image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function with(): array
    {
        return [
            'posts' => BlogPost::query()
                ->with(['category', 'author'])
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('title', 'like', '%' . $this->search . '%')
                            ->orWhere('slug', 'like', '%' . $this->search . '%')
                            ->orWhere('excerpt', 'like', '%' . $this->search . '%')
                            ->orWhere('content', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->statusFilter, fn($query) => $query->where('status', $this->statusFilter))
                ->when($this->categoryFilter, fn($query) => $query->where('blog_category_id', $this->categoryFilter))
                ->latest()
                ->paginate(10),

            'categories' => BlogCategory::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    public function create(): void
    {
        $this->resetForm(false);

        $this->showModal = true;
        $this->previewMode = false;

        $this->dispatch('open-blog-post-modal', content: $this->content);
    }

    public function edit(int $id): void
    {
        $post = BlogPost::findOrFail($id);

        $this->postId = $post->id;
        $this->blog_category_id = $post->blog_category_id;
        $this->title = $post->title;
        $this->slug = $post->slug ?? '';
        $this->excerpt = $post->excerpt ?? '';
        $this->content = $post->content ?? '';
        $this->status = $post->status;
        $this->published_at = $post->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->meta_title = $post->meta_title ?? '';
        $this->meta_description = $post->meta_description ?? '';
        $this->existing_featured_image = $post->featured_image;

        $this->editMode = true;
        $this->showModal = true;
        $this->previewMode = false;

        $this->dispatch('open-blog-post-modal', content: $this->content);
    }

    public function updatedTitle(): void
    {
        if (blank($this->slug)) {
            $this->slug = Str::slug($this->title);
        }

        if (blank($this->meta_title)) {
            $this->meta_title = Str::limit($this->title, 60, '');
        }
    }

    public function updatedSlug(): void
    {
        $this->slug = Str::slug($this->slug);
    }

    public function generateExcerpt(): void
    {
        if (blank($this->content)) {
            return;
        }

        $this->excerpt = Str::limit(trim(strip_tags($this->content)), 250);
    }

    public function generateMetaTitle(): void
    {
        $this->meta_title = Str::limit($this->title, 60, '');
    }

    public function generateMetaDescription(): void
    {
        $source = $this->excerpt ?: strip_tags($this->content);

        $this->meta_description = Str::limit(trim($source), 155, '');
    }

    public function setPreviewMode(bool $value): void
    {
        $this->previewMode = $value;

        if (!$this->previewMode) {
            $this->dispatch('refresh-blog-editor', content: $this->content);
        }
    }

    public function wordCount(): int
    {
        return str_word_count(strip_tags($this->content));
    }

    public function characterCount(): int
    {
        return mb_strlen(trim(strip_tags($this->content)));
    }

    public function readingTime(): int
    {
        return max(1, (int) ceil($this->wordCount() / 200));
    }

    public function seoChecks(): array
    {
        return [['label' => 'Title added', 'passed' => filled($this->title)], ['label' => 'Slug added', 'passed' => filled($this->slug)], ['label' => 'Meta title added', 'passed' => filled($this->meta_title)], ['label' => 'Meta title below 60 characters', 'passed' => filled($this->meta_title) && mb_strlen($this->meta_title) <= 60], ['label' => 'Meta description added', 'passed' => filled($this->meta_description)], ['label' => 'Meta description below 160 characters', 'passed' => filled($this->meta_description) && mb_strlen($this->meta_description) <= 160], ['label' => 'Excerpt added', 'passed' => filled($this->excerpt)], ['label' => 'Featured image added', 'passed' => $this->featured_image || $this->existing_featured_image], ['label' => 'Content has at least 500 words', 'passed' => $this->wordCount() >= 500], ['label' => 'Content has headings', 'passed' => str_contains($this->content, '<h2') || str_contains($this->content, '<h3')]];
    }

    public function seoScore(): int
    {
        $checks = collect($this->seoChecks());

        return (int) round(($checks->where('passed', true)->count() / $checks->count()) * 100);
    }

    public function save(BlogPostService $service): void
    {
        $validated = $this->validate();

        try {
            if ($this->editMode && $this->postId) {
                $post = BlogPost::findOrFail($this->postId);

                $service->update($post, $validated, $this->featured_image);

                session()->flash('success', 'Blog post updated successfully.');
            } else {
                $service->create($validated, $this->featured_image);

                session()->flash('success', 'Blog post created successfully.');
            }

            $this->resetForm();
        } catch (\Throwable $e) {
            Log::error('Blog post save failed', [
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Something went wrong while saving the blog post.');
        }
    }

    public function delete(int $id, BlogPostService $service): void
    {
        try {
            $post = BlogPost::findOrFail($id);

            $service->delete($post);

            session()->flash('success', 'Blog post deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('Blog post delete failed', [
                'post_id' => $id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Something went wrong while deleting the blog post.');
        }
    }

    public function resetForm(bool $closeModal = true): void
    {
        $this->reset(['showModal', 'editMode', 'previewMode', 'postId', 'blog_category_id', 'title', 'slug', 'excerpt', 'content', 'status', 'published_at', 'meta_title', 'meta_description', 'featured_image', 'existing_featured_image']);

        $this->status = 'draft';

        $this->resetValidation();

        if ($closeModal) {
            $this->dispatch('close-blog-post-modal');
        }
    }
};
?>

<div>
    <div>
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0">Blog Posts</h5>
                    <small class="text-muted">Manage public website blog articles</small>
                </div>

                <button class="btn btn-primary" wire:click="create">
                    <i class="ti ti-plus"></i> New Post
                </button>
            </div>

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Search posts..."
                            wire:model.live.debounce.400ms="search">
                    </div>

                    <div class="col-md-3">
                        <select class="form-select" wire:model.live="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select class="form-select" wire:model.live="categoryFilter">
                            <option value="">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Post</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Published</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($posts as $post)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if ($post->featured_image)
                                                <img src="{{ asset('storage/' . $post->featured_image) }}"
                                                    class="rounded"
                                                    style="width: 52px; height: 52px; object-fit: cover;"
                                                    alt="{{ $post->title }}">
                                            @else
                                                <div class="rounded bg-light d-flex align-items-center justify-content-center"
                                                    style="width: 52px; height: 52px;">
                                                    <i class="ti ti-photo text-muted"></i>
                                                </div>
                                            @endif

                                            <div>
                                                <strong>{{ $post->title }}</strong>
                                                <div class="text-muted small">/blog/{{ $post->slug }}</div>
                                                <div class="text-muted small">
                                                    {{ str($post->excerpt)->limit(90) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>{{ $post->category?->name ?? 'Uncategorized' }}</td>

                                    <td>
                                        <span
                                            class="badge bg-{{ $post->status === 'published' ? 'success' : ($post->status === 'archived' ? 'secondary' : 'warning') }}">
                                            {{ ucfirst($post->status) }}
                                        </span>
                                    </td>

                                    <td>{{ $post->published_at?->format('d M Y H:i') ?? 'Not published' }}</td>

                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary"
                                            wire:click="edit({{ $post->id }})">
                                            Edit
                                        </button>

                                        <button class="btn btn-sm btn-outline-danger"
                                            wire:click="delete({{ $post->id }})"
                                            wire:confirm="Delete this blog post?">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No blog posts found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $posts->links() }}
            </div>
        </div>

        @if ($showModal)
            <div class="blog-post-modal-wrapper" id="blogPostModal">
                <div class="blog-post-modal-dialog">
                    <div class="modal-content blog-post-modal-content">
                        <form wire:submit.prevent="save" id="blog-post-form" class="h-100 d-flex flex-column">
                            <div class="blog-post-modal-header">
                                <div>
                                    <h5 class="mb-0">
                                        {{ $editMode ? 'Edit Blog Post' : 'Create Blog Post' }}
                                    </h5>
                                    <small class="text-muted">
                                        {{ $this->wordCount() }} words ·
                                        {{ $this->readingTime() }} min read ·
                                        SEO {{ $this->seoScore() }}%
                                    </small>
                                </div>

                                <button type="button" class="btn-close" wire:click="resetForm"></button>
                            </div>

                            <div class="blog-post-modal-body">
                                <div class="blog-editor-shell">
                                    <div class="blog-editor-main">
                                        <div class="card blog-editor-card mb-3">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Title</label>
                                                    <input type="text"
                                                        class="form-control form-control-lg blog-title-input"
                                                        placeholder="Enter a strong blog title..."
                                                        wire:model.live.debounce.500ms="title">

                                                    @error('title')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Slug</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">/blog/</span>
                                                        <input type="text" class="form-control"
                                                            placeholder="my-blog-post"
                                                            wire:model.live.debounce.500ms="slug">
                                                    </div>

                                                    @error('slug')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div class="mb-3">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center gap-2">
                                                        <label class="form-label fw-semibold mb-0">Excerpt</label>

                                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                            wire:click="generateExcerpt">
                                                            Generate
                                                        </button>
                                                    </div>

                                                    <textarea class="form-control mt-2" rows="3" placeholder="Short summary shown on blog listings..."
                                                        wire:model.live.debounce.500ms="excerpt"></textarea>

                                                    @error('excerpt')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div
                                                    class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                                    <label class="form-label fw-semibold mb-0">Content</label>

                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button"
                                                            class="btn {{ !$previewMode ? 'btn-primary' : 'btn-outline-primary' }}"
                                                            wire:click="setPreviewMode(false)">
                                                            Edit
                                                        </button>

                                                        <button type="button"
                                                            class="btn {{ $previewMode ? 'btn-primary' : 'btn-outline-primary' }}"
                                                            wire:click="setPreviewMode(true)">
                                                            Preview
                                                        </button>
                                                    </div>
                                                </div>

                                                @if (!$previewMode)
                                                    <div wire:ignore class="blog-editor-box">
                                                        <textarea id="blog-content-editor">{!! $content !!}</textarea>
                                                    </div>
                                                @else
                                                    <div class="blog-preview">
                                                        @if ($content)
                                                            {!! $content !!}
                                                        @else
                                                            <div class="text-muted text-center py-5">
                                                                Nothing to preview yet.
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @error('content')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="blog-editor-sidebar">
                                        <div class="card blog-editor-card mb-3">
                                            <div class="card-header">
                                                <strong>Publish</strong>
                                            </div>

                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select class="form-select" wire:model="status">
                                                        <option value="draft">Draft</option>
                                                        <option value="published">Published</option>
                                                        <option value="archived">Archived</option>
                                                    </select>

                                                    @error('status')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Published At</label>
                                                    <input type="datetime-local" class="form-control"
                                                        wire:model="published_at">

                                                    @error('published_at')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div>
                                                    <label class="form-label">Category</label>
                                                    <select class="form-select" wire:model="blog_category_id">
                                                        <option value="">Uncategorized</option>
                                                        @foreach ($categories as $category)
                                                            <option value="{{ $category->id }}">{{ $category->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    @error('blog_category_id')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card blog-editor-card mb-3">
                                            <div class="card-header">
                                                <strong>Featured Image</strong>
                                            </div>

                                            <div class="card-body">
                                                <input type="file" class="form-control"
                                                    wire:model="featured_image" accept="image/*">

                                                @error('featured_image')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror

                                                <div class="mt-3">
                                                    @if ($featured_image)
                                                        <img src="{{ $featured_image->temporaryUrl() }}"
                                                            class="rounded w-100 blog-featured-preview"
                                                            alt="New featured image">
                                                    @elseif ($existing_featured_image)
                                                        <img src="{{ asset('storage/' . $existing_featured_image) }}"
                                                            class="rounded w-100 blog-featured-preview"
                                                            alt="Featured image">
                                                    @else
                                                        <div class="blog-featured-placeholder">
                                                            <i class="ti ti-photo fs-3 mb-2"></i>
                                                            <div>No featured image selected</div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card blog-editor-card mb-3">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <strong>SEO</strong>

                                                <span
                                                    class="badge bg-{{ $this->seoScore() >= 80 ? 'success' : ($this->seoScore() >= 50 ? 'warning' : 'danger') }}">
                                                    {{ $this->seoScore() }}%
                                                </span>
                                            </div>

                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center gap-2">
                                                        <label class="form-label mb-0">Meta Title</label>
                                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                            wire:click="generateMetaTitle">
                                                            Generate
                                                        </button>
                                                    </div>

                                                    <input type="text" class="form-control mt-2"
                                                        wire:model.live.debounce.500ms="meta_title" maxlength="255">

                                                    <div class="small text-muted mt-1">
                                                        {{ mb_strlen($meta_title) }}/60 recommended
                                                    </div>

                                                    @error('meta_title')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div class="mb-3">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center gap-2">
                                                        <label class="form-label mb-0">Meta Description</label>
                                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                            wire:click="generateMetaDescription">
                                                            Generate
                                                        </button>
                                                    </div>

                                                    <textarea class="form-control mt-2" rows="3" wire:model.live.debounce.500ms="meta_description"
                                                        maxlength="500"></textarea>

                                                    <div class="small text-muted mt-1">
                                                        {{ mb_strlen($meta_description) }}/160 recommended
                                                    </div>

                                                    @error('meta_description')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <ul class="list-unstyled mb-0 small seo-check-list">
                                                    @foreach ($this->seoChecks() as $check)
                                                        <li class="d-flex align-items-center gap-2 mb-2">
                                                            @if ($check['passed'])
                                                                <i class="ti ti-circle-check text-success"></i>
                                                            @else
                                                                <i class="ti ti-alert-circle text-warning"></i>
                                                            @endif

                                                            <span
                                                                class="{{ $check['passed'] ? 'text-muted' : 'text-dark' }}">
                                                                {{ $check['label'] }}
                                                            </span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="card blog-editor-card">
                                            <div class="card-header">
                                                <strong>Stats</strong>
                                            </div>

                                            <div class="card-body">
                                                <div class="row text-center g-2">
                                                    <div class="col-4">
                                                        <div class="blog-stat-box">
                                                            <div class="fw-bold">{{ $this->wordCount() }}</div>
                                                            <small>Words</small>
                                                        </div>
                                                    </div>

                                                    <div class="col-4">
                                                        <div class="blog-stat-box">
                                                            <div class="fw-bold">{{ $this->readingTime() }}</div>
                                                            <small>Mins</small>
                                                        </div>
                                                    </div>

                                                    <div class="col-4">
                                                        <div class="blog-stat-box">
                                                            <div class="fw-bold">{{ $this->characterCount() }}</div>
                                                            <small>Chars</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="blog-post-modal-footer">
                                <button type="button" class="btn btn-light" wire:click="resetForm">
                                    Cancel
                                </button>

                                <button type="submit" class="btn btn-primary px-4">
                                    {{ $editMode ? 'Update Post' : 'Create Post' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('styles')
        <style>
            .blog-post-modal-wrapper {
                position: fixed;
                inset: 0;
                z-index: 1055;
                background: rgba(0, 0, 0, .55);
                padding: 14px;
                overflow: hidden;
            }

            .blog-post-modal-dialog {
                width: min(1600px, 100%);
                max-width: 100%;
                height: calc(100vh - 28px);
                margin: 0 auto;
            }

            .blog-post-modal-content {
                height: 100%;
                border: 0;
                border-radius: 18px;
                overflow: hidden;
                box-shadow: 0 24px 80px rgba(0, 0, 0, .22);
                display: flex;
                flex-direction: column;
                background: #fff;
            }

            .blog-post-modal-header {
                flex: 0 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: 18px 22px;
                background: #fff;
                border-bottom: 1px solid #eef1f5;
            }

            .blog-post-modal-body {
                flex: 1 1 auto;
                overflow-y: auto;
                padding: 22px;
                background: #eef4fb;
            }

            .blog-post-modal-footer {
                flex: 0 0 auto;
                display: flex;
                justify-content: flex-end;
                gap: .75rem;
                padding: 14px 22px;
                background: #fff;
                border-top: 1px solid #eef1f5;
            }

            .blog-editor-shell {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 390px;
                gap: 20px;
                align-items: start;
            }

            .blog-editor-main,
            .blog-editor-sidebar {
                min-width: 0;
            }

            .blog-editor-sidebar {
                position: sticky;
                top: 0;
                align-self: start;
            }

            .blog-editor-card {
                border: 0;
                border-radius: 16px;
                box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
                overflow: hidden;
            }

            .blog-editor-card .card-header {
                background: #fff;
                border-bottom: 1px solid #f1f3f7;
                padding: 16px 20px;
                color: #2f3a4a;
            }

            .blog-editor-card .card-body {
                padding: 20px;
            }

            .blog-title-input {
                font-size: 1.15rem;
                font-weight: 600;
            }

            .blog-editor-box {
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid #dfe5ee;
            }

            .ck-editor__editable_inline {
                min-height: 520px;
            }

            .ck.ck-editor__main>.ck-editor__editable {
                border-left: 0 !important;
                border-right: 0 !important;
                border-bottom: 0 !important;
            }

            .blog-featured-preview {
                height: 190px;
                object-fit: cover;
            }

            .blog-featured-placeholder {
                height: 190px;
                border: 1px dashed #c9d3df;
                border-radius: 12px;
                background: #f5f8fc;
                color: #65758b;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
            }

            .blog-stat-box {
                border: 1px solid #edf1f6;
                border-radius: 12px;
                padding: .75rem .5rem;
                background: #f8fafc;
            }

            .blog-stat-box small {
                color: #64748b;
            }

            .seo-check-list {
                max-height: 240px;
                overflow-y: auto;
                padding-right: 4px;
            }

            .blog-preview {
                min-height: 520px;
                padding: 2rem;
                border: 1px solid #dfe5ee;
                border-radius: 12px;
                background: #fff;
            }

            .blog-preview h1,
            .blog-preview h2,
            .blog-preview h3 {
                margin-top: 1.25rem;
                margin-bottom: .75rem;
                font-weight: 700;
            }

            .blog-preview p {
                line-height: 1.8;
                margin-bottom: 1rem;
            }

            .blog-preview img {
                max-width: 100%;
                height: auto;
                border-radius: .5rem;
            }

            .blog-preview blockquote {
                border-left: 4px solid #ddd;
                padding-left: 1rem;
                color: #666;
            }

            .blog-preview table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 1rem;
            }

            .blog-preview table td,
            .blog-preview table th {
                border: 1px solid #ddd;
                padding: .5rem;
            }

            @media (max-width: 1199px) {
                .blog-editor-shell {
                    grid-template-columns: 1fr;
                }

                .blog-editor-sidebar {
                    position: static;
                }
            }

            @media (max-width: 767px) {
                .blog-post-modal-wrapper {
                    padding: 0;
                }

                .blog-post-modal-dialog {
                    height: 100vh;
                }

                .blog-post-modal-content {
                    border-radius: 0;
                }

                .blog-post-modal-body {
                    padding: 14px;
                }

                .blog-post-modal-footer {
                    justify-content: stretch;
                }

                .blog-post-modal-footer .btn {
                    flex: 1;
                }
            }
        </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

    <script>
        let blogEditor = null;
        let blogEditorSyncTimer = null;

        function initBlogEditor(content = '') {
            const editorElement = document.querySelector('#blog-content-editor');

            if (!editorElement) {
                return;
            }

            if (blogEditor) {
                blogEditor.destroy()
                    .then(() => {
                        blogEditor = null;
                        createBlogEditor(editorElement, content);
                    })
                    .catch(() => {
                        blogEditor = null;
                        createBlogEditor(editorElement, content);
                    });

                return;
            }

            createBlogEditor(editorElement, content);
        }

        function createBlogEditor(editorElement, content = '') {
            ClassicEditor
                .create(editorElement, {
                    toolbar: [
                        'heading',
                        '|',
                        'bold',
                        'italic',
                        'link',
                        'bulletedList',
                        'numberedList',
                        'blockQuote',
                        '|',
                        'insertTable',
                        'mediaEmbed',
                        '|',
                        'undo',
                        'redo'
                    ],
                    table: {
                        contentToolbar: [
                            'tableColumn',
                            'tableRow',
                            'mergeTableCells'
                        ]
                    },
                    mediaEmbed: {
                        previewsInData: true
                    }
                })
                .then(editor => {
                    blogEditor = editor;

                    editor.setData(content || '');

                    editor.model.document.on('change:data', () => {
                        clearTimeout(blogEditorSyncTimer);

                        blogEditorSyncTimer = setTimeout(() => {
                            @this.set('content', editor.getData());
                        }, 500);
                    });
                })
                .catch(error => {
                    console.error('CKEditor failed:', error);
                });
        }

        function destroyBlogEditor() {
            if (!blogEditor) {
                return;
            }

            blogEditor.destroy()
                .then(() => {
                    blogEditor = null;
                })
                .catch(() => {
                    blogEditor = null;
                });
        }

        document.addEventListener('livewire:init', function () {
            window.addEventListener('open-blog-post-modal', event => {
                setTimeout(() => {
                    initBlogEditor(event.detail.content || @this.get('content') || '');
                }, 300);
            });

            window.addEventListener('refresh-blog-editor', event => {
                destroyBlogEditor();

                setTimeout(() => {
                    initBlogEditor(event.detail.content || @this.get('content') || '');
                }, 300);
            });

            window.addEventListener('close-blog-post-modal', () => {
                destroyBlogEditor();
            });

            document.addEventListener('submit', function (event) {
                if (event.target && event.target.id === 'blog-post-form' && blogEditor) {
                    @this.set('content', blogEditor.getData());
                }
            });
        });
    </script>
@endpush
