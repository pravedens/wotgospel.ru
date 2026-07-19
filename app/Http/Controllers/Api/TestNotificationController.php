<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Event;  // ✅ ДОБАВИТЬ ЭТУ СТРОКУ

class TestNotificationController extends Controller
{
    protected $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    public function sendTest(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            // Проверяем, что пользователь может получать уведомления
            if (method_exists($user, 'canReceiveNotifications') && !$user->canReceiveNotifications()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Уведомления доступны только для прихожан церкви'
                ], 403);
            }
            
            // Валидация
            $validated = $request->validate([
                'channel' => 'required|string|in:email,push,webpush',
                'type' => 'required|string|in:new_event,reminder,day_before',
            ]);
            
            Log::info('Test notification request', [
                'user_id' => $user->id,
                'channel' => $validated['channel'],
                'type' => $validated['type']
            ]);
            
            // Создаём тестовое событие
            $testEvent = new Event();
            $testEvent->id = 0;
            $testEvent->title = 'Тестовое событие';
            $testEvent->slug = 'test-event';
            $testEvent->description = 'Это тестовое уведомление для проверки работы системы.';
            $testEvent->startDate = now()->addDay();
            $testEvent->startTime = '19:00:00';
            $testEvent->is_published = true;
            
            // Обработка разных каналов
            switch ($validated['channel']) {
                case 'email':
                    $this->notificationService->sendEmailNotification($user, $testEvent, $validated['type']);
                    return response()->json([
                        'success' => true,
                        'message' => 'Тестовое email уведомление отправлено на ' . $user->email
                    ]);
                    
                case 'push':
                    if (empty($user->phone_for_notifications)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Укажите номер телефона для SMS уведомлений'
                        ], 400);
                    }
                    $this->notificationService->sendPushNotification($user, $testEvent, $validated['type']);
                    return response()->json([
                        'success' => true,
                        'message' => 'Тестовое SMS уведомление отправлено на ' . $user->phone_for_notifications
                    ]);
                    
                case 'webpush':
                    if (!$user->pushSubscriptions()->exists()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Web Push не активен. Нажмите "Включить" в настройках.'
                        ], 400);
                    }
                    $this->notificationService->sendWebPushNotification($user, $testEvent, $validated['type']);
                    return response()->json([
                        'success' => true,
                        'message' => 'Тестовое Web Push уведомление отправлено!'
                    ]);
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Неизвестный канал уведомлений'
                    ], 400);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Test notification error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сервера: ' . $e->getMessage()
            ], 500);
        }
    }
}