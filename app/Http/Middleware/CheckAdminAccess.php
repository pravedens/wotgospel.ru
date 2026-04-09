<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Если пользователь не авторизован - пропускаем дальше
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        
        // Проверяем роль через Spatie Permission
        $hasAdminAccess = false;
        
        if (method_exists($user, 'hasRole')) {
            $hasAdminAccess = $user->hasRole('admin') || $user->hasRole('super_admin');
        }
        
        // Если нет прав доступа
        if (!$hasAdminAccess) {
            // Разлогиниваем
            auth()->logout();
            
            // Очищаем сессию
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            // Редирект на главный сайт
            return redirect()->to('https://wotnt.ru')->with('error', 'У вас нет прав для доступа к панели администратора');
        }

        return $next($request);
    }
}