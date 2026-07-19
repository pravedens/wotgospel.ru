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
     * Связь с учителем (получателем)
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
    
    /**
     * Связь с отправителем (если авторизован)
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * Отметить сообщение как прочитанное
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
    
    /**
     * Отметить как непрочитанное
     */
    public function markAsUnread(): void
    {
        if ($this->is_read) {
            $this->update([
                'is_read' => false,
                'read_at' => null,
            ]);
        }
    }
    
    /**
     * Scope для непрочитанных сообщений
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
    
    /**
     * Scope для прочитанных сообщений
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }
    
    /**
     * Scope для сообщений конкретному учителю
     */
    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }
}