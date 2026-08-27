<?php

namespace Tests\Unit;

use App\Helpers\ImageCompressor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageCompressorTest extends TestCase
{
    public function test_image_compressor_compresses_photo_to_webp()
    {
        Storage::fake('public');

        // Create a dummy 2000x2000 JPEG image using GD
        $img = imagecreatetruecolor(2000, 2000);
        $blue = imagecolorallocate($img, 0, 136, 204);
        imagefill($img, 0, 0, $blue);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_img_') . '.jpg';
        imagejpeg($img, $tempPath, 100);
        imagedestroy($img);

        $file = new UploadedFile(
            $tempPath,
            'photo.jpg',
            'image/jpeg',
            null,
            true
        );

        $storedPath = ImageCompressor::compressAndStore($file, 'test_photos', 'public', 1200, 80);

        // Verify stored file has .webp extension
        $this->assertStringEndsWith('.webp', $storedPath);

        // Verify file exists on public disk
        Storage::disk('public')->assertExists($storedPath);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }
}
