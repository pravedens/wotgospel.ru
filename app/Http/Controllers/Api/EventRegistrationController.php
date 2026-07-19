<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\NewRegistrationNotification;
use App\Models\User;

class EventRegistrationController extends Controller
{
    /**
     * Получить регистрацию пользователя на событие
     */
    public function getUserRegistration(Request $request, Event $event)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            $registration = EventRegistration::where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$registration) {
                return response()->json([
                    'success' => true,
                    'registered' => false
                ]);
            }
            
            return response()->json([
                'success' => true,
                'registered' => true,
                'registration' => [
                    'id' => $registration->id,
                    'status' => $registration->status,
                    'selected_service_ids' => $registration->selected_service_ids,
                    'services_count' => $registration->services_count,
                    'created_at' => $registration->created_at,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get user registration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }
    
    /**
     * Зарегистрироваться на конференцию
     */
    public function register(Request $request, Event $event)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Для регистрации необходимо подтвердить email'
                ], 403);
            }
            
            if (!$event->is_conference) {
                return response()->json([
                    'success' => false,
                    'message' => 'Регистрация доступна только для конференций'
                ], 400);
            }
            
            $validated = $request->validate([
                'selected_service_ids' => 'required|array|min:1',
                'selected_service_ids.*' => 'integer|exists:conference_services,id'
            ]);
            
            $selectedServiceIds = $validated['selected_service_ids'];
            
            // Проверка существующих регистраций
            $existing = EventRegistration::where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->first();
                
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Вы уже зарегистрированы на это событие'
                ], 400);
            }
            
            // Проверка свободных мест
            $services = $event->conferenceServices()->whereIn('id', $selectedServiceIds)->get();
            
            foreach ($services as $service) {
                if ($service->capacity > 0) {
                    $registeredCount = EventRegistration::where('event_id', $event->id)
                        ->where('status', 'confirmed')
                        ->whereJsonContains('selected_service_ids', $service->id)
                        ->count();
                        
                    if ($registeredCount >= $service->capacity) {
                        return response()->json([
                            'success' => false,
                            'message' => "На служение \"{$service->title}\" нет свободных мест"
                        ], 400);
                    }
                }
            }
            
            // Создание регистрации
            $registration = EventRegistration::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'selected_service_ids' => $selectedServiceIds,
                'services_count' => count($selectedServiceIds),
                'status' => 'pending',
            ]);
            
            // Отправка уведомлений менеджерам
$managers = User::role('managerEvents')->get();
if ($managers->isNotEmpty()) {
    foreach ($managers as $manager) {
        try {
            $manager->notify(new NewRegistrationNotification($event, $user, $registration));
        } catch (\Exception $e) {
            \Log::error('Failed to send notification to manager ID ' . $manager->id . ': ' . $e->getMessage());
        }
    }
} else {
    \Log::info('No managers with role managerEvents found for notification');
}
            
            return response()->json([
                'success' => true,
                'message' => 'Заявка на регистрацию отправлена',
                'registration' => $registration
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сервера: ' . $e->getMessage()
            ], 500);
        }
    }
    
/**
 * Получить все регистрации пользователя (для личного кабинета)
 */
public function userRegistrations(Request $request)
{
    try {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        $registrations = EventRegistration::where('user_id', $user->id)
            ->with('event.conferenceServices')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $result = [];
        foreach ($registrations as $registration) {
            // Получаем выбранные служения с деталями
            $selectedServices = [];
            $serviceIds = $registration->selected_service_ids ?? [];
            
            if (!empty($serviceIds) && $registration->event && $registration->event->conferenceServices) {
                foreach ($serviceIds as $serviceId) {
                    $service = $registration->event->conferenceServices->firstWhere('id', $serviceId);
                    if ($service) {
                        $selectedServices[] = [
                            'id' => $service->id,
                            'title' => $service->title,
                            'date' => $service->service_date ? $service->service_date->format('d.m.Y') : null,
                            'time' => $service->start_time ? substr($service->start_time, 0, 5) : null,
                            'speaker' => $service->speaker,
                        ];
                    }
                }
            }
            
            $result[] = [
                'id' => $registration->id,
                'event_id' => $registration->event_id,
                'event_title' => $registration->event->title,
                'event_slug' => $registration->event->slug,
                'status' => $registration->status,
                'selected_services' => $selectedServices,
                'created_at' => $registration->created_at ? $registration->created_at->toISOString() : null,
            ];
        }
        
        return response()->json([
            'success' => true,
            'registrations' => $result
        ]);
        
    } catch (\Exception $e) {
        Log::error('User registrations error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ], 500);
    }
}
}