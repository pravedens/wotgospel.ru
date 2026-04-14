<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PastorUserController extends Controller
{
    private function isPastor($user)
    {
        return $user && $user->hasRole('pastor');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$this->isPastor($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен'
            ], 403);
        }
        
        // Базовый запрос
        $baseQuery = User::query()
            ->whereNotNull('email_verified_at')
            ->whereNotNull('name')
            ->whereNotNull('last_name')
            ->whereNotNull('city')
            ->whereNotNull('church_name');
        
        // Получаем уникальные значения для фильтров
        $cities = (clone $baseQuery)
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->values()
            ->toArray();
            
        $churches = (clone $baseQuery)
            ->whereNotNull('church_name')
            ->distinct()
            ->orderBy('church_name')
            ->pluck('church_name')
            ->values()
            ->toArray();
            
        $birthYears = (clone $baseQuery)
            ->whereNotNull('birth_date')
            ->select(DB::raw('YEAR(birth_date) as year'))
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->values()
            ->toArray();
        
        // Основной запрос
        $query = User::with('roles')
            ->select('id', 'name', 'last_name', 'middle_name', 'email', 'avatar', 'phone', 'city', 'church_name', 'about', 'birth_date', 'email_verified_at', 'created_at')
            ->whereNotNull('email_verified_at')
            ->whereNotNull('name')
            ->whereNotNull('last_name')
            ->whereNotNull('city')
            ->whereNotNull('church_name');
        
        // Фильтры
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }
        
        if ($request->filled('church')) {
            $query->where('church_name', $request->church);
        }
        
        if ($request->filled('birth_year')) {
            $query->whereYear('birth_date', $request->birth_year);
        }
        
        if ($request->filled('has_email')) {
            if ($request->has_email === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->has_email === 'not_verified') {
                $query->whereNull('email_verified_at');
            }
        }
        
        if ($request->filled('has_phone')) {
            if ($request->has_phone === 'true') {
                $query->whereNotNull('phone');
            } elseif ($request->has_phone === 'false') {
                $query->whereNull('phone');
            }
        }
        
        $users = $query->orderBy('created_at', 'desc')->get()->map(function($user) {
            $fullName = trim(implode(' ', array_filter([
                $user->last_name,
                $user->name,
                $user->middle_name
            ])));
            
            // ✅ Формируем URL аватара для S3
            $avatarUrl = null;
            if ($user->avatar) {
                if (str_starts_with($user->avatar, 'avatars/')) {
                    $avatarUrl = 'https://storage.yandexcloud.net/wotgospel-media/' . $user->avatar;
                } else {
                    $avatarUrl = 'https://wotgospel.ru/storage/' . $user->avatar;
                }
            }
            
            return [
                'id' => $user->id,
                'name' => $user->name,
                'last_name' => $user->last_name,
                'middle_name' => $user->middle_name,
                'full_name' => $fullName ?: $user->name,
                'email' => $user->email,
                'email_verified' => !is_null($user->email_verified_at),
                'avatar' => $user->avatar,
                'avatar_url' => $avatarUrl,  // ✅ Исправлено
                'phone' => $user->phone,
                'city' => $user->city,
                'church_name' => $user->church_name,
                'about' => $user->about,
                'birth_date' => $user->birth_date,
                'roles' => $user->getRoleNames()->toArray(),
                'is_member' => $user->hasRole('member'),
                'is_minister' => $user->hasRole('minister'),
                'registered_at' => $user->created_at ? $user->created_at->format('d.m.Y H:i') : '',
            ];
        });
        
        return response()->json([
            'success' => true,
            'users' => $users,
            'filters' => [
                'cities' => array_values(array_unique($cities)),
                'churches' => array_values(array_unique($churches)),
                'birth_years' => array_values(array_unique($birthYears))
            ]
        ]);
    }
    
    public function updateRoles(Request $request, $userId)
    {
        $user = $request->user();
        
        if (!$this->isPastor($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен'
            ], 403);
        }
        
        $targetUser = User::findOrFail($userId);
        
        $request->validate([
            'is_member' => 'boolean',
            'is_minister' => 'boolean',
        ]);
        
        if ($request->has('is_member')) {
            if ($request->is_member) {
                $targetUser->assignRole('member');
            } else {
                $targetUser->removeRole('member');
            }
        }
        
        if ($request->has('is_minister')) {
            if ($request->is_minister) {
                $targetUser->assignRole('minister');
            } else {
                $targetUser->removeRole('minister');
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Роли пользователя обновлены',
            'user' => [
                'id' => $targetUser->id,
                'is_member' => $targetUser->hasRole('member'),
                'is_minister' => $targetUser->hasRole('minister'),
            ]
        ]);
    }
}