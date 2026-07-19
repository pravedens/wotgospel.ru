<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MinisterMessage;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Notifications\MinisterMessagePushNotification; 

class MinisterMessageController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function send(Request $request, $ministerId)
{
    $minister = User::role('minister')->findOrFail($ministerId);
    
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
        // ✅ АВТОРИЗОВАННЫЙ ПОЛЬЗОВАТЕЛЬ
        $messageData = [
            'minister_id' => $minister->id,
            'user_id' => $user->id,
            'sender_name' => $user->full_name ?? $user->name,
            'sender_email' => $user->email,  // ← берём из профиля
            'message' => strip_tags($request->message),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    } else {
        // ❌ НЕАВТОРИЗОВАННЫЙ ПОЛЬЗОВАТЕЛЬ
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
            'minister_id' => $minister->id,
            'user_id' => null,
            'sender_name' => $request->sender_name ?? 'Гость',
            'sender_email' => $request->sender_email ?? 'guest@example.com',
            'message' => strip_tags($request->message),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }
    
    $message = MinisterMessage::create($messageData);
    
    $this->sendNotificationIfSubscribed($minister, $message);
    
    return response()->json([
        'success' => true,
        'message' => 'Сообщение отправлено'
    ]);
}
    
    // ============================================
    // ПОЛУЧЕНИЕ СООБЩЕНИЙ ДЛЯ СЛУЖИТЕЛЯ
    // ============================================
    
    public function getMessages(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isMinister()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для служителей'
            ], 403);
        }
        
        $messages = MinisterMessage::where('minister_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }
    
    // ============================================
    // ОТМЕТИТЬ СООБЩЕНИЕ КАК ПРОЧИТАННОЕ
    // ============================================
    
    public function markAsRead(Request $request, $messageId)
    {
        $user = $request->user();
        
        if (!$user->isMinister()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для служителей'
            ], 403);
        }
        
        $message = MinisterMessage::where('id', $messageId)
            ->where('minister_id', $user->id)
            ->firstOrFail();
        
        $message->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'Сообщение отмечено прочитанным'
        ]);
    }
    
    // ============================================
    // КОЛИЧЕСТВО НЕПРОЧИТАННЫХ СООБЩЕНИЙ
    // ============================================
    
    public function getUnreadCount(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isMinister()) {
            return response()->json(['count' => 0]);
        }
        
        $count = MinisterMessage::where('minister_id', $user->id)
            ->where('is_read', false)
            ->count();
        
        return response()->json(['count' => $count]);
    }
    
    // ============================================
    // ПРОВЕРКА КАПЧИ
    // ============================================
    
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
    
    /**
 * Отправить уведомление служителю, если он подписан
 */
protected function sendNotificationIfSubscribed(User $minister, MinisterMessage $message): void
{
    // Email уведомление
    if ($minister->notify_minister_messages_email) {
        $this->notificationService->sendMinisterEmailNotification($minister, $message);
    }
    
    // Web Push уведомление (через браузер)
    if ($minister->notify_minister_messages_webpush) {
        $this->sendWebPushNotification($minister, $message);
    }
}

/**
 * Отправить Web Push уведомление
 */
protected function sendWebPushNotification(User $minister, MinisterMessage $message): void
{
    if (!$minister->pushSubscriptions()->exists()) {
        return;
    }
    
    try {
        $minister->notify(new MinisterMessagePushNotification($message));
        Log::info("WebPush sent to minister {$minister->id} for message {$message->id}");
    } catch (\Exception $e) {
        Log::error("Failed to send WebPush: " . $e->getMessage());
    }
}

// ============================================
// УДАЛЕНИЕ СООБЩЕНИЯ
// ============================================

public function destroy(Request $request, $messageId)
{
    $user = $request->user();
    
    if (!$user->isMinister()) {
        return response()->json([
            'success' => false,
            'message' => 'Доступ только для служителей'
        ], 403);
    }
    
    $message = MinisterMessage::where('id', $messageId)
        ->where('minister_id', $user->id)
        ->firstOrFail();
    
    // Удаляем связанные логи уведомлений
    $message->notificationLogs()->delete();
    
    // Удаляем само сообщение
    $message->delete();
    
    return response()->json([
        'success' => true,
        'message' => 'Сообщение удалено'
    ]);
}
}