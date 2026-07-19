<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BibleTheme extends Model
{
    protected $table = 'bible_themes';
    
    protected $fillable = [
        'course_id',
        'teacher_id',
        'title',
        'slug',
        'description',
        'order',
        'is_published'
    ];
    
    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
    ];
    
    protected static function booted()
    {
        static::creating(function ($theme) {
            if (empty($theme->slug)) {
                $theme->slug = Str::slug($theme->title);
            }
        });
    }
    
    public function course()
    {
        return $this->belongsTo(BibleCourse::class, 'course_id');
    }
    
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
    
    public function lessons(): HasMany
    {
        return $this->hasMany(BibleLesson::class, 'theme_id')->orderBy('order');
    }
    
    public function publishedLessons(): HasMany
    {
        return $this->lessons()->where('is_published', true);
    }
    
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    
    public function getProgressForUser(int $userId): array
    {
        $totalLessons = $this->publishedLessons()->count();
        if ($totalLessons === 0) {
            return ['completed' => 0, 'total' => 0, 'percentage' => 0];
        }
        
        $completedLessons = BibleUserLessonProgress::where('user_id', $userId)
            ->whereIn('lesson_id', $this->publishedLessons()->pluck('id'))
            ->whereIn('status', ['completed', 'test_passed'])
            ->count();
        
        $percentage = round(($completedLessons / $totalLessons) * 100);
        
        return [
            'completed' => $completedLessons,
            'total' => $totalLessons,
            'percentage' => $percentage,
        ];
    }
}