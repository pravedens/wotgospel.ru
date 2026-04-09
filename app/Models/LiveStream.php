<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LiveStream extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'platform',
        'embed_url',
        'is_active',
        'scheduled_start',
        'scheduled_end',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
    ];
    
    // Автоматически генерируем заголовок при создании
    protected static function booted()
    {
        static::creating(function ($liveStream) {
            if (empty($liveStream->title)) {
                $liveStream->title = 'Трансляция ' . now()->format('d.m.Y');
            }
        });
    }
    
    // Парсим embed_url и возвращаем ID для плеера
    public function getStreamIdAttribute()
    {
        if (!$this->embed_url) return null;
        
        // Для Rutube
        if (str_contains($this->embed_url, 'rutube.ru')) {
            // https://rutube.ru/play/embed/ID
            // https://rutube.ru/video/ID/
            preg_match('/(?:embed\/|video\/)([a-zA-Z0-9]+)/', $this->embed_url, $matches);
            return $matches[1] ?? null;
        }
        
        // Для YouTube
        if (str_contains($this->embed_url, 'youtube.com') || str_contains($this->embed_url, 'youtu.be')) {
            preg_match('/(?:embed\/|v\/|v=|\/)([a-zA-Z0-9_-]{11})/', $this->embed_url, $matches);
            return $matches[1] ?? null;
        }
        
        // Для VK
        if (str_contains($this->embed_url, 'vk.com')) {
            preg_match('/video(-?\d+_\d+)/', $this->embed_url, $matches);
            return $matches[1] ?? null;
        }
        
        return $this->embed_url;
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeCurrent($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('scheduled_start')
                  ->orWhere('scheduled_start', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('scheduled_end')
                  ->orWhere('scheduled_end', '>=', now());
            });
    }
}