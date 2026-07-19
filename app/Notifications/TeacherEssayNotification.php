<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class TeacherEssayNotification extends Notification
{
    use Queueable;

    protected $studentName;
    protected $lessonTitle;

    public function __construct($studentName, $lessonTitle)
    {
        $this->studentName = $studentName;
        $this->lessonTitle = $lessonTitle;
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('✍️ Новое эссе')
            ->icon('/favicon/icon-192.png')
            ->body("{$this->studentName} отправил(а) эссе к уроку «{$this->lessonTitle}»")
            ->action('Проверить', '/dashboard?tab=teacher')
            ->badge('/favicon/badge-icon.png');
    }
}