// app/Notifications/CertificateIssuedNotification.php

<?php

namespace App\Notifications;

use App\Models\BibleCourse;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class CertificateIssuedNotification extends Notification
{
    use Queueable;

    protected $course;

    public function __construct(BibleCourse $course)
    {
        $this->course = $course;
    }

    public function via($notifiable)
    {
        $channels = [];
        
        if ($notifiable->wantsNotification('certificate_issued', 'email')) {
            $channels[] = 'mail';
        }
        if ($notifiable->wantsNotification('certificate_issued', 'webpush')) {
            $channels[] = WebPushChannel::class;
        }
        
        return $channels;
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('🎓 Поздравляем с окончанием курса!')
            ->greeting('Здравствуйте, ' . $notifiable->full_name . '!')
            ->line('Поздравляем! Вы успешно завершили курс «' . $this->course->title . '».')
            ->line('Ваш сертификат готов. Вы можете скачать его в личном кабинете.')
            ->action('Скачать сертификат', config('app.frontend_url') . '/dashboard?tab=bibleSchool')
            ->salutation('С уважением, команда ' . config('app.name'));
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('🎓 Поздравляем с окончанием курса!')
            ->body('Вы успешно завершили курс «' . $this->course->title . '». Сертификат готов!')
            ->icon('/favicon/icon-192.png')
            ->badge('/favicon/favicon-32.png')
            ->data([
                'url' => config('app.frontend_url') . '/dashboard?tab=bibleSchool',
                'type' => 'certificate_issued',
                'course_title' => $this->course->title,
            ]);
    }
}