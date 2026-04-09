<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    /**
     * Обработка верификации email через подписанную ссылку
     */
    public function verify(Request $request, $id, $hash)
    {
        // Находим пользователя по ID из URL
        $user = User::find($id);

        // Проверяем, существует ли пользователь
        if (!$user) {
            return redirect()->away($this->getRedirectUrl('error', 'Пользователь не найден'));
        }

        // Проверяем, не верифицирован ли уже email
        if ($user->hasVerifiedEmail()) {
            return redirect()->away($this->getRedirectUrl('info', 'Email уже подтвержден', $user->registration_source));
        }

        // Проверяем валидность подписи и хеша
        if (!$request->hasValidSignature()) {
            return redirect()->away($this->getRedirectUrl('error', 'Недействительная или истекшая ссылка', $user->registration_source));
        }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect()->away($this->getRedirectUrl('error', 'Недействительная ссылка', $user->registration_source));
        }

        // Подтверждаем email
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            Log::info('User verified email', ['user_id' => $user->id, 'email' => $user->email]);
        }

        // Определяем источник из модели пользователя
        $source = $user->registration_source ?? config('app.frontend_url', 'https://wotnt.ru');

        return redirect()->away($this->getRedirectUrl('success', 'Email успешно подтвержден', $source));
    }

    /**
     * Отправка повторного письма
     */
    public function resend(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Пользователь не авторизован'
            ], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email уже подтвержден'
            ], 400);
        }

        $user->sendEmailVerificationNotification();
        
        Log::info('Verification email resent', ['user_id' => $user->id, 'email' => $user->email]);

        return response()->json([
            'message' => 'Письмо отправлено повторно'
        ]);
    }

    /**
     * Вспомогательный метод для формирования URL редиректа
     */
    private function getRedirectUrl(string $status, string $message, string $source = null): string
    {
        // Определяем базовый URL
        if ($source && strpos($source, 'http') === 0) {
            $baseUrl = $source;
        } elseif ($source === 'wotgospel.ru') {
            $baseUrl = 'https://wotgospel.ru';
        } else {
            $baseUrl = config('app.frontend_url', 'https://wotnt.ru');
        }
        
        // Формируем параметры для фронтенда
        $params = [
            'verified' => $status === 'success' ? '1' : '0',
            'status' => $status,
            'message' => $message,
        ];
        
        // ✅ ИЗМЕНЕНО: редирект на /auth/verify (где лежит ваш файл)
        return $baseUrl . '/auth/verify?' . http_build_query($params);
    }
}