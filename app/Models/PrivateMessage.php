<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtmlInput;

class PrivateMessage extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'message', 'is_read', 'read_at'];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        // ✅ Санитизация HTML
    'message' => CleanHtmlInput::class,
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function markAsRead()
    {
        if (! $this->is_read) {
            $this->is_read = true;
            $this->read_at = now();
            $this->save();
        }
    }
}
