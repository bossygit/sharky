<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Domain\Models\Media;
use Illuminate\Support\Facades\Storage;

class ImageVariantService
{
    /** @var list<int> */
    public const WIDTHS = [400, 800];

    public function variantRelativePath(string $path, int $width): string
    {
        $dir = dirname($path);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $prefix = $dir === '.' ? '' : $dir.'/';

        return $prefix.$name.'_'.$width.'.'.$ext;
    }

    public function generate(Media $media): void
    {
        if ($media->disk !== 'public' || $media->path === null || $media->path === '') {
            return;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($media->path)) {
            return;
        }

        $src = @imagecreatefromstring((string) $disk->get($media->path));
        if ($src === false) {
            return;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);
        $ext = strtolower((string) pathinfo($media->path, PATHINFO_EXTENSION));

        foreach (self::WIDTHS as $width) {
            if ($origW <= $width) {
                continue;
            }

            $height = max(1, (int) round($origH * ($width / $origW)));
            $dest = imagecreatetruecolor($width, $height);
            if ($ext === 'png') {
                imagealphablending($dest, false);
                imagesavealpha($dest, true);
            }
            imagecopyresampled($dest, $src, 0, 0, 0, 0, $width, $height, $origW, $origH);

            ob_start();
            match ($ext) {
                'png' => imagepng($dest, null, 6),
                'webp' => function_exists('imagewebp') ? imagewebp($dest, null, 80) : imagejpeg($dest, null, 82),
                default => imagejpeg($dest, null, 82),
            };
            $out = (string) ob_get_clean();
            imagedestroy($dest);

            $disk->put($this->variantRelativePath($media->path, $width), $out);
        }

        imagedestroy($src);
    }
}
