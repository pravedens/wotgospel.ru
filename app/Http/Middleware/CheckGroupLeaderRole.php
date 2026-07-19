<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckGroupLeaderRole
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

        if (!$user->isGroupLeader() && !$user->isTeacher() && !$user->isSuperAdmin() && !$user->isPastor()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для лидеров групп'
            ], 403);
        }

        return $next($request);
    }
}