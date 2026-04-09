<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class CustomVerifyEmailNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Подтверждение email адреса')
            ->greeting('Здравствуйте, ' . $notifiable->name . '!')
            ->line('Благодарим вас за регистрацию на нашем сайте.')
            ->line('Для подтверждения вашего email адреса, пожалуйста, нажмите кнопку ниже:')
            ->action('Подтвердить email', $verificationUrl)
            ->line('Если вы не регистрировались на нашем сайте, просто проигнорируйте это письмо.')
            ->line('Ссылка действительна в течение 60 минут.')
            ->salutation('С уважением, администрация сайта');
    }

    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'filament.admin.auth.email-verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}