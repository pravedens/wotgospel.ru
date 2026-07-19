<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizer
{
    /**
     * Оптимизация для карусели
     * Целевой размер: 1200x800 (с обрезкой), WebP качество 85
     */
    public static function optimizeForCarousel(UploadedFile $file): ?string
    {
        return self::optimizeAndStore($file, 'events/thumbnails', 1200, 800, 85);
    }

    /**
     * Оптимизация для списка событий
     * Целевой размер: 600x400, WebP качество 80
     */
    public static function optimizeForList(UploadedFile $file): ?string
    {
        return self::optimizeAndStore($file, 'events/thumbnails', 600, 400, 80);
    }

    /**
     * Универсальный метод оптимизации
     */
    public static function optimizeAndStore(
        UploadedFile $file,
        string $directory,
        int $width = 1200,
        int $height = 800,
        int $quality = 85
    ): ?string {
        try {
            // Создаём менеджер с драйвером Imagick
            $manager = new ImageManager(new Driver());
            
            // Читаем изображение
            $image = $manager->read($file->getPathname());
            
            // Получаем оригинальные размеры
            $originalWidth = $image->width();
            $originalHeight = $image->height();
            
            // ========== 1. МАСШТАБИРОВАНИЕ С ОБРЕЗКОЙ ==========
            if ($originalWidth <= $width && $originalHeight <= $height) {
                // Если изображение меньше целевого размера — не увеличиваем
                $image->scale(width: $originalWidth);
            } else {
                // Масштабируем с сохранением пропорций и обрезаем до точного размера
                $ratio = $originalWidth / $originalHeight;
                $targetRatio = $width / $height;
                
                if ($ratio > $targetRatio) {
                    // Слишком широкое — масштабируем по ширине
                    $image->scale(width: $width);
                    // Обрезаем лишнее по высоте
                    $image->crop(width: $width, height: $height);
                } else {
                    // Слишком высокое — масштабируем по высоте
                    $image->scale(height: $height);
                    // Обрезаем лишнее по ширине
                    $image->crop(width: $width, height: $height);
                }
            }
            
            // ========== 2. КОНВЕРТАЦИЯ В WEBP ==========
            $encodedImage = $image->toWebp(quality: $quality);
            
            // ========== 3. ГЕНЕРАЦИЯ УНИКАЛЬНОГО ИМЕНИ ==========
            $filename = Str::random(40) . '.webp';
            $fullPath = $directory . '/' . $filename;
            
            // ========== 4. СОХРАНЕНИЕ В S3 (Яндекс Облако) ==========
            Storage::disk('s3')->put($fullPath, (string) $encodedImage, [
                'visibility' => 'public',
                'ContentType' => 'image/webp'
            ]);
            
            \Log::info('Image optimized and stored', [
                'original_size' => $file->getSize(),
                'original_width' => $originalWidth,
                'original_height' => $originalHeight,
                'final_path' => $fullPath,
                'final_size' => strlen((string) $encodedImage)
            ]);
            
            return $fullPath;
            
        } catch (\Exception $e) {
            \Log::error('Image optimization failed: ' . $e->getMessage(), [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize()
            ]);
            return null;
        }
    }
}