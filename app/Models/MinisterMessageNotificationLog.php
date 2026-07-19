<?php
// app/Models/MinisterMessageNotificationLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinisterMessageNotificationLog extends Model
{
    protected $table = 'minister_message_notification_logs';
    
    protected $fillable = [
        'message_id',
        'minister_id',
        'type',
        'channel',
        'status',
        'error_message',
        'sent_at',
    ];
    
    protected $casts = [
        'sent_at' => 'datetime',
    ];
    
    public function message(): BelongsTo
    {
        return $this->belongsTo(MinisterMessage::class, 'message_id');
    }
    
    public function minister(): BelongsTo
    {
        return $this->belongsTo(User::class, 'minister_id');
    }
    
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