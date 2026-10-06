<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

class ImageOptimizer
{
    /** Карусель / детальные страницы: 896×672 = 2× от 448×336 */
    public static function optimizeForCarousel(UploadedFile $file): ?string
    {
        return self::optimizeAndStore($file, 'events/thumbnails', 896, 672, 82);
    }

    /** Списки / карточки: 800×600 = 2× от 400×300 */
    public static function optimizeForList(UploadedFile $file): ?string
    {
        return self::optimizeAndStore($file, 'posts/thumbnails', 800, 600, 82);
    }

    /** Логотипы друзей: 240×240 = 2× от 120×120 */
    public static function optimizeForFriends(UploadedFile $file): ?string
    {
        return self::optimizeAndStore($file, 'friends', 240, 240, 85);
    }

    public static function optimizeAndStore(
        UploadedFile $file,
        string $directory,
        int $width = 1200,
        int $height = 800,
        int $quality = 85,
    ): ?string {
        try {
            $manager = new ImageManager(new Driver);
            $image = $manager->read($file->getPathname());

            $originalWidth = $image->width();
            $originalHeight = $image->height();

            // Не увеличиваем маленькие изображения
            if ($originalWidth > $width || $originalHeight > $height) {
                $ratio = $originalWidth / $originalHeight;
                $targetRatio = $width / $height;

                if ($ratio > $targetRatio) {
                    $image->scale(width: $width);
                } else {
                    $image->scale(height: $height);
                }

                $image->crop(width: $width, height: $height);
            }

            $encodedImage = $image->toWebp(quality: $quality);

            $filename = Str::random(40).'.webp';
            $fullPath = $directory.'/'.$filename;

            Storage::disk('s3')->put($fullPath, (string) $encodedImage, [
                'visibility' => 'public',
                'ContentType' => 'image/webp',
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]);

            \Log::info('Image optimized and stored', [
                'original_size' => $file->getSize(),
                'original_dimensions' => "{$originalWidth}×{$originalHeight}",
                'final_path' => $fullPath,
                'final_size' => strlen((string) $encodedImage),
            ]);

            return $fullPath;

        } catch (\Exception $e) {
            \Log::error('Image optimization failed: '.$e->getMessage(), [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ]);

            return null;
        }
    }
}
