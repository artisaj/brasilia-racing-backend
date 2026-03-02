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

        $thumb = $this->resizeToContainCanvas($image, 320, 180);
        $card = $this->resizeToContainCanvas($image, 640, 360);
        $hero = $this->resizeToContainCanvas($image, 1280, 720);
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

    private function resizeToContainCanvas($source, int $targetWidth, int $targetHeight)
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        $background = imagecolorallocate($canvas, 16, 18, 22);
        imagefill($canvas, 0, 0, $background);

        $scale = min($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
        $newWidth = max(1, (int) round($sourceWidth * $scale));
        $newHeight = max(1, (int) round($sourceHeight * $scale));
        $dstX = (int) floor(($targetWidth - $newWidth) / 2);
        $dstY = (int) floor(($targetHeight - $newHeight) / 2);

        imagecopyresampled(
            $canvas,
            $source,
            $dstX,
            $dstY,
            0,
            0,
            $newWidth,
            $newHeight,
            $sourceWidth,
            $sourceHeight
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

        Storage::disk($this->disk)->put($path, $binary);
    }
}
