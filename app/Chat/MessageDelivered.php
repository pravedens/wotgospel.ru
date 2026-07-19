<?php

namespace App\Chat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDelivered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $messageId;
    public int $conversationId;
    public int $receiverId;

    public function __construct(int $messageId, int $conversationId, int $receiverId)
    {
        $this->messageId = $messageId;
        $this->conversationId = $conversationId;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversationId}"),
            new PrivateChannel("user.{$this->receiverId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.delivered';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'receiver_id' => $this->receiverId,
        ];
    }
}