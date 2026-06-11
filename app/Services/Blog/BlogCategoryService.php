<?php

namespace App\Services\Blog;

use App\Models\BlogCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BlogCategoryService
{
    public function create(array $data): BlogCategory
    {
        try {
            return DB::transaction(function () use ($data) {
                return BlogCategory::create([
                    'name' => $data['name'],
                    'slug' => $this->generateUniqueSlug($data['name']),
                    'description' => $data['description'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to create blog category', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function update(BlogCategory $category, array $data): BlogCategory
    {
        try {
            return DB::transaction(function () use ($category, $data) {
                $category->update([
                    'name' => $data['name'],
                    'slug' => $category->name !== $data['name']
                        ? $this->generateUniqueSlug($data['name'], $category->id)
                        : $category->slug,
                    'description' => $data['description'] ?? null,
                    'is_active' => $data['is_active'] ?? false,
                ]);

                return $category->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('Failed to update blog category', [
                'category_id' => $category->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function delete(BlogCategory $category): void
    {
        try {
            DB::transaction(function () use ($category) {
                $category->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Failed to delete blog category', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $count = 1;

        while (
            BlogCategory::where('slug', $slug)
                ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
