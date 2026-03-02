<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): JsonResponse
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'status', 'created_at'])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $users,
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $defaultPassword = (string) config('admin.password', 'admin123456');

        $user = User::query()->create([
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'password' => Hash::make($defaultPassword),
            'role' => (string) $data['role'],
            'status' => (string) $data['status'],
        ]);

        $this->auditLog->record(
            $request,
            'user.created',
            $user,
            newValues: $user->only(['name', 'email', 'role', 'status'])
        );

        return response()->json([
            'message' => 'Usuário criado com sucesso com senha padrão do sistema.',
            'data' => $user->only(['id', 'name', 'email', 'role', 'status', 'created_at']),
        ], Response::HTTP_CREATED);
    }

    public function updateStatus(UpdateUserStatusRequest $request, int $user): JsonResponse
    {
        $userModel = User::query()->findOrFail($user);

        if ((int) $request->user()->id === (int) $userModel->id) {
            return response()->json([
                'message' => 'Não é permitido alterar o próprio status.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $oldStatus = $userModel->status;

        $userModel->update([
            'status' => (string) $request->string('status'),
        ]);

        $userModel = $userModel->fresh();

        $this->auditLog->record(
            $request,
            'user.status.updated',
            $userModel,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $userModel->status]
        );

        return response()->json([
            'message' => 'Status do usuário atualizado com sucesso.',
            'data' => $userModel->only(['id', 'name', 'email', 'role', 'status', 'created_at']),
        ]);
    }

    public function update(UpdateUserRequest $request, int $user): JsonResponse
    {
        $userModel = User::query()->findOrFail($user);

        if ((int) $request->user()->id === (int) $userModel->id && (string) $request->string('role') !== 'admin') {
            return response()->json([
                'message' => 'Você não pode remover o próprio perfil de administrador.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $oldValues = $userModel->only(['name', 'email', 'role', 'status']);
        $data = $request->validated();

        $userModel->update([
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'role' => (string) $data['role'],
            'status' => (string) $data['status'],
        ]);

        $userModel = $userModel->fresh();

        $this->auditLog->record(
            $request,
            'user.updated',
            $userModel,
            oldValues: $oldValues,
            newValues: $userModel->only(['name', 'email', 'role', 'status'])
        );

        return response()->json([
            'message' => 'Usuário atualizado com sucesso.',
            'data' => $userModel->only(['id', 'name', 'email', 'role', 'status', 'created_at']),
        ]);
    }

    public function destroy(Request $request, int $user): JsonResponse
    {
        $userModel = User::query()->findOrFail($user);

        if ((int) $request->user()->id === (int) $userModel->id) {
            return response()->json([
                'message' => 'Você não pode excluir o próprio usuário.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $oldValues = $userModel->only(['name', 'email', 'role', 'status']);
        $userModel->delete();

        $this->auditLog->record(
            $request,
            'user.deleted',
            $userModel,
            oldValues: $oldValues
        );

        return response()->json([
            'message' => 'Usuário excluído com sucesso.',
        ]);
    }

}
