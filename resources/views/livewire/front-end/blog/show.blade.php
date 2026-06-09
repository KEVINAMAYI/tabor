<?php

use App\Models\BlogPost;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app.frontend')] class extends Component {
    public BlogPost $post;

    public function mount(string $uuid): void
    {
        $this->post = BlogPost::query()
            ->with(['category', 'author'])
            ->published()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function title(): string
    {
        return $this->post->meta_title ?: $this->post->title;
    }

    public function with(): array
    {
        return [
            'relatedPosts' => BlogPost::query()
                ->with('category')
                ->published()
                ->where('id', '!=', $this->post->id)
                ->when($this->post->blog_category_id, function ($query) {
                    $query->where('blog_category_id', $this->post->blog_category_id);
                })
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ];
    }
};
?>

<div>
    <section class="py-5 bg-light border-bottom">
        <div class="container">
            <div class="mx-auto" style="max-width: 900px;">
                <div class="mb-3">
                    @if ($post->category)
                        <span class="badge bg-primary">{{ $post->category->name }}</span>
                    @endif

                    <span class="text-muted small ms-2">
                        {{ $post->published_at?->format('d M Y') }}
                    </span>
                </div>

                <h1 class="fw-bold mb-3">{{ $post->title }}</h1>

                @if ($post->excerpt)
                    <p class="lead text-muted mb-0">{{ $post->excerpt }}</p>
                @endif
            </div>
        </div>
    </section>

    @if ($post->featured_image)
        <section class="py-4">
            <div class="container">
                <div class="mx-auto" style="max-width: 1000px;">
                    <img src="{{ asset('storage/' . $post->featured_image) }}" class="img-fluid rounded shadow-sm w-100"
                        style="max-height: 520px; object-fit: cover;" alt="{{ $post->title }}">
                </div>
            </div>
        </section>
    @endif

    <section class="py-5">
        <div class="container">
            <div class="mx-auto blog-content" style="max-width: 850px;">
                {!! $post->content !!}
            </div>
        </div>
    </section>

    @if ($relatedPosts->count())
        <section class="py-5 bg-light border-top">
            <div class="container">
                <h3 class="fw-bold mb-4">Related Posts</h3>

                <div class="row g-4">
                    @foreach ($relatedPosts as $related)
                        <div class="col-md-4">
                            <article class="card h-100 border-0 shadow-sm">
                                @if ($related->featured_image)
                                    <a href="{{ route('front-end.blog.show', $related->uuid) }}">
                                        <img src="{{ asset('storage/' . $related->featured_image) }}"
                                            class="card-img-top" style="height: 180px; object-fit: cover;"
                                            alt="{{ $related->title }}">
                                    </a>
                                @endif

                                <div class="card-body">
                                    <div class="mb-2">
                                        @if ($related->category)
                                            <span class="badge bg-primary-subtle text-primary">
                                                {{ $related->category->name }}
                                            </span>
                                        @endif
                                    </div>

                                    <h6 class="fw-bold">
                                        <a href="{{ route('front-end.blog.show', $related->uuid) }}"
                                            class="text-decoration-none text-dark">
                                            {{ $related->title }}
                                        </a>
                                    </h6>

                                    <p class="text-muted small mb-0">
                                        {{ str($related->excerpt ?: strip_tags($related->content))->limit(100) }}
                                    </p>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <style>
        .blog-content {
            font-size: 1.05rem;
            line-height: 1.8;
        }

        .blog-content img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin: 1.5rem 0;
        }

        .blog-content p {
            margin-bottom: 1.2rem;
        }

        .blog-content h2,
        .blog-content h3,
        .blog-content h4 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .blog-content ul,
        .blog-content ol {
            padding-left: 1.5rem;
            margin-bottom: 1.2rem;
        }

        .blog-content table {
            width: 100%;
            margin-bottom: 1.5rem;
            border-collapse: collapse;
        }

        .blog-content table th,
        .blog-content table td {
            border: 1px solid #dee2e6;
            padding: .75rem;
        }
    </style>
</div>
