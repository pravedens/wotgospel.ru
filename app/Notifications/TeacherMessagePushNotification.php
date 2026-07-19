<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class TeacherMessagePushNotification extends Notification
{
    use Queueable;
    
    protected $message;
    
    public function __construct($message)
    {
        $this->message = $message;
    }
    
    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }
    
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title("✉️ Новое сообщение")
            ->body("От: {$this->message->sender_name}")
            ->icon('/favicon/favicon-32.png')
            ->badge('/favicon/favicon-32.png')
            ->data([
                'url' => 'https://wotnt.ru/dashboard?tab=teacher',
                'message_id' => $this->message->id,
                'type' => 'teacher_message',
            ])
            ->action('Открыть', 'open');
    }
}