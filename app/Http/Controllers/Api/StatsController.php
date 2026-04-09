<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostStat;
use Illuminate\Http\Request;
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
            
            Log::info('📱 Track view attempt', [
                'post_id' => $postId,
                'ip' => $ip,
                'fingerprint' => substr($fingerprint, 0, 10)
            ]);
            
            // Просто ищем запись по fingerprint
            $stat = PostStat::where('post_id', $postId)
                ->where('fingerprint', $fingerprint)
                ->first();
            
            DB::transaction(function () use ($post, $ip, $userAgent, $fingerprint, $today, $stat) {
                if (!$stat) {
                    // Новый уникальный посетитель
                    PostStat::create([
                        'post_id' => $post->id,
                        'ip' => $ip,
                        'user_agent' => $userAgent,
                        'fingerprint' => $fingerprint,
                        'viewed' => true,
                        'viewed_at' => now(),
                        'viewed_at_date' => $today
                    ]);
                    
                    $post->increment('views_count');
                    
                    Log::info('✅ New visitor counted', [
                        'post_id' => $post->id,
                        'new_views_count' => $post->views_count + 1
                    ]);
                    
                } else {
                    // Посетитель уже был, проверяем дату последнего просмотра
                    $lastViewDate = $stat->viewed_at ? $stat->viewed_at->toDateString() : null;
                    
                    if ($lastViewDate !== $today) {
                        // Новый день - новый просмотр
                        $stat->update([
                            'viewed' => true,
                            'viewed_at' => now(),
                            'viewed_at_date' => $today
                        ]);
                        
                        $post->increment('views_count');
                        
                        Log::info('🔄 Returning visitor new day', [
                            'post_id' => $post->id,
                            'last_view' => $lastViewDate,
                            'today' => $today,
                            'new_views_count' => $post->views_count + 1
                        ]);
                    } else {
                        // Тот же день - не считаем повторно
                        Log::info('⏭️ Duplicate view ignored (same day)', [
                            'post_id' => $post->id,
                            'last_view' => $lastViewDate
                        ]);
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
                'liked' => $updatedStat ? $updatedStat->liked : false
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error tracking view', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отслеживании просмотра'
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
            
            Log::info('❤️ Toggle like attempt', [
                'post_id' => $postId,
                'fingerprint' => substr($fingerprint, 0, 10)
            ]);
            
            // Ищем запись по fingerprint
            $stat = PostStat::where('post_id', $postId)
                ->where('fingerprint', $fingerprint)
                ->first();
            
            DB::transaction(function () use ($post, $ip, $userAgent, $fingerprint, $stat) {
                if (!$stat) {
                    // Новый посетитель ставит лайк
                    PostStat::create([
                        'post_id' => $post->id,
                        'ip' => $ip,
                        'user_agent' => $userAgent,
                        'fingerprint' => $fingerprint,
                        'viewed' => false,
                        'liked' => true,
                        'viewed_at' => null,
                        'viewed_at_date' => null
                    ]);
                    
                    $post->increment('likes_count');
                    
                    Log::info('✅ New like added', [
                        'post_id' => $post->id,
                        'new_likes_count' => $post->likes_count + 1
                    ]);
                    
                } else {
                    // Переключаем лайк
                    $newLikedStatus = !$stat->liked;
                    
                    if ($newLikedStatus) {
                        $post->increment('likes_count');
                        Log::info('➕ Like added', ['post_id' => $post->id]);
                    } else {
                        $post->decrement('likes_count');
                        Log::info('➖ Like removed', ['post_id' => $post->id]);
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
                'views_count' => $post->views_count
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error toggling like', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при переключении лайка'
            ], 500);
        }
    }

    /**
     * Получить статистику поста
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
                'viewed_today' => $stat && $stat->viewed_at && $stat->viewed_at->isToday()
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error getting stats', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'views_count' => 0,
                'likes_count' => 0,
                'liked' => false,
                'viewed_today' => false
            ]);
        }
    }
}