<?php

namespace App\Chat;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Conversation $conversation;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->conversation->user1_id}"),
            new PrivateChannel("user.{$this->conversation->user2_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.conversation.created';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user1_id' => $this->conversation->user1_id,
            'user2_id' => $this->conversation->user2_id,
            'type' => $this->conversation->type,
        ];
    }
}