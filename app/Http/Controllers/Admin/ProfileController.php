<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOwnPasswordRequest;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ]);
    }

    public function updatePassword(UpdateOwnPasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (! Hash::check((string) $data['current_password'], (string) $user->password)) {
            return response()->json([
                'message' => 'A senha atual informada está incorreta.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->update([
            'password' => (string) $data['password'],
        ]);

        $this->auditLog->record(
            $request,
            'profile.password.updated',
            $user,
            metadata: ['user_id' => $user->id]
        );

        return response()->json([
            'message' => 'Senha atualizada com sucesso.',
        ]);
    }
}
