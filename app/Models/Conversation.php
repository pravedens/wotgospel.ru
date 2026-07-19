<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'user1_id',
        'user2_id',
        'type',
        'party_id',
        'last_message_at',
        'last_read_at_user1',
        'last_read_at_user2',
        'is_active',
        'is_archived_user1',
        'is_archived_user2',
    ];

    protected $casts = [
        'user1_id' => 'integer',
        'user2_id' => 'integer',
        'party_id' => 'integer',
        'last_message_at' => 'datetime',
        'last_read_at_user1' => 'datetime',
        'last_read_at_user2' => 'datetime',
        'is_active' => 'boolean',
        'is_archived_user1' => 'boolean',
        'is_archived_user2' => 'boolean',
    ];

    // Связи
    public function user1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    public function user2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user2_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(BibleParty::class, 'party_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latest();
    }

    // Методы
    public function getOtherUser(int $userId): ?User
    {
        if ($this->user1_id === $userId) {
            return $this->user2;
        }
        if ($this->user2_id === $userId) {
            return $this->user1;
        }
        return null;
    }

    public function hasUser(int $userId): bool
    {
        return (int) $this->user1_id === (int) $userId || (int) $this->user2_id === (int) $userId;
    }

    public function markAsRead(int $userId): void
    {
        if ($this->user1_id === $userId) {
            $this->last_read_at_user1 = now();
        } elseif ($this->user2_id === $userId) {
            $this->last_read_at_user2 = now();
        }
        $this->save();

        $this->messages()
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->messages()
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    // Scope
    public function scopeBetweenUsers($query, int $user1Id, int $user2Id)
    {
        return $query->where(function ($q) use ($user1Id, $user2Id) {
            $q->where('user1_id', $user1Id)->where('user2_id', $user2Id);
        })->orWhere(function ($q) use ($user1Id, $user2Id) {
            $q->where('user1_id', $user2Id)->where('user2_id', $user1Id);
        });
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user1_id', $userId)->orWhere('user2_id', $userId);
        })->where('is_active', true);
    }

    // Инициализация беседы
    public static function findOrCreate(int $user1Id, int $user2Id, string $type = 'private'): self
    {
        $conversation = self::betweenUsers($user1Id, $user2Id)->first();

        if (!$conversation) {
            $conversation = self::create([
                'user1_id' => min($user1Id, $user2Id),
                'user2_id' => max($user1Id, $user2Id),
                'type' => $type,
            ]);
        }

        return $conversation;
    }
}