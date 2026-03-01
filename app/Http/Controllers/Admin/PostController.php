<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Models\Post;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $posts = Post::query()
            ->with(['author:id,name,email,role', 'category:id,name,slug', 'coverMedia'])
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status'))
            )
            ->latest('created_at')
            ->paginate((int) $request->integer('per_page', 15));

        return response()->json($posts);
    }

    public function featured(): JsonResponse
    {
        $posts = Post::query()
            ->with(['author:id,name,email,role', 'category:id,name,slug', 'coverMedia'])
            ->where('is_featured', true)
            ->orderByRaw('featured_order is null')
            ->orderBy('featured_order')
            ->latest('updated_at')
            ->get();

        return response()->json([
            'data' => $posts,
        ]);
    }

    public function reorderFeatured(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_ids' => ['required', 'array', 'min:1'],
            'post_ids.*' => ['integer', 'distinct', 'exists:posts,id'],
        ]);

        $postIds = array_values($validated['post_ids']);

        $featuredIds = Post::query()
            ->whereIn('id', $postIds)
            ->where('is_featured', true)
            ->pluck('id')
            ->all();

        if (count($featuredIds) !== count($postIds)) {
            return response()->json([
                'message' => 'A reordenação aceita apenas notícias atualmente em destaque.',
            ], 422);
        }

        foreach ($postIds as $index => $postId) {
            Post::query()->where('id', $postId)->update([
                'featured_order' => $index + 1,
            ]);
        }

        $orderedPosts = Post::query()
            ->with(['author:id,name,email,role', 'category:id,name,slug', 'coverMedia'])
            ->whereIn('id', $postIds)
            ->orderByRaw('featured_order is null')
            ->orderBy('featured_order')
            ->get();

        return response()->json([
            'message' => 'Ordem dos destaques atualizada com sucesso.',
            'data' => $orderedPosts,
        ]);
    }

    public function feature(Request $request, int $post): JsonResponse
    {
        $postModel = Post::query()->findOrFail($post);

        $nextFeaturedOrder = ((int) Post::query()->where('is_featured', true)->max('featured_order')) + 1;

        $postModel->update([
            'is_featured' => true,
            'featured_order' => $postModel->featured_order ?? $nextFeaturedOrder,
        ]);

        $postModel = $postModel->fresh();

        $this->auditLog->record(
            $request,
            'post.featured',
            $postModel,
            metadata: ['title' => $postModel->title],
            newValues: ['is_featured' => true, 'featured_order' => $postModel->featured_order],
        );

        return response()->json([
            'message' => 'Notícia definida como destaque.',
            'data' => $postModel,
        ]);
    }

    public function unfeature(Request $request, int $post): JsonResponse
    {
        $postModel = Post::query()->findOrFail($post);

        $postModel->update([
            'is_featured' => false,
            'featured_order' => null,
        ]);

        $postModel = $postModel->fresh();

        $this->auditLog->record(
            $request,
            'post.unfeatured',
            $postModel,
            metadata: ['title' => $postModel->title],
            newValues: ['is_featured' => false, 'featured_order' => null],
        );

        return response()->json([
            'message' => 'Destaque removido com sucesso.',
            'data' => $postModel,
        ]);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $status = $data['status'] ?? 'draft';

        $post = Post::create([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'slug' => $this->resolveUniqueSlug($data['slug'] ?? $data['title']),
            'content' => $data['content'],
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
            'scheduled_at' => $status === 'scheduled' ? ($data['scheduled_at'] ?? null) : null,
            'author_id' => $request->user()->id,
            'category_id' => $data['category_id'] ?? null,
            'cover_media_id' => $data['cover_media_id'] ?? null,
        ]);

        $this->auditLog->record(
            $request,
            'post.created',
            $post,
            metadata: ['title' => $post->title, 'status' => $post->status],
            newValues: $post->toArray(),
        );

        return response()->json([
            'message' => 'Notícia criada com sucesso.',
            'data' => $post->load(['author:id,name,email,role', 'category:id,name,slug', 'coverMedia']),
        ], 201);
    }

    public function show(int $post): JsonResponse
    {
        $postModel = Post::query()
            ->with(['author:id,name,email,role', 'category:id,name,slug', 'coverMedia'])
            ->findOrFail($post);

        return response()->json([
            'data' => $postModel,
        ]);
    }

    public function update(UpdatePostRequest $request, int $post): JsonResponse
    {
        $postModel = Post::query()->findOrFail($post);
        $oldValues = $postModel->only([
            'title',
            'subtitle',
            'slug',
            'status',
            'published_at',
            'scheduled_at',
            'category_id',
            'cover_media_id',
        ]);
        $data = $request->validated();
        $status = $data['status'] ?? $postModel->status;

        $postModel->update([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'slug' => $this->resolveUniqueSlug($data['slug'] ?? $data['title'], $postModel->id),
            'content' => $data['content'],
            'status' => $status,
            'published_at' => $status === 'published' ? ($postModel->published_at ?? now()) : null,
            'scheduled_at' => $status === 'scheduled' ? ($data['scheduled_at'] ?? null) : null,
            'category_id' => $data['category_id'] ?? null,
            'cover_media_id' => $data['cover_media_id'] ?? null,
        ]);

        $postModel = $postModel->fresh();

        $this->auditLog->record(
            $request,
            'post.updated',
            $postModel,
            metadata: ['title' => $postModel->title, 'status' => $postModel->status],
            oldValues: $oldValues,
            newValues: $postModel->only([
                'title',
                'subtitle',
                'slug',
                'status',
                'published_at',
                'scheduled_at',
                'category_id',
                'cover_media_id',
            ]),
        );

        return response()->json([
            'message' => 'Notícia atualizada com sucesso.',
            'data' => $postModel->load(['author:id,name,email,role', 'category:id,name,slug', 'coverMedia']),
        ]);
    }

    public function destroy(Request $request, int $post): JsonResponse
    {
        $postModel = Post::query()->findOrFail($post);
        $oldValues = $postModel->only([
            'title',
            'subtitle',
            'slug',
            'status',
            'published_at',
            'scheduled_at',
            'category_id',
            'cover_media_id',
        ]);

        $postModel->delete();

        $this->auditLog->record(
            $request,
            'post.deleted',
            $postModel,
            metadata: ['title' => $oldValues['title'] ?? null],
            oldValues: $oldValues,
        );

        return response()->json([
            'message' => 'Notícia removida com sucesso.',
        ]);
    }

    public function publish(Request $request, int $post): JsonResponse
    {
        $postModel = Post::query()->findOrFail($post);
        $oldValues = $postModel->only(['status', 'published_at', 'scheduled_at']);

        $postModel->update([
            'status' => 'published',
            'published_at' => now(),
            'scheduled_at' => null,
        ]);

        $postModel = $postModel->fresh();

        $this->auditLog->record(
            $request,
            'post.published',
            $postModel,
            metadata: ['title' => $postModel->title],
            oldValues: $oldValues,
            newValues: $postModel->only(['status', 'published_at', 'scheduled_at']),
        );

        return response()->json([
            'message' => 'Notícia publicada com sucesso.',
            'data' => $postModel,
        ]);
    }

    public function schedule(Request $request, int $post): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $postModel = Post::query()->findOrFail($post);
        $oldValues = $postModel->only(['status', 'published_at', 'scheduled_at']);

        $postModel->update([
            'status' => 'scheduled',
            'scheduled_at' => $validated['scheduled_at'],
            'published_at' => null,
        ]);

        $postModel = $postModel->fresh();

        $this->auditLog->record(
            $request,
            'post.scheduled',
            $postModel,
            metadata: ['title' => $postModel->title],
            oldValues: $oldValues,
            newValues: $postModel->only(['status', 'published_at', 'scheduled_at']),
        );

        return response()->json([
            'message' => 'Notícia agendada com sucesso.',
            'data' => $postModel,
        ]);
    }

    private function resolveUniqueSlug(string $slugSource, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slugSource);

        if ($baseSlug === '') {
            $baseSlug = 'noticia';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            Post::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
