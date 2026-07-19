<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TeacherMessage;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Notifications\TeacherMessagePushNotification;

class TeacherMessageController extends Controller
{
    protected $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    /**
     * Отправить сообщение учителю (публичный маршрут)
     */
    public function send(Request $request, $teacherId)
    {
        $teacher = User::role('teacher')->findOrFail($teacherId);
        
        $validator = Validator::make($request->all(), [
            'sender_name' => 'sometimes|string|max:255',
            'sender_email' => 'sometimes|email|max:255',
            'message' => 'required|string|max:5000',
            'captcha_token' => 'sometimes|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        
        if ($user) {
            $messageData = [
                'teacher_id' => $teacher->id,
                'user_id' => $user->id,
                'sender_name' => $user->full_name ?? $user->name,
                'sender_email' => $user->email,
                'message' => strip_tags($request->message),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];
        } else {
            if ($request->has('captcha_token')) {
                $captchaValid = $this->verifyCaptcha($request->captcha_token);
                if (!$captchaValid) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Проверка капчи не пройдена'
                    ], 422);
                }
            }
            
            $messageData = [
                'teacher_id' => $teacher->id,
                'user_id' => null,
                'sender_name' => $request->sender_name ?? 'Гость',
                'sender_email' => $request->sender_email ?? 'guest@example.com',
                'message' => strip_tags($request->message),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];
        }
        
        $message = TeacherMessage::create($messageData);
        
        $this->sendNotificationIfSubscribed($teacher, $message);
        
        return response()->json([
            'success' => true,
            'message' => 'Сообщение отправлено',
            'data' => [
                'id' => $message->id,
                'created_at' => $message->created_at,
            ]
        ]);
    }
    
    /**
     * Получить сообщения для учителя (авторизованный маршрут)
     */
    public function getMessages(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('teacher')) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для учителей'
            ], 403);
        }
        
        $messages = TeacherMessage::where('teacher_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }
    
    /**
     * Отметить сообщение как прочитанное
     */
    public function markAsRead(Request $request, $messageId)
    {
        $user = $request->user();
        
        if (!$user->hasRole('teacher')) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для учителей'
            ], 403);
        }
        
        $message = TeacherMessage::where('id', $messageId)
            ->where('teacher_id', $user->id)
            ->firstOrFail();
        
        $message->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'Сообщение отмечено прочитанным'
        ]);
    }
    
    /**
     * Получить количество непрочитанных сообщений
     */
    public function getUnreadCount(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('teacher')) {
            return response()->json(['count' => 0]);
        }
        
        $count = TeacherMessage::where('teacher_id', $user->id)
            ->where('is_read', false)
            ->count();
        
        return response()->json(['count' => $count]);
    }
    
    /**
     * Удалить сообщение
     */
    public function destroy(Request $request, $messageId)
    {
        $user = $request->user();
        
        if (!$user->hasRole('teacher')) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для учителей'
            ], 403);
        }
        
        $message = TeacherMessage::where('id', $messageId)
            ->where('teacher_id', $user->id)
            ->firstOrFail();
        
        $message->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Сообщение удалено'
        ]);
    }
    
    /**
     * Отправить уведомления учителю
     */
    protected function sendNotificationIfSubscribed(User $teacher, TeacherMessage $message): void
{
    \Log::info('=== sendNotificationIfSubscribed (Teacher) ===', [
        'teacher_id' => $teacher->id,
        'notify_email_raw' => $teacher->notify_teacher_messages_email,
        'notify_email_cast' => (bool) $teacher->notify_teacher_messages_email,
        'notify_email_value' => $teacher->getAttribute('notify_teacher_messages_email'),
    ]);
    
    if ($teacher->notify_teacher_messages_email) {
        $this->notificationService->sendTeacherEmailNotification($teacher, $message);
    }
    
    if ($teacher->notify_teacher_messages_webpush) {
        $this->notificationService->sendTeacherWebPushNotification($teacher, $message);
    }
}
    
    /**
     * Отправить email уведомление
     */
    protected function sendEmailNotification(User $teacher, TeacherMessage $message): void
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
            
            \Illuminate\Support\Facades\Mail::send('emails.teacher-message', $data, function ($mail) use ($teacher, $subject) {
                $mail->to($teacher->email, $teacher->full_name ?? $teacher->name)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            Log::info("Teacher email sent to {$teacher->email}", [
                'message_id' => $message->id
            ]);
            
        } catch (\Exception $e) {
            Log::error("Teacher email failed: " . $e->getMessage());
        }
    }
    
    /**
     * Отправить Web Push уведомление
     */
    protected function sendWebPushNotification(User $teacher, TeacherMessage $message): void
    {
        if (!$teacher->pushSubscriptions()->exists()) {
            return;
        }
        
        try {
            $teacher->notify(new TeacherMessagePushNotification($message));
            Log::info("WebPush sent to teacher {$teacher->id} for message {$message->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send WebPush to teacher: " . $e->getMessage());
        }
    }
    
    /**
     * Проверка Yandex SmartCaptcha
     */
    private function verifyCaptcha($token)
    {
        $secretKey = config('services.yandex.captcha.secret_key');
        
        if (!$secretKey) {
            Log::warning('Yandex Captcha secret key not configured');
            return true;
        }
        
        try {
            $response = Http::asForm()->post('https://smartcaptcha.cloud.yandex.ru/validate', [
                'secret' => $secretKey,
                'token' => $token,
                'ip' => request()->ip(),
            ]);
            
            $data = $response->json();
            
            return isset($data['status']) && $data['status'] === 'ok';
        } catch (\Exception $e) {
            Log::error('Captcha verification failed: ' . $e->getMessage());
            return true;
        }
    }
}