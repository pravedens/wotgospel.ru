<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class StudentEnrollmentApprovedNotification extends Notification
{
    use Queueable;

    protected $courseName;

    public function __construct($courseName)
    {
        $this->courseName = $courseName;
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('🎉 Вы зачислены на курс!')
            ->icon('/favicon/icon-192.png')
            ->body("Вы зачислены на курс «{$this->courseName}». Перейдите в личный кабинет.")
            ->action('Перейти', '/dashboard?tab=bibleSchool');
    }
}