<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Blog\BlogPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlogContentImageUploadController extends Controller
{
    public function __invoke(Request $request, BlogPostService $service): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:4096'],
        ]);

        try {
            $url = $service->uploadContentImage($request->file('file'));

            return response()->json([
                'location' => $url,
            ]);
        } catch (\Throwable $e) {
            Log::error('Blog content image upload failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Image upload failed.',
            ], 500);
        }
    }
}
