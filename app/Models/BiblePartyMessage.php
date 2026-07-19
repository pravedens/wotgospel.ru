<?php
// app/Models/BiblePartyMessage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiblePartyMessage extends Model
{
    protected $table = 'bible_party_messages';
    
    protected $fillable = [
        'party_id',
        'user_id',
        'message',
        'is_approved',
        'approved_at',
        'approved_by',
    ];
    
    protected $casts = [
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];
    
    public function party(): BelongsTo
    {
        return $this->belongsTo(BibleParty::class, 'party_id');
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
    
    public function reject(): void
    {
        $this->delete();
    }
}