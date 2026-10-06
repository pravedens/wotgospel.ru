<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

class PostObserver
{
    public function created(Post $post): void
    {
        $this->clearFiltersCache();
        $this->optimizeThumbnail($post);
    }

    public function updated(Post $post): void
    {
        $this->clearFiltersCache();
        $this->optimizeThumbnail($post);
    }

    public function deleted(Post $post): void
    {
        $this->clearFiltersCache();
        $this->deleteThumbnail($post);
    }

    public function restored(Post $post): void
    {
        $this->clearFiltersCache();
    }

    public function forceDeleted(Post $post): void
    {
        $this->clearFiltersCache();
        $this->deleteThumbnail($post);
    }

    private function deleteThumbnail(Post $post): void
    {
        if ($post->thumbnail && ! str_starts_with($post->thumbnail, 'http')) {
            Storage::disk('s3')->delete($post->thumbnail);
        }
    }

    private function optimizeThumbnail(Post $post): void
    {
        if (! $post->thumbnail || ! $post->wasChanged('thumbnail')) {
            return;
        }

        if (str_ends_with($post->thumbnail, '.webp')) {
            return;
        }

        try {
            $contents = Storage::disk('s3')->get($post->thumbnail);
            if (! $contents) {
                return;
            }

            $manager = new ImageManager(new Driver);
            $image = $manager->read($contents);

            $image->scaleDown(width: 800, height: 600);
            $encoded = $image->toWebp(quality: 82);

            $newPath = 'posts/thumbnails/'.pathinfo($post->thumbnail, PATHINFO_FILENAME).'.webp';

            Storage::disk('s3')->put($newPath, (string) $encoded, [
                'visibility' => 'public',
                'ContentType' => 'image/webp',
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]);

            if ($post->thumbnail !== $newPath) {
                Storage::disk('s3')->delete($post->thumbnail);
            }

            $post->timestamps = false;
            $post->forceFill(['thumbnail' => $newPath])->saveQuietly();
            $post->timestamps = true;

        } catch (\Exception $e) {
            \Log::error('Post thumbnail optimization failed', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function clearFiltersCache(): void
    {
        $locale = app()->getLocale();

        Cache::forget('filters_'.md5('[]').'_'.$locale);
        Cache::forget('filters_'.md5(json_encode(['category_id' => null])).'_'.$locale);
        Cache::forget('filters_'.md5(json_encode(['group_id' => null])).'_'.$locale);
        Cache::forget('filters_'.md5(json_encode(['conference_id' => null])).'_'.$locale);
    }
}
