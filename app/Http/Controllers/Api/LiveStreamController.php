<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use Illuminate\Http\Request;

class LiveStreamController extends Controller
{
    public function current(Request $request)
    {
        $stream = LiveStream::current()->first();
        
        if (!$stream) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Нет активной трансляции'
            ]);
        }
        
        // Получаем сохраненное время из запроса
        $savedTime = (int)$request->query('startTime', 0);
        
        // Формируем embed URL с параметрами
        $embedUrl = $this->getEmbedUrl($stream, $savedTime);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $stream->id,
                'title' => $stream->title,
                'platform' => $stream->platform,
                'embedUrl' => $embedUrl,
                'isActive' => $stream->is_active,
                'scheduledStart' => $stream->scheduled_start,
                'scheduledEnd' => $stream->scheduled_end,
                'streamId' => $stream->stream_id,
            ]
        ]);
    }
    
    private function getEmbedUrl($stream, $savedTime = 0)
    {
        $streamId = $stream->stream_id;
        
        if (!$streamId) {
            return $stream->embed_url;
        }
        
        // Базовый URL для разных платформ
        $baseUrl = match($stream->platform) {
            'rutube' => "https://rutube.ru/play/embed/{$streamId}",
            'youtube' => "https://www.youtube.com/embed/{$streamId}",
            'vk' => "https://vk.com/video_ext.php?oid={$streamId}",
            default => $stream->embed_url,
        };
        
        // Добавляем параметры
        $params = ['autoplay=1'];
        
        if ($savedTime > 0) {
            if ($stream->platform === 'rutube') {
                $params[] = "startTime={$savedTime}";
            } else {
                $params[] = "start={$savedTime}";
            }
        }
        
        return $baseUrl . '?' . implode('&', $params);
    }
    
    public function upcoming()
    {
        $streams = LiveStream::where('is_active', false)
            ->where('scheduled_start', '>', now())
            ->orderBy('scheduled_start')
            ->limit(5)
            ->get()
            ->map(function($stream) {
                return [
                    'id' => $stream->id,
                    'title' => $stream->title,
                    'platform' => $stream->platform,
                    'streamId' => $stream->stream_id,
                    'scheduledStart' => $stream->scheduled_start,
                    'scheduledEnd' => $stream->scheduled_end,
                    'embedUrl' => $this->getEmbedUrl($stream),
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $streams
        ]);
    }
}