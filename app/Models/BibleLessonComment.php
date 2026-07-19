<?php
// app/Models/BibleLessonComment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibleLessonComment extends Model
{
    protected $table = 'bible_lesson_comments';
    
    protected $fillable = [
        'lesson_id',
        'user_id',
        'parent_id',
        'content',
        'is_approved',
        'approved_at',
        'approved_by',
    ];
    
    protected $casts = [
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];
    
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(BibleLesson::class, 'lesson_id');
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
    
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
    
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
    public function approve(int $approverId): void
    {
        $this->is_approved = true;
        $this->approved_at = now();
        $this->approved_by = $approverId;
        $this->save();
    }
}