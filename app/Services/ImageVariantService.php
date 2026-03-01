<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageVariantService
{
    private const DISK = 'public';

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

        $thumb = $this->resizeAndCrop($image, 320, 180);
        $card = $this->resizeAndCrop($image, 640, 360);
        $hero = $this->resizeAndCrop($image, 1280, 720);
        $full = $this->resizeToMaxWidth($image, 1920);

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
            'disk' => self::DISK,
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

        Storage::disk(self::DISK)->putFileAs($baseDir, $file, $filename);

        return $path;
    }

    private function resizeAndCrop($source, int $targetWidth, int $targetHeight)
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $targetRatio);
            $srcX = (int) floor(($sourceWidth - $cropWidth) / 2);
            $srcY = 0;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $targetRatio);
            $srcX = 0;
            $srcY = (int) floor(($sourceHeight - $cropHeight) / 2);
        }

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            $srcX,
            $srcY,
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight
        );

        return $canvas;
    }

    private function resizeToMaxWidth($source, int $maxWidth)
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth <= $maxWidth) {
            $copy = imagecreatetruecolor($sourceWidth, $sourceHeight);
            imagecopy($copy, $source, 0, 0, 0, 0, $sourceWidth, $sourceHeight);

            return $copy;
        }

        $newWidth = $maxWidth;
        $newHeight = (int) round(($sourceHeight / $sourceWidth) * $newWidth);

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

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

        Storage::disk(self::DISK)->put($path, $binary);
    }
}
