<?php
// app/Services/NotificationService.php

namespace App\Services;

use App\Models\User;
use App\Models\Event;
use App\Models\BibleLesson;
use App\Models\BibleCourse; 
use App\Models\BibleEssay;
use App\Models\MinisterMessage;
use App\Models\EventNotificationLog;
use App\Models\MinisterMessageNotificationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Notifications\EventPushNotification;
use App\Notifications\MinisterMessageNotification;

class NotificationService
{
    /**
     * Отправить email уведомление
     */
    public function sendEmailNotification(User $user, Event $event, string $type): void
    {
        $log = new EventNotificationLog([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'type' => $type,
            'channel' => 'email',
            'status' => 'pending',
        ]);
        $log->save();
        
        try {
            $formattedDate = $event->startDate 
                ? Carbon::parse($event->startDate)->translatedFormat('d F Y')
                : 'Дата не указана';
            
            $formattedTime = $event->startTime 
                ? Carbon::parse($event->startTime)->format('H:i')
                : null;
            
            $data = [
                'user' => $user,
                'event' => $event,
                'type' => $type,
                'formattedDate' => $formattedDate,
                'formattedTime' => $formattedTime,
                'eventUrl' => "https://wotnt.ru/events/{$event->slug}",
                'siteUrl' => 'https://wotnt.ru',
                'churchName' => 'Церковь "Слово Истины"',
                'year' => date('Y'),
            ];
            
            $subject = $this->getEmailSubject($type, $event);
            $template = $this->getEmailView($type);
            
            Mail::send($template, $data, function ($message) use ($user, $subject) {
                $message->to($user->email, $user->getFullNameAttribute() ?? $user->name)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            $log->update(['status' => 'sent', 'sent_at' => now()]);
            
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }
    
    /**
     * Получить тему письма в зависимости от типа
     */
    protected function getEmailSubject(string $type, Event $event): string
    {
        return match($type) {
            'new_event' => "🆕 Новое событие: {$event->title}",
            'reminder' => "🔔 Напоминание: {$event->title} — сегодня!",
            'day_before' => "📅 Напоминание: {$event->title} — завтра",
            default => $event->title,
        };
    }
    
    /**
     * Получить путь к шаблону в зависимости от типа
     */
    protected function getEmailView(string $type): string
    {
        return match($type) {
            'new_event' => 'emails.events.new',
            'reminder' => 'emails.events.reminder',
            'day_before' => 'emails.events.day-before',
            default => 'emails.events.default',
        };
    }
    
    /**
     * Отправить SMS уведомление
     */
    public function sendPushNotification(User $user, Event $event, string $type): void
    {
        if (empty($user->phone_for_notifications)) {
            return;
        }
        
        $log = new EventNotificationLog([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'type' => $type,
            'channel' => 'push',
            'status' => 'pending',
        ]);
        $log->save();
        
        try {
            $message = $this->getPushMessage($type, $event);
            
            Http::timeout(30)->post('https://your-sms-gateway.com/send', [
                'phone' => $user->phone_for_notifications,
                'message' => $message,
                'api_key' => config('services.sms.api_key', env('SMS_API_KEY')),
                'sender' => config('services.sms.sender', 'WoTNT'),
            ]);
            
            $log->update(['status' => 'sent', 'sent_at' => now()]);
            
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }
    
    /**
     * Получить текст SMS сообщения
     */
    protected function getPushMessage(string $type, Event $event): string
    {
        $time = $event->startTime ? Carbon::parse($event->startTime)->format('H:i') : '';
        $date = $event->startDate ? Carbon::parse($event->startDate)->format('d.m') : '';
        
        return match($type) {
            'new_event' => "🆕 Новое событие: {$event->title}. Дата: {$date} {$time}. https://wotnt.ru/events/{$event->slug}",
            'reminder' => "🔔 Напоминаем: {$event->title} сегодня в {$time}. https://wotnt.ru/events/{$event->slug}",
            'day_before' => "📅 Напоминание: {$event->title} завтра в {$time}. https://wotnt.ru/events/{$event->slug}",
            'cancellation' => "❌ Событие отменено: {$event->title} ({$date}). Приносим извинения. https://wotnt.ru/events/{$event->slug}",
            default => "{$event->title}. https://wotnt.ru/events/{$event->slug}",
        };
    }
    
    /**
     * Отправить Web Push уведомление (через браузер)
     */
    public function sendWebPushNotification(User $user, Event $event, string $type): void
    {
        if (!$user->pushSubscriptions()->exists()) {
            return;
        }
        
        $log = new EventNotificationLog([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'type' => $type,
            'channel' => 'webpush',
            'status' => 'pending',
        ]);
        $log->save();
        
        try {
            $user->notify(new EventPushNotification($event, $type));
            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }
    
    /**
     * Отправить уведомление о новом событии всем подписанным пользователям
     * с учётом прав доступа к событию (members_only, ministers_only)
     */
    public function notifyNewEvent(Event $event): void
    {
        try {
            $users = User::query()
                ->canReceiveNotifications()
                ->whereNotNull('notification_consent_given_at')
                ->get();
            
            foreach ($users as $user) {
                if (!$user->canReceiveNotificationForEvent($event)) {
                    continue;
                }
                
                $alreadySent = EventNotificationLog::where('user_id', $user->id)
                    ->where('event_id', $event->id)
                    ->where('type', 'new_event')
                    ->where('status', 'sent')
                    ->exists();
                
                if ($alreadySent) {
                    continue;
                }
                
                if ($user->wantsNotification('new_event', 'email')) {
                    $this->sendEmailNotification($user, $event, 'new_event');
                }
                if ($user->wantsNotification('new_event', 'push')) {
                    $this->sendPushNotification($user, $event, 'new_event');
                }
                if ($user->wantsNotification('new_event', 'webpush')) {
                    $this->sendWebPushNotification($user, $event, 'new_event');
                }
            }
            
        } catch (\Exception $e) {
            // Ошибка логируется системой
        }
    }
    
    /**
     * Отправить напоминания за день до события (только для тех, кто нажал «Я приду»)
     */
    public function sendDayBeforeReminders(): void
    {
        try {
            $tomorrow = now()->addDay()->startOfDay();
            $dayAfterTomorrow = now()->addDays(2)->startOfDay();
            
            $events = Event::whereDate('startDate', '>=', $tomorrow)
                ->whereDate('startDate', '<', $dayAfterTomorrow)
                ->where('is_published', true)
                ->where('startDate', '>=', now())
                ->get();
            
            foreach ($events as $event) {
                $attendees = $event->getAttendingUsers()->get();
                
                foreach ($attendees as $user) {
                    if (!$user->canReceiveNotificationForEvent($event)) {
                        continue;
                    }
                    
                    $alreadySent = EventNotificationLog::where('user_id', $user->id)
                        ->where('event_id', $event->id)
                        ->where('type', 'day_before')
                        ->whereDate('created_at', now()->toDateString())
                        ->exists();
                    
                    if ($alreadySent) {
                        continue;
                    }
                    
                    if ($user->wantsNotification('day_before', 'email')) {
                        $this->sendEmailNotification($user, $event, 'day_before');
                    }
                    if ($user->wantsNotification('day_before', 'push')) {
                        $this->sendPushNotification($user, $event, 'day_before');
                    }
                    if ($user->wantsNotification('day_before', 'webpush')) {
                        $this->sendWebPushNotification($user, $event, 'day_before');
                    }
                }
            }
            
        } catch (\Exception $e) {
            // Ошибка логируется системой
        }
    }
    
    /**
     * Отправить напоминания в день события (за 2 часа до начала) — только для тех, кто нажал «Я приду»
     */
    public function sendDayOfEventReminders(): void
    {
        try {
            $now = now();
            
            $startWindow = $now->copy()->addHours(1.5);
            $endWindow = $now->copy()->addHours(2.5);
            
            $events = Event::where('is_published', true)
                ->whereDate('startDate', '>=', $now->toDateString())
                ->whereRaw("CONCAT(startDate, ' ', startTime) BETWEEN ? AND ?", [
                    $startWindow->toDateTimeString(),
                    $endWindow->toDateTimeString()
                ])
                ->get();
            
            foreach ($events as $event) {
                $attendees = $event->getAttendingUsers()->get();
                
                foreach ($attendees as $user) {
                    if (!$user->canReceiveNotificationForEvent($event)) {
                        continue;
                    }
                    
                    $alreadySent = EventNotificationLog::where('user_id', $user->id)
                        ->where('event_id', $event->id)
                        ->where('type', 'reminder')
                        ->whereDate('created_at', now()->toDateString())
                        ->exists();
                    
                    if ($alreadySent) {
                        continue;
                    }
                    
                    if ($user->wantsNotification('reminder', 'email')) {
                        $this->sendEmailNotification($user, $event, 'reminder');
                    }
                    if ($user->wantsNotification('reminder', 'push')) {
                        $this->sendPushNotification($user, $event, 'reminder');
                    }
                    if ($user->wantsNotification('reminder', 'webpush')) {
                        $this->sendWebPushNotification($user, $event, 'reminder');
                    }
                }
            }
            
        } catch (\Exception $e) {
            // Ошибка логируется системой
        }
    }
    
    /**
     * Отправить уведомление служителю о новом сообщении
     */
    public function sendMinisterMessageNotification(MinisterMessage $message): void
    {
        $minister = $message->minister;
        
        if (!$minister || !$minister->email) {
            return;
        }
        
        $alreadySent = MinisterMessageNotificationLog::where('message_id', $message->id)
            ->where('type', 'new_message')
            ->where('status', 'sent')
            ->exists();
        
        if ($alreadySent) {
            return;
        }
        
        $log = new MinisterMessageNotificationLog([
            'message_id' => $message->id,
            'minister_id' => $minister->id,
            'type' => 'new_message',
            'channel' => 'email',
            'status' => 'pending',
        ]);
        $log->save();
        
        try {
            $minister->notify(new MinisterMessageNotification($message));
            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 500)
            ]);
        }
    }
    
    /**
     * Отправить уведомление служителю о новом сообщении (email через Mail)
     */
    public function sendMinisterEmailNotification(User $minister, MinisterMessage $message): void
    {
        $log = new MinisterMessageNotificationLog([
            'message_id' => $message->id,
            'minister_id' => $minister->id,
            'type' => 'new_message',
            'channel' => 'email',
            'status' => 'pending',
        ]);
        $log->save();
        
        try {
            $data = [
                'minister' => $minister,
                'message' => $message,
                'sender_name' => $message->sender_name,
                'sender_email' => $message->sender_email,
                'messageText' => $message->message,
                'siteUrl' => 'https://wotnt.ru',
                'dashboardUrl' => 'https://wotnt.ru/dashboard',
                'churchName' => 'Церковь "Слово Истины"',
                'year' => date('Y'),
            ];
            
            $subject = "✉️ Новое сообщение от {$message->sender_name}";
            
            Mail::send('emails.minister-message', $data, function ($mail) use ($minister, $subject) {
                $mail->to($minister->email, $minister->full_name ?? $minister->name)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 500)
            ]);
        }
    }
    
    /**
     * Получить статистику по отправленным уведомлениям служителям
     */
    public function getMinisterNotificationStats(int $ministerId, ?string $channel = null): array
    {
        $query = MinisterMessageNotificationLog::where('minister_id', $ministerId);
        
        if ($channel) {
            $query->where('channel', $channel);
        }
        
        return [
            'total' => $query->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
        ];
    }
    
    /**
     * Отправить уведомления об отмене события всем, кто нажал «Я приду»
     */
    public function sendEventCancellationNotifications(Event $event): void
    {
        try {
            $attendees = $event->getAttendingUsers()->get();
            
            if ($attendees->isEmpty()) {
                return;
            }
            
            foreach ($attendees as $user) {
                try {
                    if ($user->wantsNotification('reminder', 'email')) {
                        $user->notify(new \App\Notifications\EventCancellationNotification($event));
                    }
                    
                    if ($user->wantsNotification('reminder', 'webpush')) {
                        $user->notify(new \App\Notifications\EventCancellationNotification($event));
                    }
                    
                    if ($user->wantsNotification('reminder', 'push') && !empty($user->phone_for_notifications)) {
                        $this->sendPushNotification($user, $event, 'cancellation');
                    }
                } catch (\Exception $e) {
                    // Ошибка логируется системой
                }
            }
            
        } catch (\Exception $e) {
            // Ошибка логируется системой
        }
    }
    
    /**
     * Отправить уведомление учителю о новом сообщении (email через Mail)
     */
    public function sendTeacherEmailNotification(User $teacher, $message): void
    {
        try {
            $data = [
                'teacher' => $teacher,
                'message' => $message,
                'sender_name' => $message->sender_name,
                'sender_email' => $message->sender_email,
                'messageText' => $message->message,
                'siteUrl' => 'https://wotnt.ru',
                'dashboardUrl' => 'https://wotnt.ru/dashboard?tab=teacher',
                'churchName' => 'Церковь "Слово Истины"',
                'year' => date('Y'),
            ];
            
            $subject = "✉️ Новое сообщение от {$message->sender_name}";
            
            Mail::send('emails.teacher-message', $data, function ($mail) use ($teacher, $subject) {
                $mail->to($teacher->email, $teacher->full_name ?? $teacher->name)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Exception $e) {
            // Ошибка логируется системой
        }
    }
    
    /**
     * Отправить Web Push уведомление учителю
     */
    public function sendTeacherWebPushNotification(User $teacher, $message): void
    {
        if (!$teacher->pushSubscriptions()->exists()) {
            return;
        }
        
        try {
            $teacher->notify(new \App\Notifications\TeacherMessagePushNotification($message));
        } catch (\Exception $e) {
            // Ошибка логируется системой
        }
    }
    
    /**
     * Отправить уведомление учителям о новой заявке на обучение
     */
    public function notifyTeachersAboutEnrollment($user, $request, $courseId = null): void
    {
        if (!$courseId) {
            return;
        }
        
        $teachers = \App\Models\User::whereHas('themes', function($q) use ($courseId) {
            $q->where('course_id', $courseId);
        })->get();
        
        if ($teachers->isEmpty()) {
            return;
        }
        
        $messageText = "Пользователь {$user->full_name} подал заявку на обучение.\n";
        if ($request->city) $messageText .= "Город: {$request->city}\n";
        if ($request->church_name) $messageText .= "Церковь: {$request->church_name}\n";
        if ($request->phone) $messageText .= "Телефон: {$request->phone}\n";
        if ($request->ministry) $messageText .= "Служение: {$request->ministry}";
        $messageText = strip_tags($messageText);
        
        foreach ($teachers as $teacher) {
            if ($teacher->notify_teacher_messages_email) {
                $this->sendTeacherEnrollmentEmail($teacher, $user, $messageText);
            }
            if ($teacher->notify_teacher_messages_webpush) {
                $this->sendTeacherEnrollmentWebPush($teacher, $user);
            }
        }
    }
    
    /**
     * Отправить email учителю о новой заявке
     */
    public function sendTeacherEnrollmentEmail(User $teacher, $user, string $messageText): void
    {
        try {
            $data = [
                'teacher' => $teacher,
                'sender_name' => $user->full_name,
                'sender_email' => $user->email,
                'messageText' => $messageText,
                'siteUrl' => 'https://wotnt.ru',
                'dashboardUrl' => 'https://wotnt.ru/dashboard?tab=teacher',
                'churchName' => 'Церковь "Слово Истины"',
                'year' => date('Y'),
            ];
            
            $subject = "📋 Новая заявка на обучение от {$user->full_name}";
            
            Mail::send('emails.teacher-enrollment', $data, function ($mail) use ($teacher, $subject) {
                $mail->to($teacher->email, $teacher->full_name ?? $teacher->name)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Exception $e) {
            // Ошибка логируется системой
        }
    }
    
    /**
     * Отправить Web Push учителю о новой заявке
     */
    public function sendTeacherEnrollmentWebPush(User $teacher, $user): void
    {
        if (!$teacher->pushSubscriptions()->exists()) {
            return;
        }
        
        try {
            $teacher->notify(new \App\Notifications\TeacherEnrollmentNotification($user->full_name));
        } catch (\Exception $e) {
            // Ошибка логируется системой
        }
    }
    
    /**
     * Отправить email учителю о новом эссе
     */
    public function sendTeacherEssayNotification(User $teacher, User $student, BibleLesson $lesson, BibleEssay $essay): void
    {
        try {
            $data = [
                'teacher' => $teacher,
                'student_name' => $student->full_name,
                'student_email' => $student->email,
                'lesson_title' => $lesson->title,
                'essay_preview' => mb_substr(strip_tags($essay->content), 0, 200) . '...',
                'essay_id' => $essay->id,
                'dashboardUrl' => config('app.frontend_url') . '/dashboard?tab=teacher',
                'churchName' => 'Церковь "Слово Истины"',
                'year' => date('Y'),
            ];
            
            $subject = "✍️ Новое эссе от {$student->full_name} к уроку «{$lesson->title}»";
            
            Mail::send('emails.teacher-essay', $data, function ($mail) use ($teacher, $subject) {
                $mail->to($teacher->email, $teacher->full_name ?? $teacher->name)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Exception $e) {
            // Ошибка логируется системой
        }
    }
    
    /**
     * Отправить WebPush учителю о новом эссе
     */
    public function sendTeacherEssayWebPush(User $teacher, User $student, BibleLesson $lesson): void
    {
        if (!$teacher->pushSubscriptions()->exists()) {
            return;
        }
        
        try {
            $teacher->notify(new \App\Notifications\TeacherEssayNotification($student->full_name, $lesson->title));
        } catch (\Exception $e) {
            // Ошибка логируется системой
        }
    }
    
    /**
     * Отправить ученику уведомление об одобрении заявки
     */
    public function sendStudentEnrollmentApprovedNotification(User $student, ?BibleCourse $course): void
    {
        $courseName = $course ? $course->title : 'обучение';
        $dashboardUrl = config('app.frontend_url') . '/dashboard?tab=bibleSchool';
        
        if ($student->email_verified_at) {
            try {
                $data = [
                    'student_name' => $student->full_name,
                    'course_name' => $courseName,
                    'dashboardUrl' => $dashboardUrl,
                    'year' => date('Y'),
                    'churchName' => 'Церковь "Слово Истины"',
                ];
                
                Mail::send('emails.student-enrollment-approved', $data, function ($mail) use ($student) {
                    $mail->to($student->email, $student->full_name)
                        ->subject('🎉 Вы зачислены на обучение!')
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
            } catch (\Exception $e) {
                // Ошибка логируется системой
            }
        }
        
        if ($student->pushSubscriptions()->exists()) {
            try {
                $student->notify(new \App\Notifications\StudentEnrollmentApprovedNotification($courseName));
            } catch (\Exception $e) {
                // Ошибка логируется системой
            }
        }
    }
    
    /**
     * Отправить ученику уведомление о проверке эссе
     */
    public function sendEssayReviewedNotification(User $student, BibleLesson $lesson, int $score, string $feedback, string $status): void
    {
        $dashboardUrl = config('app.frontend_url') . '/bible-school/lessons/' . $lesson->slug;
        $isApproved = $status === 'approved';
        $subject = $isApproved ? '✅ Ваше эссе проверено' : '❌ Ваше эссе требует доработки';
        
        if ($student->email_verified_at) {
            try {
                $data = [
                    'student_name' => $student->full_name,
                    'lesson_title' => $lesson->title,
                    'score' => $score,
                    'feedback' => $feedback,
                    'is_approved' => $isApproved,
                    'dashboardUrl' => $dashboardUrl,
                    'year' => date('Y'),
                    'churchName' => 'Церковь "Слово Истины"',
                ];
                
                Mail::send('emails.student-essay-reviewed', $data, function ($mail) use ($student, $subject) {
                    $mail->to($student->email, $student->full_name)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
            } catch (\Exception $e) {
                // Ошибка логируется системой
            }
        }
        
        if ($student->pushSubscriptions()->exists()) {
            try {
                $student->notify(new \App\Notifications\StudentEssayReviewedNotification($lesson->title, $score, $isApproved));
            } catch (\Exception $e) {
                // Ошибка логируется системой
            }
        }
    }
    
    /**
     * Отправить уведомление об отклонении заявки
     */
    public function sendStudentEnrollmentRejectedNotification(User $student): void
    {
        try {
            $student->notify(new \App\Notifications\StudentEnrollmentRejectedNotification());
        } catch (\Exception $e) {
            // Ошибка логируется системой
        }
    }
    
    /**
     * Отправить уведомление о выдаче сертификата
     */
    public function sendCertificateIssuedNotification(User $student, BibleCourse $course): void
    {
        try {
            $student->notify(new \App\Notifications\CertificateIssuedNotification($course));
        } catch (\Exception $e) {
            // Ошибка логируется системой
        }
    }
}