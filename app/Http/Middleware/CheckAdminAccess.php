<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Разрешаем доступ: super_admin, admin, redactorEvents, teacher
        if (!$user->hasAnyRole(['super_admin', 'admin', 'redactorEvents', 'teacher'])) {
            abort(403, 'Недостаточно прав доступа');
        }

        return $next($request);
    }
}