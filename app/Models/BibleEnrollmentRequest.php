<?php
// app/Models/BibleEnrollmentRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BibleEnrollmentRequest extends Model
{
    protected $table = 'bible_enrollment_requests';
    
    protected $fillable = [
        'user_id',
        'course_id',
        'status',
        'notes',
        'reviewed_by',
        'reviewed_at',
        // 🆕 Анкетные поля
        'city',
        'church_name',
        'phone',
        'birth_date',
        'about',
        'marital_status',
        'gender',
        'ministry',
        'bible_courses_experience',
        'learning_expectations',
        'agreement_accepted',
        'agreement_accepted_at',
        'agreement_ip',
    ];
    
    protected $casts = [
        'reviewed_at' => 'datetime',
        'birth_date' => 'date',
    ];
    
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
    
    public function approve(int $reviewerId, ?int $courseId = null): void
{
    $this->status = self::STATUS_APPROVED;
    $this->reviewed_by = $reviewerId;
    $this->reviewed_at = now();
    $this->save();
    
    $course = $courseId ? BibleCourse::find($courseId) : null;
    
    // Обновляем профиль пользователя
    $this->user->update([
        'city' => $this->city,
        'church_name' => $this->church_name,
        'phone' => $this->phone,
        'birth_date' => $this->birth_date,
        'about' => $this->about,
        'marital_status' => $this->marital_status,
        'gender' => $this->gender,
        'ministry' => $this->ministry,
        'bible_courses_experience' => $this->bible_courses_experience,
        'learning_expectations' => $this->learning_expectations,
        'assigned_course_id' => $courseId,
        'enrolled_year' => now()->year,
        'agreement_accepted_at' => now(),
        'agreement_ip' => request()->ip(),
    ]);
    
    $this->user->assignRole('student');
    
    // ✅ Отправляем уведомление ученику
    $notificationService = app(\App\Services\NotificationService::class);
    $notificationService->sendStudentEnrollmentApprovedNotification($this->user, $course);
}
    
    public function reject(int $reviewerId): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->reviewed_by = $reviewerId;
        $this->reviewed_at = now();
        $this->save();
        
        // ✅ Уведомление студенту
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->sendStudentEnrollmentRejectedNotification($this->user);
    }
}