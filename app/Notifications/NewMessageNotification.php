<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    protected string $senderName;
    protected string $messageText;

    public function __construct(string $senderName, string $messageText)
    {
        $this->senderName = $senderName;
        $this->messageText = $messageText;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'chat_message',
            'sender_name' => $this->senderName,
            'message' => $this->messageText,
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}