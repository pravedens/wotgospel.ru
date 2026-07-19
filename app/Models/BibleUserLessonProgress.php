<?php
// app/Models/BibleUserLessonProgress.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BibleUserLessonProgress extends Model
{
    protected $table = 'bible_user_lesson_progress';
    
    protected $fillable = [
        'user_id',
        'lesson_id',
        'status',
        'video_watched_at',
        'practice_completed_at',
        'test_passed_at',
        'test_score',
        'test_attempts', 
        'attended_by_leader_at',
    ];
    
    protected $casts = [
        'video_watched_at' => 'datetime',
        'practice_completed_at' => 'datetime',
        'test_passed_at' => 'datetime',
        'attended_by_leader_at' => 'datetime',
        'test_score' => 'integer',
        'test_attempts' => 'integer', 
    ];
    
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_CALL_COMPLETED = 'call_completed';
    const STATUS_SCRIPTURE_COMPLETED = 'scripture_completed';
    const STATUS_VIDEO_WATCHED = 'video_watched';
    const STATUS_PRACTICE_COMPLETED = 'practice_completed';
    const STATUS_TEST_PASSED = 'test_passed';
    const STATUS_COMPLETED = 'completed';
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(BibleLesson::class, 'lesson_id');
    }
    
    public function markCallCompleted(): void
    {
        if ($this->status === self::STATUS_NOT_STARTED) {
            $this->status = self::STATUS_CALL_COMPLETED;
            $this->save();
        }
    }
    
    public function markScriptureCompleted(): void
    {
        if (in_array($this->status, [self::STATUS_NOT_STARTED, self::STATUS_CALL_COMPLETED])) {
            $this->status = self::STATUS_SCRIPTURE_COMPLETED;
            $this->save();
        }
    }
    
    public function markVideoWatched(): void
    {
        if ($this->status !== self::STATUS_VIDEO_WATCHED) {
            $this->status = self::STATUS_VIDEO_WATCHED;
            $this->video_watched_at = now();
            $this->save();
        }
    }
    
    public function markPracticeCompleted(): void
    {
        if ($this->status === self::STATUS_VIDEO_WATCHED) {
            $this->status = self::STATUS_PRACTICE_COMPLETED;
            $this->practice_completed_at = now();
            $this->save();
        }
    }
    
    public function markCompleted(): void
    {
        if ($this->status === self::STATUS_TEST_PASSED) {
            $this->status = self::STATUS_COMPLETED;
            $this->save();
        }
    }
    
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
    
    public function canProceedToNextStage(): array
    {
        $allowedNext = [
            self::STATUS_NOT_STARTED => [self::STATUS_CALL_COMPLETED],
            self::STATUS_CALL_COMPLETED => [self::STATUS_SCRIPTURE_COMPLETED],
            self::STATUS_SCRIPTURE_COMPLETED => [self::STATUS_VIDEO_WATCHED],
            self::STATUS_VIDEO_WATCHED => [self::STATUS_PRACTICE_COMPLETED],
            self::STATUS_PRACTICE_COMPLETED => [self::STATUS_TEST_PASSED],
            self::STATUS_TEST_PASSED => [self::STATUS_COMPLETED],
            self::STATUS_COMPLETED => [],
        ];
        
        return [
            'current_status' => $this->status,
            'allowed_next' => $allowedNext[$this->status] ?? [],
        ];
    }
    
    public function incrementTestAttempts(): void
{
    $this->test_attempts = ($this->test_attempts ?? 0) + 1;
    $this->save();
}

public function canRetryTest(): bool
{
    return ($this->test_attempts ?? 0) < 3; // попытки сдачи теста
}

public function markTestPassed(int $score): void
{
    if (in_array($this->status, [self::STATUS_PRACTICE_COMPLETED, self::STATUS_TEST_PASSED])) {
        $this->status = self::STATUS_TEST_PASSED;
        $this->test_passed_at = now();
        $this->test_score = $score;
        $this->test_attempts = 0; // Сбрасываем попытки при успешной сдаче
        $this->save();
    }
}
}