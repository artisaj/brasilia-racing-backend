<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

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

        $commentModel = $commentModel->fresh();

        $this->auditLog->record(
            $request,
            'comment.approved',
            $commentModel,
            metadata: ['post_id' => $commentModel->post_id],
            oldValues: ['status' => 'pending'],
            newValues: $commentModel->only(['status', 'reviewed_by', 'reviewed_at']),
        );

        return response()->json([
            'message' => 'Comentário aprovado com sucesso.',
            'data' => $commentModel->load(['post:id,title,slug', 'reviewer:id,name,email']),
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

        $commentModel = $commentModel->fresh();

        $this->auditLog->record(
            $request,
            'comment.rejected',
            $commentModel,
            metadata: ['post_id' => $commentModel->post_id],
            oldValues: ['status' => 'pending'],
            newValues: $commentModel->only(['status', 'reviewed_by', 'reviewed_at']),
        );

        return response()->json([
            'message' => 'Comentário rejeitado com sucesso.',
            'data' => $commentModel->load(['post:id,title,slug', 'reviewer:id,name,email']),
        ]);
    }

    public function destroy(Request $request, int $comment): JsonResponse
    {
        $commentModel = Comment::query()->findOrFail($comment);
        $oldValues = $commentModel->only(['post_id', 'author_name', 'author_email', 'status', 'content']);
        $commentModel->delete();

        $this->auditLog->record(
            $request,
            'comment.deleted',
            $commentModel,
            metadata: ['post_id' => $oldValues['post_id'] ?? null],
            oldValues: $oldValues,
        );

        return response()->json([
            'message' => 'Comentário removido com sucesso.',
        ]);
    }
}
