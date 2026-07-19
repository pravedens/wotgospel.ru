<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class StudentEssayReviewedNotification extends Notification
{
    use Queueable;

    protected $lessonTitle;
    protected $score;
    protected $isApproved;

    public function __construct($lessonTitle, $score, $isApproved)
    {
        $this->lessonTitle = $lessonTitle;
        $this->score = $score;
        $this->isApproved = $isApproved;
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        $title = $this->isApproved ? '✅ Эссе одобрено!' : '❌ Эссе требует доработки';
        $body = $this->isApproved 
            ? "Ваше эссе к уроку «{$this->lessonTitle}» одобрено. Оценка: {$this->score}/100"
            : "Ваше эссе к уроку «{$this->lessonTitle}» требует доработки. Ознакомьтесь с отзывом учителя.";
        
        return (new WebPushMessage)
            ->title($title)
            ->icon('/favicon/icon-192.png')
            ->body($body)
            ->action('Перейти к уроку', "/bible-school/lessons/{$this->lessonTitle}");
    }
}