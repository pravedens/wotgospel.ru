<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BibleLessonVideo extends Model
{
    protected $table = 'bible_lesson_videos';
    
    protected $fillable = [
        'lesson_id',
        'title',
        'url',
        'platform',
        'video_id',
        'order'
    ];
    
    protected $casts = [
        'order' => 'integer',
    ];
    
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(BibleLesson::class);
    }
    
    // Автоматическое определение платформы и ID видео
    public function setUrlAttribute($value)
    {
        $this->attributes['url'] = $value;
        
        if ($value) {
            // Rutube
            if (str_contains($value, 'rutube.ru')) {
                $this->attributes['platform'] = 'rutube';
                preg_match('/\/video\/(?:private\/)?([a-f0-9]+)(?:\/?\?p=([a-zA-Z0-9_\-]+))?/i', $value, $matches);
                $this->attributes['video_id'] = $matches[1] ?? null;
            }
            // YouTube
            elseif (str_contains($value, 'youtube.com') || str_contains($value, 'youtu.be')) {
                $this->attributes['platform'] = 'youtube';
                preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $value, $matches);
                $this->attributes['video_id'] = $matches[1] ?? null;
            }
            // VK
            elseif (str_contains($value, 'vk.com')) {
                $this->attributes['platform'] = 'vk';
                preg_match('/oid=(-?\d+).*?id=(\d+)/', $value, $matches);
                $this->attributes['video_id'] = $matches[2] ?? null;
            }
            // Vimeo
            elseif (str_contains($value, 'vimeo.com')) {
                $this->attributes['platform'] = 'vimeo';
                preg_match('/vimeo\.com\/(\d+)/', $value, $matches);
                $this->attributes['video_id'] = $matches[1] ?? null;
            }
        }
    }
    
    // Получить embed URL
    public function getEmbedUrlAttribute(): ?string
    {
        if (!$this->url) return null;
        
        if ($this->platform === 'rutube') {
            $embedUrl = "https://rutube.ru/play/embed/{$this->video_id}";
            
            // Добавляем ключ доступа для приватных видео
            if (preg_match('/[?&]p=([a-zA-Z0-9_\-]+)/', $this->url, $matches)) {
                $embedUrl .= "?p=" . $matches[1];
            }
            
            return $embedUrl;
        }
        
        return match ($this->platform) {
            'youtube' => "https://www.youtube.com/embed/{$this->video_id}",
            'vk' => "https://vk.com/video_ext.php?oid={$this->video_id}",
            'vimeo' => "https://player.vimeo.com/video/{$this->video_id}",
            default => $this->url,
        };
    }
}