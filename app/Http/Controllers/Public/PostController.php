<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function featured(): JsonResponse
    {
        $featuredPosts = Post::query()
            ->with(['category:id,name,slug', 'coverMedia'])
            ->published()
            ->where('is_featured', true)
            ->orderByRaw('featured_order is null')
            ->orderBy('featured_order')
            ->latest('updated_at')
            ->limit(8)
            ->get();

        return response()->json([
            'data' => $featuredPosts,
        ]);
    }

    public function index(): JsonResponse
    {
        $posts = Post::query()
            ->with(['category:id,name,slug', 'coverMedia'])
            ->published()
            ->latest('published_at')
            ->paginate(12);

        return response()->json($posts);
    }

    public function show(string $slug): JsonResponse
    {
        $post = Post::query()
            ->with(['category:id,name,slug', 'coverMedia'])
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => $post,
        ]);
    }
}
