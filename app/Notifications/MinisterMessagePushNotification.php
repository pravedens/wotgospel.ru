<?php
// app/Notifications/MinisterMessagePushNotification.php

namespace App\Notifications;

use App\Models\MinisterMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class MinisterMessagePushNotification extends Notification
{
    use Queueable;
    
    protected $message;
    
    public function __construct(MinisterMessage $message)
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
            ->title('✉️ Новое сообщение')
            ->body("От {$this->message->sender_name}: " . substr($this->message->message, 0, 100))
            ->icon('/favicon/android-chrome-192x192.png')
            ->badge('/favicon/favicon-32.png')
            ->tag('minister-message')
            ->renotify(true)
            ->data(['url' => '/dashboard']);
    }
}