<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class StudentEnrollmentRejectedNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        $channels = [];
        
        if ($notifiable->wantsNotification('enrollment_rejected', 'email')) {
            $channels[] = 'mail';
        }
        if ($notifiable->wantsNotification('enrollment_rejected', 'webpush')) {
            $channels[] = WebPushChannel::class;
        }
        
        return $channels;
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('❌ Заявка на обучение отклонена')
            ->greeting('Здравствуйте, ' . $notifiable->full_name . '!')
            ->line('К сожалению, ваша заявка на обучение в библейской школе была отклонена.')
            ->line('Если вы считаете, что это ошибка, пожалуйста, свяжитесь с администратором.')
            ->action('Перейти на сайт', config('app.frontend_url'))
            ->salutation('С уважением, команда ' . config('app.name'));
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('❌ Заявка отклонена')
            ->body('Ваша заявка на обучение отклонена. Свяжитесь с администратором.')
            ->icon('/favicon/icon-192.png')
            ->badge('/favicon/favicon-32.png')
            ->data([
                'url' => config('app.frontend_url') . '/bible-school',
                'type' => 'enrollment_rejected',
            ]);
    }
}