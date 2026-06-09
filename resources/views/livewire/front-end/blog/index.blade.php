<?php

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app.frontend')] #[Title('Blogs')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $category = '';

    public function with(): array
    {
        return [
            'posts' => BlogPost::query()
                ->with('category')
                ->published()
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('title', 'like', '%' . $this->search . '%')
                            ->orWhere('excerpt', 'like', '%' . $this->search . '%')
                            ->orWhere('content', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->category, function ($query) {
                    $query->whereHas('category', fn($q) => $q->where('slug', $this->category));
                })
                ->latest('published_at')
                ->paginate(9),

            'categories' => BlogCategory::query()->where('is_active', true)->whereHas('posts', fn($query) => $query->published())->orderBy('name')->get(),
        ];
    }

    public function filterByCategory(string $slug): void
    {
        $this->category = $slug;
        $this->resetPage();
    }

    public function clearCategory(): void
    {
        $this->category = '';
        $this->resetPage();
    }
};
?>

<div>
    <section class="py-5 bg-light border-bottom">
        <div class="container">
            <div class="text-center">
                <h1 class="fw-bold mb-2">Blogs</h1>
                <p class="text-muted mb-0">
                    News, updates, student stories, and insights from Tabor Training Institute.
                </p>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-3 mb-4 align-items-center">
                <div class="col-md-5">
                    <input type="text" class="form-control" placeholder="Search blog posts..."
                        wire:model.live.debounce.500ms="search">
                </div>

                <div class="col-md-7">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                        <button class="btn btn-sm {{ $category === '' ? 'btn-primary' : 'btn-outline-primary' }}"
                            wire:click="clearCategory">
                            All
                        </button>

                        @foreach ($categories as $item)
                            <button
                                class="btn btn-sm {{ $category === $item->slug ? 'btn-primary' : 'btn-outline-primary' }}"
                                wire:click="filterByCategory('{{ $item->slug }}')">
                                {{ $item->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="row g-4">
                @forelse ($posts as $post)
                    <div class="col-md-6 col-lg-4">
                        <article class="card h-100 border-0 shadow-sm">
                            @if ($post->featured_image)
                                <a href="{{ route('front-end.blog.show', $post->uuid) }}">
                                    <img src="{{ asset('storage/' . $post->featured_image) }}" class="card-img-top"
                                        style="height: 220px; object-fit: cover;" alt="{{ $post->title }}">
                                </a>
                            @endif

                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    @if ($post->category)
                                        <span class="badge bg-primary-subtle text-primary">
                                            {{ $post->category->name }}
                                        </span>
                                    @endif

                                    <span class="text-muted small ms-1">
                                        {{ $post->published_at?->format('d M Y') }}
                                    </span>
                                </div>

                                <h5 class="fw-bold">
                                    <a href="{{ route('front-end.blog.show', $post->uuid) }}"
                                        class="text-decoration-none text-dark">
                                        {{ $post->title }}
                                    </a>
                                </h5>

                                <p class="text-muted">
                                    {{ str($post->excerpt ?: strip_tags($post->content))->limit(130) }}
                                </p>

                                <div class="mt-auto">
                                    <a href="{{ route('front-end.blog.show', $post->uuid) }}"
                                        class="btn btn-outline-primary btn-sm">
                                        Read More
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-light border text-center">
                            No published blog posts found.
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $posts->links() }}
            </div>
        </div>
    </section>
</div>
