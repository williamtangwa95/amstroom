<?php

namespace Tests\Unit;

use App\Helpers\ImageCompressor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageCompressorTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_storage_fallback_route_serves_image_file()
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/test_avatar.webp', 'fake_webp_data');

        $user = User::create([
            'name'     => 'Test User',
            'email'    => 'test_user_img@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
            'status'   => 'active',
        ]);

        $response = $this->actingAs($user)->get('/media/avatars/test_avatar.webp');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'image/webp');
    }
}
