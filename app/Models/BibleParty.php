<?php
// app/Models/BibleParty.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BibleParty extends Model
{
    protected $table = 'bible_parties';
    
    protected $fillable = [
        'name',
        'description',
        'course_id',
        'leader_id',
        'join_code',
        'meeting_day',
        'meeting_time',
        'zoom_link',
        'max_students',
        'is_active',
    ];
    
    protected $casts = [
        'meeting_time' => 'datetime',
        'max_students' => 'integer',
        'is_active' => 'boolean',
    ];
    
    protected static function booted()
    {
        static::creating(function ($party) {
            if (empty($party->join_code)) {
                $party->join_code = Str::upper(Str::random(6));
            }
        });
    }
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(BibleCourse::class, 'course_id');
    }
    
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }
    
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bible_party_user', 'party_id', 'user_id')
            ->withPivot('joined_at', 'is_active')
            ->withTimestamps();
    }
    
    public function activeStudents(): BelongsToMany
    {
        return $this->students()->wherePivot('is_active', true);
    }
    
    public function messages(): HasMany
    {
        return $this->hasMany(BiblePartyMessage::class, 'party_id');
    }
    
    public function approvedMessages(): HasMany
    {
        return $this->messages()->where('is_approved', true);
    }
    
    public function addStudent(int $userId): bool
    {
        if ($this->activeStudents()->count() >= $this->max_students) {
            return false;
        }
        
        $this->students()->attach($userId, ['joined_at' => now(), 'is_active' => true]);
        return true;
    }
    
    public function removeStudent(int $userId): void
    {
        $this->students()->updateExistingPivot($userId, ['is_active' => false]);
    }
    
    public function regenerateJoinCode(): void
    {
        $this->join_code = Str::upper(Str::random(6));
        $this->save();
    }
}