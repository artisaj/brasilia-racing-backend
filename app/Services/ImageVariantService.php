<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageVariantService
{
    private string $disk;

    public function __construct()
    {
        $this->disk = (string) config('filesystems.media_disk', config('filesystems.default', 'public'));
    }

    public function processAndStore(UploadedFile $file): array
    {
        $binary = file_get_contents($file->getRealPath());

        if ($binary === false) {
            abort(422, 'Não foi possível ler o arquivo de imagem.');
        }

        $image = imagecreatefromstring($binary);

        if (! $image) {
            abort(422, 'Formato de imagem inválido ou não suportado.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $uuid = Str::uuid()->toString();
        $baseDir = 'media/'.$uuid;

        $originalPath = $this->storeRawOriginal($file, $baseDir);

        $thumbPath = $baseDir.'/thumb.jpg';
        $cardPath = $baseDir.'/card.jpg';
        $heroPath = $baseDir.'/hero.jpg';
        $fullPath = $baseDir.'/full.jpg';

        $thumb = $this->resizeToOrientationLimit($image, 480, 320, 320, 480);
        $card = $this->resizeToOrientationLimit($image, 900, 660, 660, 900);
        $hero = $this->resizeToOrientationLimit($image, 1920, 680, 680, 1920);
        $full = $this->resizeToOrientationLimit($image, 1600, 1200, 1200, 1600);

        $this->storeJpeg($thumb, $thumbPath, 88);
        $this->storeJpeg($card, $cardPath, 88);
        $this->storeJpeg($hero, $heroPath, 88);
        $this->storeJpeg($full, $fullPath, 90);

        imagedestroy($thumb);
        imagedestroy($card);
        imagedestroy($hero);
        imagedestroy($full);
        imagedestroy($image);

        return [
            'disk' => $this->disk,
            'original_path' => $originalPath,
            'thumb_path' => $thumbPath,
            'card_path' => $cardPath,
            'hero_path' => $heroPath,
            'full_path' => $fullPath,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function storeRawOriginal(UploadedFile $file, string $baseDir): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $filename = 'original.'.$extension;
        $path = $baseDir.'/'.$filename;

        Storage::disk($this->disk)->putFileAs($baseDir, $file, $filename);

        return $path;
    }

    private function resizeToOrientationLimit($source, int $landscapeMaxWidth, int $landscapeMaxHeight, int $portraitMaxWidth, int $portraitMaxHeight)
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        $isLandscape = $sourceWidth >= $sourceHeight;
        $maxWidth = $isLandscape ? $landscapeMaxWidth : $portraitMaxWidth;
        $maxHeight = $isLandscape ? $landscapeMaxHeight : $portraitMaxHeight;

        $scale = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $targetWidth = (int) max(1, round($sourceWidth * $scale));
        $targetHeight = (int) max(1, round($sourceHeight * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        return $canvas;
    }

    private function storeJpeg($resource, string $path, int $quality): void
    {
        ob_start();
        imagejpeg($resource, null, $quality);
        $binary = ob_get_clean();

        if ($binary === false) {
            abort(500, 'Falha ao processar variação de imagem.');
        }

        Storage::disk($this->disk)->put($path, $binary);
    }
}
