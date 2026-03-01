<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSponsorRequest;
use App\Http\Requests\Admin\UpdateSponsorRequest;
use App\Models\Sponsor;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SponsorController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): JsonResponse
    {
        $sponsors = Sponsor::query()
            ->with('image')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $sponsors,
        ]);
    }

    public function store(StoreSponsorRequest $request): JsonResponse
    {
        $data = $request->validated();

        $sponsor = Sponsor::create([
            'name' => $data['name'],
            'destination_url' => $data['destination_url'],
            'image_media_id' => $data['image_media_id'],
            'placement' => $data['placement'] ?? 'footer',
            'status' => $data['status'] ?? 'active',
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        $this->auditLog->record(
            $request,
            'sponsor.created',
            $sponsor,
            metadata: ['name' => $sponsor->name, 'placement' => $sponsor->placement],
            newValues: $sponsor->toArray(),
        );

        return response()->json([
            'message' => 'Patrocinador criado com sucesso.',
            'data' => $sponsor->load('image'),
        ], Response::HTTP_CREATED);
    }

    public function show(int $sponsor): JsonResponse
    {
        $sponsorModel = Sponsor::query()->with('image')->findOrFail($sponsor);

        return response()->json([
            'data' => $sponsorModel,
        ]);
    }

    public function update(UpdateSponsorRequest $request, int $sponsor): JsonResponse
    {
        $sponsorModel = Sponsor::query()->findOrFail($sponsor);
        $oldValues = $sponsorModel->only([
            'name',
            'destination_url',
            'image_media_id',
            'placement',
            'status',
            'starts_at',
            'ends_at',
        ]);
        $data = $request->validated();

        $sponsorModel->update([
            'name' => $data['name'],
            'destination_url' => $data['destination_url'],
            'image_media_id' => $data['image_media_id'],
            'placement' => $data['placement'] ?? 'footer',
            'status' => $data['status'] ?? 'active',
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        $sponsorModel = $sponsorModel->fresh();

        $this->auditLog->record(
            $request,
            'sponsor.updated',
            $sponsorModel,
            metadata: ['name' => $sponsorModel->name, 'placement' => $sponsorModel->placement],
            oldValues: $oldValues,
            newValues: $sponsorModel->only([
                'name',
                'destination_url',
                'image_media_id',
                'placement',
                'status',
                'starts_at',
                'ends_at',
            ]),
        );

        return response()->json([
            'message' => 'Patrocinador atualizado com sucesso.',
            'data' => $sponsorModel->load('image'),
        ]);
    }

    public function destroy(Request $request, int $sponsor): JsonResponse
    {
        $sponsorModel = Sponsor::query()->findOrFail($sponsor);
        $oldValues = $sponsorModel->only([
            'name',
            'destination_url',
            'image_media_id',
            'placement',
            'status',
            'starts_at',
            'ends_at',
        ]);
        $sponsorModel->delete();

        $this->auditLog->record(
            $request,
            'sponsor.deleted',
            $sponsorModel,
            metadata: ['name' => $oldValues['name'] ?? null, 'placement' => $oldValues['placement'] ?? null],
            oldValues: $oldValues,
        );

        return response()->json([
            'message' => 'Patrocinador removido com sucesso.',
        ]);
    }
}
