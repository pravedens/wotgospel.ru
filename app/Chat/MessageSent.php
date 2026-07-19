<?php

namespace App\Chat;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;
    public int $conversationId;

    public function __construct(Message $message, int $conversationId)
    {
        $this->message = $message;
        $this->conversationId = $conversationId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversationId}"),
            new PrivateChannel("user.{$this->message->receiver_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->conversationId,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->full_name ?? 'Пользователь',
            'sender_avatar' => $this->message->sender->avatar_url ?? null,
            'receiver_id' => $this->message->receiver_id,
            'message' => $this->message->message,
            'type' => $this->message->type,
            'attachments' => $this->message->attachments,
            'is_read' => $this->message->is_read,
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }
}