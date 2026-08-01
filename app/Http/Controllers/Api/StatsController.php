<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatsController extends Controller
{
    /**
     * Учитываем просмотр поста (только на детальной странице)
     */
    public function trackView(Request $request, $postId)
    {
        try {
            $post = Post::findOrFail($postId);

            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $fingerprint = PostStat::generateFingerprint($ip, $userAgent);
            $today = now()->toDateString();

            // Просто ищем запись по fingerprint
            $stat = PostStat::where('post_id', $postId)
                ->where('fingerprint', $fingerprint)
                ->first();

            DB::transaction(function () use ($post, $ip, $userAgent, $fingerprint, $today, $stat) {
                if (! $stat) {
                    // Новый уникальный посетитель
                    PostStat::create([
                        'post_id' => $post->id,
                        'ip' => $ip,
                        'user_agent' => $userAgent,
                        'fingerprint' => $fingerprint,
                        'viewed' => true,
                        'viewed_at' => now(),
                        'viewed_at_date' => $today,
                    ]);

                    $post->increment('views_count');

                } else {
                    // Посетитель уже был, проверяем дату последнего просмотра
                    $lastViewDate = $stat->viewed_at ? $stat->viewed_at->toDateString() : null;

                    if ($lastViewDate !== $today) {
                        // Новый день - новый просмотр
                        $stat->update([
                            'viewed' => true,
                            'viewed_at' => now(),
                            'viewed_at_date' => $today,
                        ]);

                        $post->increment('views_count');

                    } else {

                    }
                }
            });

            $post->refresh();

            // Получаем актуальный статус лайка
            $updatedStat = PostStat::where('post_id', $postId)
                ->where('fingerprint', $fingerprint)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Просмотр обработан',
                'views_count' => $post->views_count,
                'likes_count' => $post->likes_count,
                'liked' => $updatedStat ? $updatedStat->liked : false,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error tracking view', [
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отслеживании просмотра',
            ], 500);
        }
    }

    /**
     * Переключение лайка
     */
    public function toggleLike(Request $request, $postId)
    {
        try {
            $post = Post::findOrFail($postId);

            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $fingerprint = PostStat::generateFingerprint($ip, $userAgent);

            // Ищем запись по fingerprint
            $stat = PostStat::where('post_id', $postId)
                ->where('fingerprint', $fingerprint)
                ->first();

            DB::transaction(function () use ($post, $ip, $userAgent, $fingerprint, $stat) {
                if (! $stat) {
                    // Новый посетитель ставит лайк
                    PostStat::create([
                        'post_id' => $post->id,
                        'ip' => $ip,
                        'user_agent' => $userAgent,
                        'fingerprint' => $fingerprint,
                        'viewed' => false,
                        'liked' => true,
                        'viewed_at' => null,
                        'viewed_at_date' => null,
                    ]);

                    $post->increment('likes_count');

                } else {
                    // Переключаем лайк
                    $newLikedStatus = ! $stat->liked;

                    if ($newLikedStatus) {
                        $post->increment('likes_count');

                    } else {
                        $post->decrement('likes_count');

                    }

                    $stat->update(['liked' => $newLikedStatus]);
                }
            });

            $post->refresh();

            // Получаем обновленный статус
            $updatedStat = PostStat::where('post_id', $postId)
                ->where('fingerprint', $fingerprint)
                ->first();

            return response()->json([
                'success' => true,
                'message' => $updatedStat->liked ? 'Лайк поставлен' : 'Лайк убран',
                'liked' => $updatedStat->liked,
                'likes_count' => $post->likes_count,
                'views_count' => $post->views_count,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error toggling like', [
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при переключении лайка',
            ], 500);
        }
    }

    /**
     * Получение статистики для нескольких постов
     */
    public function getBulkStats(Request $request)
    {
        $start = microtime(true);

        try {
            $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:posts,id',
            ]);

            $fingerprint = PostStat::generateFingerprint(
                $request->ip(),
                $request->userAgent()
            );

            $cacheKey = 'bulk_stats_'.md5(implode(',', $request->ids).'_'.$fingerprint);

            // ✅ Проверяем кеш
            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {

                $end = microtime(true);
                $time = round(($end - $start) * 1000, 2);

                return response()->json($cachedData);
            }

            // ✅ ОДИН ЗАПРОС через JOIN
            $results = DB::table('posts')
                ->leftJoin('post_stats', function ($join) use ($fingerprint) {
                    $join->on('posts.id', '=', 'post_stats.post_id')
                        ->where('post_stats.fingerprint', '=', $fingerprint);
                })
                ->whereIn('posts.id', $request->ids)
                ->select(
                    'posts.id',
                    'posts.views_count',
                    'posts.likes_count',
                    'post_stats.liked',
                    'post_stats.viewed_at'
                )
                ->get();

            $data = [];
            foreach ($results as $row) {
                $data[$row->id] = [
                    'views_count' => $row->views_count ?? 0,
                    'likes_count' => $row->likes_count ?? 0,
                    'liked' => (bool) ($row->liked ?? false),
                    'viewed_today' => $row->viewed_at && now()->isToday($row->viewed_at),
                ];
            }

            Cache::put($cacheKey, $data, 60);

            $end = microtime(true);
            $time = round(($end - $start) * 1000, 2);

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error('Error getting bulk stats: '.$e->getMessage());

            return response()->json([
                'error' => 'Ошибка получения статистики',
            ], 500);
        }
    }

    /**
     * ⚠️ DEPRECATED: Используйте getBulkStats() вместо этого метода
     * Получить статистику одного поста
     */
    public function getStats($postId)
    {
        try {
            $post = Post::findOrFail($postId);

            $fingerprint = PostStat::generateFingerprint(
                request()->ip(),
                request()->userAgent()
            );

            $stat = PostStat::where('post_id', $postId)
                ->where('fingerprint', $fingerprint)
                ->first();

            return response()->json([
                'views_count' => $post->views_count ?? 0,
                'likes_count' => $post->likes_count ?? 0,
                'liked' => $stat ? $stat->liked : false,
                'viewed_today' => $stat && $stat->viewed_at && $stat->viewed_at->isToday(),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error getting stats', [
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'views_count' => 0,
                'likes_count' => 0,
                'liked' => false,
                'viewed_today' => false,
            ]);
        }
    }
}
