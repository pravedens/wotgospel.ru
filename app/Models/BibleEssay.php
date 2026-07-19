<?php
// app/Models/BibleEssay.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BibleEssay extends Model
{
    protected $table = 'bible_essays';
    
    protected $fillable = [
        'user_id',
        'lesson_id',
        'question_id',
        'teacher_id',
        'content',
        'status',
        'teacher_feedback',
        'score',
        'reviewed_at',
        'reviewed_by',
    ];
    
    protected $casts = [
        'reviewed_at' => 'datetime',
        'score' => 'integer',
    ];
    
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(BibleLesson::class, 'lesson_id');
    }
    
    public function question(): BelongsTo
    {
        return $this->belongsTo(BibleTestQuestion::class, 'question_id');
    }
    
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
    
    /**
     * Учитель, которому отправлено эссе
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
    
    public function approve(int $score, string $feedback, int $reviewerId): void
{
    $this->status = self::STATUS_APPROVED;
    $this->score = $score;
    $this->teacher_feedback = $feedback;
    $this->reviewed_by = $reviewerId;
    $this->reviewed_at = now();
    $this->save();
    
    // ✅ Отправляем уведомление ученику
    $notificationService = app(\App\Services\NotificationService::class);
    $notificationService->sendEssayReviewedNotification($this->user, $this->lesson, $score, $feedback, 'approved');
}
    
    public function reject(string $feedback, int $reviewerId): void
{
    $this->status = self::STATUS_REJECTED;
    $this->score = 0;
    $this->teacher_feedback = $feedback;
    $this->reviewed_by = $reviewerId;
    $this->reviewed_at = now();
    $this->save();
    
    // ✅ Отправляем уведомление ученику
    $notificationService = app(\App\Services\NotificationService::class);
    $notificationService->sendEssayReviewedNotification($this->user, $this->lesson, 0, $feedback, 'rejected');
}
    
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}