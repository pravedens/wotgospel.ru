<?php
// app/Models/BibleCourse.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\BibleTheme;

class BibleCourse extends Model
{
    protected $table = 'bible_courses';
    
    protected $fillable = [
        'title',
        'slug',
        'description',
        'image_url',
        'order',
        'is_published',
        'what_you_will_learn',
        'skills',
        'price',
        'statuses',
        'certificate_text',
    ];
    
    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
        'statuses' => 'array',
    ];
    
    protected static function booted()
    {
        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title);
            }
        });
    }
    
    public function lessons(): HasMany
    {
        return $this->hasMany(BibleLesson::class, 'course_id')->orderBy('order');
    }
    
    public function publishedLessons(): HasMany
    {
        return $this->lessons()->where('is_published', true);
    }
    
    public function parties(): HasMany
    {
        return $this->hasMany(BibleParty::class, 'course_id');
    }
    
    public function certificates(): HasMany
    {
        return $this->hasMany(BibleCertificate::class, 'course_id');
    }
    
   public function themes()
    {
        return $this->hasMany(BibleTheme::class, 'course_id')->orderBy('order');
    }

    public function publishedThemes(): HasMany
    {
        return $this->themes()->where('is_published', true);
    }
    
 /**   
    * Получить учителей, связанных с этим курсом.
    * Связь many-to-many через таблицу 'course_teacher'.
 */
public function teachers()
{
    return $this->belongsToMany(User::class, 'course_teacher', 'course_id', 'teacher_id');
}
    
    public function getProgressForUser(int $userId): array
{
    $totalLessons = $this->publishedLessons()->count();
    if ($totalLessons === 0) {
        return ['completed' => 0, 'total' => 0, 'percentage' => 0];
    }
    
    $completedLessons = BibleUserLessonProgress::where('user_id', $userId)
        ->whereIn('lesson_id', $this->publishedLessons()->pluck('id'))
        ->whereIn('status', ['completed', 'test_passed']) // Добавляем test_passed
        ->count();
    
    $percentage = round(($completedLessons / $totalLessons) * 100);
    
    return [
        'completed' => $completedLessons,
        'total' => $totalLessons,
        'percentage' => $percentage,
    ];
}
    
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    
    public function getStatusesListAttribute(): array
    {
        $defaultStatuses = [
            ['name' => 'Ученик', 'percentage' => 0, 'icon' => '📘'],
            ['name' => 'Служитель', 'percentage' => 25, 'icon' => '🙏'],
            ['name' => 'Лидер', 'percentage' => 50, 'icon' => '👑'],
            ['name' => 'Наставник', 'percentage' => 75, 'icon' => '⭐'],
        ];
        
        return $this->statuses ?? $defaultStatuses;
    }
}