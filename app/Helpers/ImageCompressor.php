<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageCompressor
{
    /**
     * Compress and store an uploaded image file as WebP.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $disk
     * @param int $maxWidth
     * @param int $quality (1-100)
     * @return string Relative path of stored compressed webp image
     */
    public static function compressAndStore(
        UploadedFile $file,
        string $directory = 'uploads',
        string $disk = 'public',
        int $maxWidth = 1200,
        int $quality = 80
    ): string {
        $mime = $file->getMimeType() ?? '';

        // If it's not a raster image (e.g. PDF/DOC attachment), fall back to standard store
        if (!str_starts_with($mime, 'image/')) {
            return $file->store($directory, $disk);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        // SVG files are strictly prohibited system-wide
        if ($extension === 'svg' || $mime === 'image/svg+xml') {
            throw new \InvalidArgumentException('SVG uploads are strictly restricted across the system.');
        }

        // Animated GIFs should be preserved directly
        if ($extension === 'gif' || $mime === 'image/gif') {
            return $file->store($directory, $disk);
        }


        try {
            $realPath = $file->getRealPath();
            if (!$realPath || !file_exists($realPath)) {
                return $file->store($directory, $disk);
            }

            $contents = file_get_contents($realPath);
            if (!$contents) {
                return $file->store($directory, $disk);
            }

            $image = @imagecreatefromstring($contents);
            if (!$image) {
                return $file->store($directory, $disk);
            }

            // Fix orientation if EXIF metadata exists (JPEG photos taken on phones)
            if (function_exists('exif_read_data') && in_array($extension, ['jpg', 'jpeg'])) {
                try {
                    $exif = @exif_read_data($realPath);
                    if (!empty($exif['Orientation'])) {
                        switch ($exif['Orientation']) {
                            case 3:
                                $image = imagerotate($image, 180, 0);
                                break;
                            case 6:
                                $image = imagerotate($image, -90, 0);
                                break;
                            case 8:
                                $image = imagerotate($image, 90, 0);
                                break;
                        }
                    }
                } catch (\Throwable $e) {
                    // Ignore EXIF parsing errors
                }
            }

            $width = imagesx($image);
            $height = imagesy($image);

            // Resize if dimensions exceed $maxWidth
            if ($width > 0 && $width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = (int) max(1, round(($height / $width) * $newWidth));

                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

                // Preserve alpha transparency for PNG/WebP
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);

                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resizedImage;
            } else {
                // Ensure alpha transparency settings
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }

            // Output WebP to buffer
            ob_start();
            $success = imagewebp($image, null, $quality);
            $compressedData = ob_get_clean();
            imagedestroy($image);

            if (!$success || empty($compressedData)) {
                return $file->store($directory, $disk);
            }

            // Generate webp filename
            $filename = Str::random(40) . '.webp';
            $path = trim($directory, '/') . '/' . $filename;

            Storage::disk($disk)->put($path, $compressedData);

            return $path;
        } catch (\Throwable $e) {
            // Safe fallback to default file upload handler if GD processing fails
            return $file->store($directory, $disk);
        }
    }
}
