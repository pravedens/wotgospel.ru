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
        'stream_id',      // ✅ Добавляем stream_id
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
            
            // ✅ Автоматически извлекаем stream_id из embed_url
            if (empty($liveStream->stream_id) && $liveStream->embed_url) {
                $liveStream->stream_id = $liveStream->getStreamIdFromUrl();
            }
        });
        
        // ✅ Обновляем stream_id при сохранении
        static::saving(function ($liveStream) {
            if ($liveStream->isDirty('embed_url') && $liveStream->embed_url) {
                $liveStream->stream_id = $liveStream->getStreamIdFromUrl();
            }
        });
    }
    
    // ✅ Извлекаем ID из embed_url
    public function getStreamIdFromUrl()
    {
        if (!$this->embed_url) return null;
        
        // Для Rutube
        if (str_contains($this->embed_url, 'rutube.ru')) {
            preg_match('/(?:embed\/|video\/)([a-f0-9]+)/', $this->embed_url, $matches);
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
        
        return null;
    }
    
    // ✅ Аксессор для получения ID
    public function getStreamIdAttribute()
    {
        if ($this->attributes['stream_id'] ?? false) {
            return $this->attributes['stream_id'];
        }
        return $this->getStreamIdFromUrl();
    }
    
    // ✅ Мутатор для установки stream_id
    public function setStreamIdAttribute($value)
    {
        $this->attributes['stream_id'] = $value;
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