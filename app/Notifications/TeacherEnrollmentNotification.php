<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class TeacherEnrollmentNotification extends Notification
{
    use Queueable;

    protected $userName;

    public function __construct($userName)
    {
        $this->userName = $userName;
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('📋 Новая заявка на обучение')
            ->icon('/favicon/icon-192.png')
            ->body("{$this->userName} подал(а) заявку на обучение")
            ->action('Перейти к заявкам', '/dashboard?tab=teacher')
            ->badge('/favicon/badge-icon.png');
    }
}