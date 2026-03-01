<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::query()
            ->with('user:id,name,email,role')
            ->when(
                $request->filled('action'),
                fn ($query) => $query->where('action', $request->string('action'))
            )
            ->when(
                $request->filled('user_id'),
                fn ($query) => $query->where('user_id', (int) $request->integer('user_id'))
            )
            ->when(
                $request->filled('request_id'),
                fn ($query) => $query->where('request_id', (string) $request->string('request_id'))
            )
            ->latest('id')
            ->paginate((int) $request->integer('per_page', 30));

        return response()->json($logs);
    }
}
