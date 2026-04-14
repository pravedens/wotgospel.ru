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
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Используем единый метод проверки
        if (!$user->canAccessAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Недостаточно прав доступа'
            ], 403);
        }

        return $next($request);
    }
}