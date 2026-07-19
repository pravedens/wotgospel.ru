<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStudentRole
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

        if (!$user->isStudent() && !$user->isSuperAdmin() && !$user->isPastor() && !$user->isTeacher() && !$user->isGroupLeader()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для студентов'
            ], 403);
        }

        return $next($request);
    }
}