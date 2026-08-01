<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bible;
use App\Models\Event;
use App\Models\Friend;
use App\Models\LiveStream;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Получить все данные для главной страницы одним запросом
     */
    public function index(Request $request)
    {
        try {
            $cacheKey = 'home_page_data_'.md5(json_encode([
                'locale' => app()->getLocale(),
                'user_id' => $request->user()?->id ?? 'guest',
            ]));

            // ✅ Проверяем кеш ВРУЧНУЮ
            $cachedData = Cache::get($cacheKey);

            if ($cachedData) {
                Log::info('✅ Home page cache HIT');

                return response()->json([
                    'success' => true,
                    'data' => $cachedData,
                    'cached' => true,
                ]);
            }

            Log::info('❌ Home page cache MISS - generating data');

            // 1. Рекомендуемые посты (случайные)
            $randomPosts = Post::with([
                'category:id,title,slug',
                'group:id,title,slug',
                'conference:id,title,slug',
            ])
                ->select(
                    'id', 'title', 'slug', 'description', 'content',
                    'thumbnail', 'category_id', 'group_id', 'conference_id',
                    'created_at', 'views_count', 'likes_count'
                )
                ->inRandomOrder()
                ->limit(4)
                ->get();

            // 2. Ближайшие события
            $upcomingEvents = Event::where('is_published', true)
                ->where('show_in_carousel', true)
                ->whereDate('startDate', '>=', now())
                ->orderBy('startDate', 'asc')
                ->orderBy('startTime', 'asc')
                ->limit(6)
                ->get();

            // 3. Друзья
            $friends = Friend::active()
                ->ordered()
                ->get()
                ->map(function ($friend) {
                    return [
                        'id' => $friend->id,
                        'title' => $friend->title,
                        'slug' => $friend->slug,
                        'description' => $friend->description,
                        'thumbnail' => $friend->thumbnail,
                        'thumbnail_url' => $friend->thumbnail_url,
                        'link' => $friend->link,
                        'sort_order' => $friend->sort_order,
                    ];
                });

            // 4. Стих дня
            $verseOfDay = Bible::inRandomOrder()->first();
            $verseData = null;
            if ($verseOfDay) {
                $verseData = [
                    'id' => $verseOfDay->id,
                    'title' => $verseOfDay->title,
                    'description' => strip_tags($verseOfDay->description),
                    'slug' => $verseOfDay->slug,
                ];
            }

            // 5. Текущая трансляция
            $liveStream = LiveStream::current()->first();
            $liveData = null;
            if ($liveStream) {
                $liveData = [
                    'id' => $liveStream->id,
                    'title' => $liveStream->title,
                    'platform' => $liveStream->platform,
                    'embedUrl' => $this->getEmbedUrl($liveStream),
                    'isActive' => $liveStream->is_active,
                    'scheduledStart' => $liveStream->scheduled_start,
                    'scheduledEnd' => $liveStream->scheduled_end,
                ];
            }

            $data = [
                'random_posts' => $randomPosts,
                'upcoming_events' => $upcomingEvents,
                'friends' => $friends,
                'verse_of_day' => $verseData,
                'live_stream' => $liveData,
            ];

            // ✅ Сохраняем в кеш
            Cache::put($cacheKey, $data, 300);
            Log::info('💾 Home page cache SET');

            return response()->json([
                'success' => true,
                'data' => $data,
                'cached' => false,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Home page error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки данных главной страницы',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить embed URL для трансляции
     */
    private function getEmbedUrl($stream)
    {
        $streamId = $stream->stream_id;

        if (! $streamId) {
            return $stream->embed_url;
        }

        $baseUrl = match ($stream->platform) {
            'rutube' => "https://rutube.ru/play/embed/{$streamId}",
            'youtube' => "https://www.youtube.com/embed/{$streamId}",
            'vk' => "https://vk.com/video_ext.php?oid={$streamId}",
            default => $stream->embed_url,
        };

        return $baseUrl.'?autoplay=1';
    }

    /**
     * Очистка кеша главной страницы
     */
    public function clearCache(Request $request)
    {
        try {
            $cacheKey = 'home_page_data_'.md5(json_encode([
                'locale' => app()->getLocale(),
                'user_id' => $request->user()?->id ?? 'guest',
            ]));

            Cache::forget($cacheKey);

            return response()->json([
                'success' => true,
                'message' => 'Кеш главной страницы очищен',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Clear home cache error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка очистки кеша',
            ], 500);
        }
    }
}
