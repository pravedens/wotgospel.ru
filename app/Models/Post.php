<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    
    protected $table = 'posts';
    
    protected $appends = [
        'audio_url', 
        'text_url', 
        'audio_size_formatted',
        'text_size_formatted',
        'thumbnail_url',
        'display_text_filename',
        'clean_description',  // Добавляем
        'clean_content'       // Добавляем
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'audio_size' => 'integer',
        'text_size' => 'integer',
    ];
    
    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = 2;
            if (empty($post->created_at)) {
                $post->created_at = now();
            }
        });
    }
    
    // ===== ОТНОШЕНИЯ =====
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }
    
    public function favoritedBy()
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }
    
    public function stats()
    {
        return $this->hasMany(PostStat::class);
    }
    
    // ===== АКСЕССОРЫ =====
    
    /**
     * Получить URL изображения
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) return null;
        
        if (filter_var($this->thumbnail, FILTER_VALIDATE_URL)) {
            return $this->thumbnail;
        }
        
        if (str_starts_with($this->thumbnail, 'posts/thumbnails/')) {
            return Storage::disk('s3')->url($this->thumbnail);
        }
        
        return Storage::disk('public')->url($this->thumbnail);
    }
    
    /**
     * Получить URL аудио файла
     */
    public function getAudioUrlAttribute(): ?string
    {
        if (!$this->audio_file) return null;
        
        if (filter_var($this->audio_file, FILTER_VALIDATE_URL)) {
            return $this->audio_file;
        }
        
        if (str_starts_with($this->audio_file, 'posts/audio/')) {
            return Storage::disk('s3')->url($this->audio_file);
        }
        
        return Storage::disk('public')->url($this->audio_file);
    }
    
    /**
     * Получить URL текстового файла
     */
    public function getTextUrlAttribute(): ?string
    {
        if (!$this->text_file) return null;
        
        if (filter_var($this->text_file, FILTER_VALIDATE_URL)) {
            return $this->text_file;
        }
        
        if (str_starts_with($this->text_file, 'posts/text/')) {
            return Storage::disk('s3')->url($this->text_file);
        }
        
        return Storage::disk('public')->url($this->text_file);
    }
    
    /**
     * Получить читаемое имя текстового файла для отображения
     */
    public function getDisplayTextFilenameAttribute(): string
    {
        if ($this->text_filename) {
            return $this->text_filename;
        }
        
        $extension = 'docx';
        if ($this->text_file) {
            $extension = pathinfo($this->text_file, PATHINFO_EXTENSION);
        }
        
        return $this->title . '.' . $extension;
    }
    
    /**
     * ✅ НОВЫЙ: получить описание без HTML-тегов
     */
    public function getCleanDescriptionAttribute(): string
    {
        if (!$this->description) {
            return '';
        }
        
        // Удаляем все HTML-теги
        return strip_tags($this->description);
    }
    
    /**
     * ✅ НОВЫЙ: получить контент без HTML-тегов
     */
    public function getCleanContentAttribute(): string
    {
        if (!$this->content) {
            return '';
        }
        
        // Удаляем все HTML-теги
        return strip_tags($this->content);
    }
    
    /**
     * Форматированный размер аудио
     */
    public function getAudioSizeFormattedAttribute(): string
    {
        return $this->formatBytes($this->audio_size);
    }
    
    /**
     * Форматированный размер текстового файла
     */
    public function getTextSizeFormattedAttribute(): string
    {
        return $this->formatBytes($this->text_size);
    }
    
    /**
     * Вспомогательный метод для форматирования байтов
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        if (!$bytes) return '';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    // ===== СТАТИСТИКА =====
    
    public function getFavoritesCountAttribute()
    {
        return $this->favoritedBy()->count();
    }

    public function getIsFavoritedAttribute()
    {
        if (!auth()->check()) return false;
        return $this->favoritedBy()
            ->where('user_id', auth()->id())
            ->exists();
    }
    
    public function getViewsCountAttribute($value)
    {
        return $value ?? 0;
    }

    public function getLikesCountAttribute($value)
    {
        return $value ?? 0;
    }
}