<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Для отладки

class CheckUserAccess
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        
        // Получаем URL, с которого была отправлена форма входа
        $referer = request()->header('referer');
        
        // --- ОТЛАДКА (можно будет посмотреть в storage/logs/laravel.log) ---
        // Log::info('Login event fired', ['user_id' => $user->id, 'referer' => $referer]);
        // ----------------------------------------------------------------

        // 1. Проверяем, пытается ли пользователь войти в админ-панель
        // Ориентируемся на referer или на путь запроса
        if ($referer && str_contains($referer, '/admin')) {
            
            // Проверяем права доступа к админ-панели
            if (!$this->canAccessAdminPanel($user)) {
                
                Auth::logout();
                
                // Устанавливаем куку для показа ошибки на главной
                setcookie('access_error', 'У вас нет прав для доступа к панели администратора', [
                    'expires' => time() + 5, // Короткое время
                    'path' => '/',
                    'domain' => '',
                    'secure' => true,
                    'httponly' => false,
                    'samesite' => 'Lax'
                ]);
                
                // Редирект на главную
                header('Location: https://wotgospel.ru');
                exit;
            }
        }
        
        // 2. Если это вход в личный кабинет (/account) или с главной страницы - ничего не делаем,
        // просто пропускаем. Права доступа к панели 'user' уже проверяются самой панелью.
    }
    
    /**
     * Проверяет, может ли пользователь зайти в админ-панель.
     */
    private function canAccessAdminPanel($user): bool
    {
        // Ваша логика из метода canAccessPanel, но для конкретного пользователя
        foreach ($user->roles as $role) {
            if ($role->name !== 'user') {
                return true;
            }
        }
        return false;
    }
}