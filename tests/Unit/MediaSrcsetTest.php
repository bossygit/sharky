<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Catalog\Application\ImageVariantService;
use App\Modules\Catalog\Domain\Models\Media;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaSrcsetTest extends TestCase
{
    public function test_srcset_lists_existing_width_variants(): void
    {
        Storage::fake('public');

        $png = imagecreatetruecolor(800, 800);
        $white = imagecolorallocate($png, 255, 255, 255);
        imagefilledrectangle($png, 0, 0, 799, 799, $white);

        ob_start();
        imagejpeg($png);
        $bytes = ob_get_clean();
        imagedestroy($png);

        Storage::disk('public')->put('products/legacy/demo.jpg', $bytes);

        $media = new Media([
            'disk' => 'public',
            'path' => 'products/legacy/demo.jpg',
            'mime_type' => 'image/jpeg',
            'width' => 800,
            'height' => 800,
            'is_available' => true,
        ]);

        $service = new ImageVariantService;
        $service->generate($media);

        $srcset = $media->srcset();

        $this->assertStringContainsString('/storage/products/legacy/demo_400.jpg 400w', $srcset);
        $this->assertStringContainsString('/storage/products/legacy/demo.jpg 800w', $srcset);
        $this->assertTrue(Storage::disk('public')->exists('products/legacy/demo_400.jpg'));
    }
}
