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
    $user = User::findOrFail($id);
    
    if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        return redirect()->away('https://wotnt.ru/auth/login?verified=0&error=invalid');
    }
    
    if ($user->hasVerifiedEmail()) {
        return redirect()->away('https://wotnt.ru/auth/login?verified=already');
    }
    
    $user->markEmailAsVerified();
    
    // ✅ Редирект на страницу логина с сообщением об успехе
    return redirect()->away('https://wotnt.ru/auth/login?verified=1');
}

    /**
 * Повторная отправка письма с подтверждением
 */
public function resend(Request $request)
{
    // Если есть email в запросе (неавторизованный пользователь)
    if ($request->has('email')) {
        $user = User::where('email', $request->email)->first();
        if ($user && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            return response()->json(['message' => 'Письмо отправлено']);
        }
        return response()->json(['message' => 'Пользователь не найден или уже подтверждён'], 404);
    }
    
    // Авторизованный пользователь
    $user = $request->user();
    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email уже подтверждён'], 400);
    }
    
    $user->sendEmailVerificationNotification();
    return response()->json(['message' => 'Письмо отправлено']);
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