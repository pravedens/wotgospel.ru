<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use Illuminate\Http\Request;

class LiveStreamController extends Controller
{
    public function current()
    {
        $stream = LiveStream::current()->first();
        
        if (!$stream) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Нет активной трансляции'
            ]);
        }
        
        // Формируем правильный embed URL
        $embedUrl = $this->getEmbedUrl($stream);
        
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
            ]
        ]);
    }
    
    private function getEmbedUrl($stream)
    {
        $streamId = $stream->stream_id;
        
        return match($stream->platform) {
            'rutube' => "https://rutube.ru/play/embed/{$streamId}",
            'youtube' => "https://www.youtube.com/embed/{$streamId}",
            'vk' => "https://vk.com/video_ext.php?oid={$streamId}",
            default => $stream->embed_url,
        };
    }
    
    public function upcoming()
    {
        $streams = LiveStream::where('is_active', false)
            ->where('scheduled_start', '>', now())
            ->orderBy('scheduled_start')
            ->limit(5)
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $streams
        ]);
    }
}