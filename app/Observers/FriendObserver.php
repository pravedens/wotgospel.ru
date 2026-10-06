<?php

namespace App\Observers;

use App\Models\Friend;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

class FriendObserver
{
    public function created(Friend $friend): void
    {
        $this->optimizeThumbnail($friend);
    }

    public function updated(Friend $friend): void
    {
        $this->optimizeThumbnail($friend);
    }

    public function deleting(Friend $friend): void
    {
        if ($friend->thumbnail) {
            Storage::disk('s3')->delete($friend->thumbnail);
        }
    }

    private function optimizeThumbnail(Friend $friend): void
    {
        if (! $friend->thumbnail || ! $friend->wasChanged('thumbnail')) {
            return;
        }

        // Уже оптимизирован
        if (str_ends_with($friend->thumbnail, '.webp')) {
            return;
        }

        // SVG не трогаем
        if (str_ends_with($friend->thumbnail, '.svg')) {
            return;
        }

        try {
            $contents = Storage::disk('s3')->get($friend->thumbnail);
            if (! $contents) {
                return;
            }

            $manager = new ImageManager(new Driver);
            $image = $manager->read($contents);

            $image->scaleDown(width: 240, height: 240);
            $encoded = $image->toWebp(quality: 85);

            $newPath = 'friends/'.pathinfo($friend->thumbnail, PATHINFO_FILENAME).'.webp';

            Storage::disk('s3')->put($newPath, (string) $encoded, [
                'visibility' => 'public',
                'ContentType' => 'image/webp',
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]);

            if ($friend->thumbnail !== $newPath) {
                Storage::disk('s3')->delete($friend->thumbnail);
            }

            $friend->timestamps = false;
            $friend->forceFill(['thumbnail' => $newPath])->saveQuietly();
            $friend->timestamps = true;

        } catch (\Exception $e) {
            Log::error('Friend thumbnail optimization failed', [
                'friend_id' => $friend->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
