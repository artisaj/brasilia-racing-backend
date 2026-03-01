<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSite\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\RecaptchaService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
{
    public function index(string $slug): JsonResponse
    {
        $post = Post::query()->published()->where('slug', $slug)->firstOrFail();

        $comments = Comment::query()
            ->where('post_id', $post->id)
            ->where('status', 'approved')
            ->latest('created_at')
            ->get();

        return response()->json([
            'data' => $comments,
        ]);
    }

    public function store(string $slug, StoreCommentRequest $request, RecaptchaService $recaptchaService): JsonResponse
    {
        $post = Post::query()->published()->where('slug', $slug)->firstOrFail();
        $validated = $request->validated();

        $isRecaptchaValid = $recaptchaService->validateToken(
            $validated['recaptcha_token'],
            $request->ip()
        );

        if (! $isRecaptchaValid) {
            return response()->json([
                'message' => 'Falha na validação do reCAPTCHA.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $comment = Comment::create([
            'post_id' => $post->id,
            'author_name' => $validated['author_name'],
            'author_email' => $validated['author_email'],
            'body' => $validated['body'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Comentário enviado e aguardando moderação.',
            'data' => $comment,
        ], Response::HTTP_CREATED);
    }
}
