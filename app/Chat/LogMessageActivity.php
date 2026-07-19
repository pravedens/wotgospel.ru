<?php

namespace App\Chat;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogMessageActivity implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MessageSent $event): void
    {
        Log::info('New message sent', [
            'message_id' => $event->message->id,
            'conversation_id' => $event->conversationId,
            'sender_id' => $event->message->sender_id,
            'receiver_id' => $event->message->receiver_id,
            'type' => $event->message->type,
            'created_at' => $event->message->created_at,
        ]);
    }
}