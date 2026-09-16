<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherMessage extends Model
{
    protected $table = 'teacher_messages';

    protected $fillable = [
        'teacher_id',
        'user_id',
        'sender_name',
        'sender_email',
        'message',
        'is_read',
        'read_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * ✅ Убираем HTML-теги при сохранении.
     * Сообщения учителям — plain text, HTML не нужен.
     */
    public function setMessageAttribute($value)
    {
        $this->attributes['message'] = trim(strip_tags($value));
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function markAsUnread(): void
    {
        if ($this->is_read) {
            $this->update([
                'is_read' => false,
                'read_at' => null,
            ]);
        }
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }
}
