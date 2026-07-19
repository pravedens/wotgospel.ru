<?php
// app/Models/EventNotificationLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventNotificationLog extends Model
{
    protected $table = 'event_notifications_log';
    
    protected $fillable = [
        'user_id',
        'event_id',
        'type',
        'channel',
        'sent_at',
        'status',
        'error_message',
    ];
    
    protected $casts = [
        'sent_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    // Нет связи с Event из-за отсутствия внешнего ключа
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }
    
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
    
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
    
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
}