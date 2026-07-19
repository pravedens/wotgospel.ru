<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller
{
    /**
     * Сброс пароля
     */
    public function reset(Request $request)
    {
        Log::info('=== RESET PASSWORD REQUEST ===');
        Log::info('Request data:', [
            'email' => $request->email,
            'token' => $request->token ? substr($request->token, 0, 20) . '...' : null,
            'has_password' => !empty($request->password),
            'has_password_confirmation' => !empty($request->password_confirmation),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            Log::error('Reset password validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        Log::info('Validation passed, attempting to reset password');

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                Log::info('Password reset callback executed for user:', [
                    'user_id' => $user->id,
                    'user_email' => $user->email
                ]);
                
                $user->password = Hash::make($password);
                $user->save();
                
                Log::info('User password updated successfully', [
                    'user_id' => $user->id,
                    'user_email' => $user->email
                ]);
                
                event(new PasswordReset($user));
            }
        );

        Log::info('Password reset status', [
            'status' => $status,
            'status_message' => $this->getStatusMessage($status)
        ]);

        if ($status === Password::PASSWORD_RESET) {
            Log::info('Password reset successful');
            return response()->json([
                'message' => 'Пароль успешно изменен'
            ]);
        }

        Log::error('Password reset failed', [
            'status' => $status,
            'email' => $request->email
        ]);

        return response()->json([
            'message' => 'Недействительный токен или email'
        ], 400);
    }

    /**
     * Получить человеко-читаемое сообщение о статусе
     */
    private function getStatusMessage($status)
    {
        $messages = [
            Password::PASSWORD_RESET => 'PASSWORD_RESET - Успешно',
            Password::INVALID_TOKEN => 'INVALID_TOKEN - Недействительный токен',
            Password::INVALID_USER => 'INVALID_USER - Пользователь не найден',
            Password::RESET_THROTTLED => 'RESET_THROTTLED - Слишком много попыток',
        ];
        
        return $messages[$status] ?? 'UNKNOWN_STATUS';
    }
}