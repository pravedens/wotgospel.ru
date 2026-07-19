<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'receiver_id',
        'message',
        'type',
        'attachments',
        'is_read',
        'read_at',
        'is_delivered',
        'delivered_at',
        'is_approved',
        'approved_at',
        'approved_by',
        'is_censored',
        'original_message',
        'is_system',
        'metadata',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'is_read' => 'boolean',
        'is_delivered' => 'boolean',
        'is_approved' => 'boolean',
        'is_censored' => 'boolean',
        'is_system' => 'boolean',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }
    }

    public function markAsDelivered(): void
    {
        if (!$this->is_delivered) {
            $this->update([
                'is_delivered' => true,
                'delivered_at' => now()
            ]);
        }
    }

    public function approve(int $approverId): void
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approverId,
        ]);
    }

    public function reject(): void
    {
        $this->delete();
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('sender_id', $userId)->orWhere('receiver_id', $userId);
    }
}