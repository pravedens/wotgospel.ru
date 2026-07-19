<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BibleCourseReset extends Model
{
    protected $table = 'bible_course_resets';
    
    protected $fillable = [
        'user_id',
        'course_id',
        'lesson_id',
        'reset_reason',
        'old_status',
        'new_status'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(BibleCourse::class, 'course_id');
    }
    
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(BibleLesson::class, 'lesson_id');
    }
}