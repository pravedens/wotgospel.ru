<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Friend;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

class ReoptimizeImages extends Command
{
    protected $signature = 'images:reoptimize {--dry-run}';
    protected $description = 'Пересжать все thumbnails в WebP с правильными размерами';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? '🔍 DRY-RUN (изменения не сохраняются)' : '🚀 Оптимизация');
        $this->newLine();

        $this->process(
            Event::class,
            'events/thumbnails',
            896,
            672,
            82,
            $dryRun
        );

        $this->process(
            Post::class,
            'posts/thumbnails',
            800,
            600,
            82,
            $dryRun
        );

        $this->process(
            Friend::class,
            'friends',
            240,
            240,
            85,
            $dryRun
        );

        $this->newLine();
        $this->info('✅ Готово.');

        return self::SUCCESS;
    }

    private function process(
        string $modelClass,
        string $directory,
        int $width,
        int $height,
        int $quality,
        bool $dryRun,
    ): void {
        $this->info("→ {$modelClass} ({$directory}, {$width}×{$height}, q{$quality})");

        $manager = new ImageManager(new Driver);
        $processed = 0;
        $skipped = 0;
        $failed = 0;
        $savedBytes = 0;

        $modelClass::query()
            ->whereNotNull('thumbnail')
            ->where('thumbnail', 'not like', 'http%')
            ->chunkById(100, function ($items) use (
                $manager, $directory, $width, $height, $quality,
                $dryRun, &$processed, &$skipped, &$failed, &$savedBytes
            ) {
                foreach ($items as $model) {
                    $path = $model->thumbnail;

                    // Уже оптимизирован
                    if (str_ends_with($path, '.webp')) {
                        $skipped++;
                        continue;
                    }

                    // Не в целевой папке (например, старое `public/`)
                    if (! str_starts_with($path, $directory.'/')) {
                        $skipped++;
                        continue;
                    }

                    try {
                        $contents = Storage::disk('s3')->get($path);
                        if (! $contents) {
                            $failed++;
                            continue;
                        }

                        $originalSize = strlen($contents);
                        $image = $manager->read($contents);

                        $originalWidth = $image->width();
                        $originalHeight = $image->height();

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

                        $encoded = (string) $image->toWebp(quality: $quality);
                        $newSize = strlen($encoded);

                        $newPath = $directory.'/'.pathinfo($path, PATHINFO_FILENAME).'.webp';

                        if ($dryRun) {
                            $this->line("  [DRY] {$path} ({$originalSize}b) → {$newPath} ({$newSize}b)");
                            $processed++;
                            $savedBytes += max(0, $originalSize - $newSize);
                            continue;
                        }

                        Storage::disk('s3')->put($newPath, $encoded, [
                            'visibility' => 'public',
                            'ContentType' => 'image/webp',
                            'CacheControl' => 'public, max-age=31536000, immutable',
                        ]);

                        if ($newPath !== $path) {
                            Storage::disk('s3')->delete($path);

                            $model->timestamps = false;
                            $model->forceFill(['thumbnail' => $newPath])->saveQuietly();
                            $model->timestamps = true;
                        }

                        $processed++;
                        $savedBytes += max(0, $originalSize - $newSize);

                    } catch (\Exception $e) {
                        $this->warn("  ✗ {$path}: {$e->getMessage()}");
                        $failed++;
                    }
                }
            });

        $this->line("  Обработано: {$processed}, пропущено: {$skipped}, ошибок: {$failed}");
        if ($savedBytes > 0) {
            $this->line("  Экономия: ".round($savedBytes / 1024, 1).' КиБ');
        }
        $this->newLine();
    }
}
