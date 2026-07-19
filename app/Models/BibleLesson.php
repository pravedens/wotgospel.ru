<?php
// app/Models/BibleLesson.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BibleLesson extends Model
{
    protected $table = 'bible_lessons';
    
    protected $fillable = [
        'course_id',
        'order',
        'title',
        'slug',
        'call_question',
        'call_answer',              // ✅ ДОБАВЛЕНО: ответ на призыв
        'scripture_verses',
        'content',
        'practice_task',
        'pdf_conspect_url',
        'is_published',
        'scripture_verse_ids',
        'theme_id',
    ];
    
    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
        'scripture_verse_ids' => 'array',
    ];
    
    protected static function booted()
    {
        static::creating(function ($lesson) {
            if (empty($lesson->slug)) {
                $lesson->slug = Str::slug($lesson->title);
            }
        });
    }
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(BibleCourse::class, 'course_id');
    }
    
    public function questions(): HasMany
    {
        return $this->hasMany(BibleTestQuestion::class, 'lesson_id')->orderBy('order');
    }
    
    public function progress(): HasMany
    {
        return $this->hasMany(BibleUserLessonProgress::class, 'lesson_id');
    }
    
    public function essays(): HasMany
    {
        return $this->hasMany(BibleEssay::class, 'lesson_id');
    }
    
    public function comments(): HasMany
    {
        return $this->hasMany(BibleLessonComment::class, 'lesson_id');
    }
    
    public function approvedComments(): HasMany
    {
        return $this->comments()->where('is_approved', true);
    }
    
    public function theme(): BelongsTo
    {
        return $this->belongsTo(BibleTheme::class, 'theme_id');
    }
    
    public function videos(): HasMany
    {
        return $this->hasMany(BibleLessonVideo::class)->orderBy('order');
    }

    // Для обратной совместимости: метод для получения первого видео
    public function getFirstVideoAttribute(): ?BibleLessonVideo
    {
        return $this->videos()->first();
    }
    
    public function isCompletedByUser(int $userId): bool
    {
        return $this->progress()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->exists();
    }
    
    public function getUserProgress(int $userId): ?BibleUserLessonProgress
    {
        return $this->progress()->where('user_id', $userId)->first();
    }
    
   /**
 * Получить предыдущий урок в рамках КУРСА (глобально)
 */
public function getPreviousLesson(): ?self
{
    return self::where('course_id', $this->course_id)
        ->where('order', '<', $this->order)
        ->where('is_published', true)
        ->orderBy('order', 'desc')
        ->first();
}
    
    public function getNextLesson(): ?self
{
    return self::where('course_id', $this->course_id)
        ->where('order', '>', $this->order)
        ->where('is_published', true)
        ->orderBy('order', 'asc')
        ->first();
}
    
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    
    /**
     * Форматированный текст стихов для отображения на фронтенде
     */
    public function getFormattedScriptureVersesAttribute(): string
    {
        if (empty($this->scripture_verses)) {
            return '<p class="text-white/50">📖 Стихи для этого урока пока не добавлены</p>';
        }
        
        $verses = explode("\n\n", trim($this->scripture_verses));
        $html = '';
        foreach ($verses as $verse) {
            if (trim($verse)) {
                $html .= "<p class=\"mb-3 pb-2 border-b border-white/10\">📖 {$verse}</p>";
            }
        }
        return $html;
    }
    
    /**
     * Мутатор для scripture_verse_ids (массив ID стихов)
     */
    public function setScriptureVerseIdsAttribute($value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        
        if (!is_array($value)) {
            $value = [];
        }
        
        $this->attributes['scripture_verse_ids'] = json_encode(array_values($value));
    }
    
    /**
     * Аксессор для scripture_verse_ids
     */
    public function getScriptureVerseIdsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
    
    /**
     * ✅ НОВЫЙ МЕТОД: получение стихов для ручного ввода в Filament
     * Преобразует scripture_verses обратно в массив для Repeater
     */
    public function getScriptureVersesManualAttribute(): array
    {
        if (empty($this->scripture_verses)) {
            return [];
        }
        
        $verses = explode("\n\n", trim($this->scripture_verses));
        $result = [];
        
        foreach ($verses as $verse) {
            if (preg_match('/^(.+?) — (.+)$/s', $verse, $matches)) {
                $result[] = [
                    'reference' => trim($matches[1]),
                    'text' => trim($matches[2]),
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * ✅ НОВЫЙ МЕТОД: сохранение ручного ввода стихов из Filament
     */
    public function setScriptureVersesManualAttribute($value)
{
    if (empty($value) || !is_array($value)) {
        $this->attributes['scripture_verses'] = null;
        return;
    }
    
    $textsArray = [];
    foreach ($value as $item) {
        $reference = trim($item['reference'] ?? '');
        $text = trim($item['text'] ?? '');
        if ($reference && $text) {
            $textsArray[] = $reference . "\n" . $text;
        }
    }
    
    $this->attributes['scripture_verses'] = implode("\n\n", $textsArray);
}
    
    public function setVideoUrlAttribute($value)
    {
        // Сохраняем ссылку, если она понадобится
        $this->attributes['video_url'] = $value;

        if ($value) {
            // === 1. Обработка Rutube ===
            if (str_contains($value, 'rutube.ru')) {
                $this->attributes['video_platform'] = 'rutube';
                $originalUrl = $value;

                preg_match('/\/video\/(?:private\/)?([a-f0-9]+)(?:\/?\?p=([a-zA-Z0-9_\-]+))?/i', $originalUrl, $matches);
                
                $videoId = $matches[1] ?? null;
                $accessKey = isset($matches[2]) ? '?p=' . $matches[2] : '';
                
                if ($videoId) {
                    $embedUrl = "https://rutube.ru/play/embed/{$videoId}{$accessKey}";
                    $this->attributes['video_url'] = $embedUrl;
                    $this->attributes['video_id'] = $videoId;
                }
            }
            
            // === 2. Обработка VK ===
            if (str_contains($value, 'vk.com')) {
                $this->attributes['video_platform'] = 'vk';
                preg_match('/oid=(-?\d+).*?id=(\d+)/', $value, $matches);
                if (isset($matches[1]) && isset($matches[2])) {
                    $this->attributes['video_id'] = $matches[2];
                }
            }
            
            // === 3. Обработка YouTube ===
            if (str_contains($value, 'youtube.com') || str_contains($value, 'youtu.be')) {
                $this->attributes['video_platform'] = 'youtube';
                preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $value, $matches);
                if (isset($matches[1])) {
                    $this->attributes['video_id'] = $matches[1];
                }
            }
        }
    }
}