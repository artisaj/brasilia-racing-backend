<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $comments = Comment::query()
            ->with(['post:id,title,slug', 'reviewer:id,name,email'])
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')),
                fn ($query) => $query->where('status', 'pending')
            )
            ->latest('created_at')
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json($comments);
    }

    public function approve(Request $request, int $comment): JsonResponse
    {
        $commentModel = Comment::query()->findOrFail($comment);

        $commentModel->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Comentário aprovado com sucesso.',
            'data' => $commentModel->fresh()->load(['post:id,title,slug', 'reviewer:id,name,email']),
        ]);
    }

    public function reject(Request $request, int $comment): JsonResponse
    {
        $commentModel = Comment::query()->findOrFail($comment);

        $commentModel->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Comentário rejeitado com sucesso.',
            'data' => $commentModel->fresh()->load(['post:id,title,slug', 'reviewer:id,name,email']),
        ]);
    }

    public function destroy(int $comment): JsonResponse
    {
        $commentModel = Comment::query()->findOrFail($comment);
        $commentModel->delete();

        return response()->json([
            'message' => 'Comentário removido com sucesso.',
        ]);
    }
}
