<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinisterMessage extends Model
{
    protected $fillable = [
        'minister_id', 'user_id', 'sender_name', 'sender_email', 'message',
        'is_read', 'read_at', 'ip_address', 'user_agent'
    ];
    
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];
    
    public function minister(): BelongsTo
    {
        return $this->belongsTo(User::class, 'minister_id');
    }
    
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function notificationLogs()
    {
        return $this->hasMany(MinisterMessageNotificationLog::class, 'message_id');
    }
    
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
}