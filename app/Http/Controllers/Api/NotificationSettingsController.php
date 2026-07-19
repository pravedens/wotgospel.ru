<?php
// app/Http/Controllers/Api/NotificationSettingsController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationSettingsController extends Controller
{
    public function getSettings(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'settings' => $user->notification_settings,
        ]);
    }
    
    public function updateSettings(Request $request)
    {
        $user = $request->user();
        
        if (is_null($user->email_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Для настройки уведомлений необходимо подтвердить email'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            // События
            'notify_new_events_email' => 'boolean',
            'notify_new_events_push' => 'boolean',
            'notify_new_events_webpush' => 'boolean',
            'notify_event_reminder_email' => 'boolean',
            'notify_event_reminder_push' => 'boolean',
            'notify_event_reminder_webpush' => 'boolean',
            'notify_event_day_email' => 'boolean',
            'notify_event_day_push' => 'boolean',
            'notify_event_day_webpush' => 'boolean',
            
            // Библейская школа
            'notify_enrollment_rejected_email' => 'boolean',
            'notify_enrollment_rejected_webpush' => 'boolean',
            'notify_certificate_issued_email' => 'boolean',
            'notify_certificate_issued_webpush' => 'boolean',
            
            // Общие
            'phone_for_notifications' => 'nullable|string|max:20',
            'consent_given' => 'required|accepted',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // События
        $user->notify_new_events_email = $request->notify_new_events_email ?? false;
        $user->notify_new_events_push = $request->notify_new_events_push ?? false;
        $user->notify_new_events_webpush = $request->notify_new_events_webpush ?? false;
        
        $user->notify_event_reminder_email = $request->notify_event_reminder_email ?? false;
        $user->notify_event_reminder_push = $request->notify_event_reminder_push ?? false;
        $user->notify_event_reminder_webpush = $request->notify_event_reminder_webpush ?? false;
        
        $user->notify_event_day_email = $request->notify_event_day_email ?? false;
        $user->notify_event_day_push = $request->notify_event_day_push ?? false;
        $user->notify_event_day_webpush = $request->notify_event_day_webpush ?? false;
        
        // Библейская школа
        $user->notify_enrollment_rejected_email = $request->notify_enrollment_rejected_email ?? false;
        $user->notify_enrollment_rejected_webpush = $request->notify_enrollment_rejected_webpush ?? false;
        $user->notify_certificate_issued_email = $request->notify_certificate_issued_email ?? false;
        $user->notify_certificate_issued_webpush = $request->notify_certificate_issued_webpush ?? false;
        
        if ($request->has('phone_for_notifications')) {
            $user->phone_for_notifications = $request->phone_for_notifications;
        }
        
        if ($request->consent_given && !$user->notification_consent_given_at) {
            $result = $user->giveNotificationConsent($request->ip());
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 403);
            }
        }
        
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Настройки уведомлений обновлены',
            'settings' => $user->notification_settings,
        ]);
    }
}