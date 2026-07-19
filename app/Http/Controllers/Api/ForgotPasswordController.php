<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    /**
     * Отправка ссылки для сброса пароля
     */
    public function sendResetLink(Request $request)
    {
        Log::info('=== FORGOT PASSWORD REQUEST ===');
        Log::info('Request data:', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            Log::error('Forgot password validation failed', [
                'errors' => $validator->errors()->toArray(),
                'email' => $request->email
            ]);
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        Log::info('Validation passed, sending reset link to:', ['email' => $request->email]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        Log::info('Send reset link status', [
            'status' => $status,
            'email' => $request->email,
            'status_message' => $this->getStatusMessage($status)
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Reset link sent successfully to:', ['email' => $request->email]);
            return response()->json([
                'message' => 'Ссылка для сброса пароля отправлена на ваш email'
            ]);
        }

        Log::error('Failed to send reset link', [
            'email' => $request->email,
            'status' => $status,
            'status_message' => $this->getStatusMessage($status)
        ]);

        return response()->json([
            'message' => 'Не удалось отправить ссылку для сброса пароля'
        ], 500);
    }

    /**
     * Получить человеко-читаемое сообщение о статусе
     */
    private function getStatusMessage($status)
    {
        $messages = [
            Password::RESET_LINK_SENT => 'RESET_LINK_SENT - Ссылка отправлена',
            Password::RESET_THROTTLED => 'RESET_THROTTLED - Слишком много попыток',
            Password::INVALID_USER => 'INVALID_USER - Пользователь не найден',
        ];
        
        return $messages[$status] ?? 'UNKNOWN_STATUS';
    }
}