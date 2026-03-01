<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadMediaRequest;
use App\Models\Media;
use App\Services\ImageVariantService;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    public function index(): JsonResponse
    {
        $media = Media::query()
            ->latest('created_at')
            ->paginate(24);

        return response()->json($media);
    }

    public function upload(UploadMediaRequest $request, ImageVariantService $imageVariantService): JsonResponse
    {
        $file = $request->file('file');
        $processed = $imageVariantService->processAndStore($file);

        $media = Media::create([
            'type' => 'image',
            'disk' => $processed['disk'],
            'original_path' => $processed['original_path'],
            'thumb_path' => $processed['thumb_path'],
            'card_path' => $processed['card_path'],
            'hero_path' => $processed['hero_path'],
            'full_path' => $processed['full_path'],
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize() ?: 0,
            'width' => $processed['width'],
            'height' => $processed['height'],
            'uploaded_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Upload realizado com sucesso.',
            'data' => $media,
        ], 201);
    }
}
