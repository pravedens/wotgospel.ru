<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class ChatMessageNotification extends Notification
{
    use Queueable;

    protected Message $message;
    protected string $senderName;

    public function __construct(Message $message, string $senderName)
    {
        $this->message = $message;
        $this->senderName = $senderName;
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        $conversationId = $this->message->conversation_id;

        return (new WebPushMessage)
            ->title("💬 {$this->senderName}")
            ->body($this->message->message)
            ->icon('/favicon/icon-192.png')
            ->badge('/favicon/favicon-32.png')
            ->data([
                'url' => 'https://wotnt.ru/dashboard?tab=chat&conversation=' . $conversationId,
                'conversation_id' => $conversationId,
                'message_id' => $this->message->id,
                'type' => 'chat_message',
            ])
            ->action('Открыть чат', 'open');
    }
}