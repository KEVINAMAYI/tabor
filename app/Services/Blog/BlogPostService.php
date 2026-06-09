<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class BlogPostService
{
    public function create(array $data, ?UploadedFile $featuredImage = null): BlogPost
    {
        try {
            return DB::transaction(function () use ($data, $featuredImage) {
                $imagePath = $featuredImage
                    ? $featuredImage->store('blogs/featured', 'public')
                    : null;

                return BlogPost::create([
                    'blog_category_id' => $data['blog_category_id'] ?? null,
                    'author_id' => auth()->id(),
                    'title' => $data['title'],
                    'slug' => $data['slug'],
                    'excerpt' => $data['excerpt'] ?? null,
                    'content' => !empty($data['content']) ? Purifier::clean($data['content']) : null,
                    'featured_image' => $imagePath,
                    'status' => $data['status'] ?? 'draft',
                    'published_at' => ($data['status'] ?? 'draft') === 'published'
                        ? ($data['published_at'] ?? now())
                        : null,
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to create blog post', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function update(BlogPost $post, array $data, ?UploadedFile $featuredImage = null): BlogPost
    {
        try {
            return DB::transaction(function () use ($post, $data, $featuredImage) {
                $imagePath = $post->featured_image;

                if ($featuredImage) {
                    if ($post->featured_image && Storage::disk('public')->exists($post->featured_image)) {
                        Storage::disk('public')->delete($post->featured_image);
                    }

                    $imagePath = $featuredImage->store('blogs/featured', 'public');
                }

                $post->update([
                    'blog_category_id' => $data['blog_category_id'] ?? null,
                    'title' => $data['title'],
                    'slug' => $post->title !== $data['title']
                        ? $this->generateUniqueSlug($data['title'], $post->id)
                        : $post->slug,
                    'excerpt' => $data['excerpt'] ?? null,
                    'content' => !empty($data['content']) ? Purifier::clean($data['content']) : null,
                    'featured_image' => $imagePath,
                    'status' => $data['status'] ?? 'draft',
                    'published_at' => ($data['status'] ?? 'draft') === 'published'
                        ? ($data['published_at'] ?? $post->published_at ?? now())
                        : null,
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                ]);

                return $post->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('Failed to update blog post', [
                'post_id' => $post->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function delete(BlogPost $post): void
    {
        try {
            DB::transaction(function () use ($post) {
                $post->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Failed to delete blog post', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function uploadContentImage(UploadedFile $image): string
    {
        $path = $image->store('blogs/content', 'public');

        return asset('storage/' . $path);
    }

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $count = 1;

        while (
            BlogPost::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
