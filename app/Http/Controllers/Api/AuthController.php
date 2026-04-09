<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Registered;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    /**
     * Вход пользователя и выдача токена
     */
    public function login(Request $request)
    {
        try {
            Log::info('Login attempt', ['email' => $request->email]);
            
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ищем пользователя вручную (без Auth::attempt)
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                Log::warning('User not found', ['email' => $request->email]);
                return response()->json([
                    'message' => 'Неверные email или пароль'
                ], 401);
            }
            
            // Проверяем пароль
            if (!Hash::check($request->password, $user->password)) {
                Log::warning('Invalid password', ['email' => $request->email]);
                return response()->json([
                    'message' => 'Неверные email или пароль'
                ], 401);
            }
            
            // Удаляем старые токены
            $user->tokens()->delete();
            
            // Создаем новый токен
            $token = $user->createToken('auth-token')->plainTextToken;
            
            // Получаем роли пользователя
            $roles = [];
            if (method_exists($user, 'roles')) {
                $roles = $user->roles->pluck('name')->toArray();
            }
            
            // Проверяем доступ к админке
            $canAccessAdmin = false;
            foreach ($roles as $role) {
                if ($role !== 'user') {
                    $canAccessAdmin = true;
                    break;
                }
            }
            
            Log::info('Login successful', ['user_id' => $user->id]);
            
            return response()->json([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'last_name' => $user->last_name,
                    'middle_name' => $user->middle_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'city' => $user->city,
                    'church_name' => $user->church_name,
                    'about' => $user->about,
                    'birth_date' => $user->birth_date,
                    'avatar' => $user->avatar,
                    'created_at' => $user->created_at,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'roles' => $roles,
                'can_access_admin' => $canAccessAdmin,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'message' => 'Ошибка при входе в систему'
            ], 500);
        }
    }

    /**
     * Регистрация нового пользователя
     */
    public function register(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'privacy_accepted' => 'required|accepted',
            'registration_source' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'privacy_accepted' => $request->privacy_accepted,
            'registration_source' => $request->registration_source ?? 'wotnt.ru',
        ]);

        // Назначаем роль 'user' по умолчанию
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('user');
        }

        // Отправляем письмо с подтверждением email
        event(new Registered($user));

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Регистрация успешна. Пожалуйста, подтвердите email.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'created_at' => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
            ],
            'roles' => ['user'],
            'can_access_admin' => false,
            'requires_verification' => true,
        ], 201);

    } catch (\Exception $e) {
        Log::error('Registration error: ' . $e->getMessage());
        return response()->json([
            'message' => 'Ошибка при регистрации: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Получение данных текущего пользователя
     */
    public function user(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            // Загружаем роли
            if (method_exists($user, 'roles')) {
                $user->load('roles');
                $roles = $user->roles->pluck('name')->toArray();
            } else {
                $roles = ['user'];
            }
            
            // Проверяем доступ к админке
            $canAccessAdmin = false;
            foreach ($roles as $role) {
                if ($role !== 'user') {
                    $canAccessAdmin = true;
                    break;
                }
            }
            
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'last_name' => $user->last_name,
                    'middle_name' => $user->middle_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'city' => $user->city,
                    'church_name' => $user->church_name,
                    'about' => $user->about,
                    'birth_date' => $user->birth_date,
                    'avatar' => $user->avatar,
                    'created_at' => $user->created_at,
                ],
                'roles' => $roles,
                'can_access_admin' => $canAccessAdmin,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get user error: ' . $e->getMessage());
            return response()->json(['message' => 'Error loading user'], 500);
        }
    }

    /**
     * Выход пользователя
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if ($user && method_exists($user, 'currentAccessToken')) {
                $user->currentAccessToken()->delete();
            }
            
            return response()->json([
                'message' => 'Выход выполнен успешно'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ошибка при выходе'
            ], 500);
        }
    }

    /**
     * Обновление профиля пользователя
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:255',
                'church_name' => 'nullable|string|max:255',
                'about' => 'nullable|string',
                'birth_date' => 'nullable|date',
                'new_password' => 'nullable|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Обновляем основные данные
            $user->fill($request->only([
                'name', 'last_name', 'middle_name', 'email',
                'phone', 'city', 'church_name', 'about', 'birth_date'
            ]));

            // Обновляем пароль, если указан
            if ($request->filled('new_password')) {
                $user->password = Hash::make($request->new_password);
            }

            $user->save();

            return response()->json([
                'message' => 'Профиль успешно обновлен',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'last_name' => $user->last_name,
                    'middle_name' => $user->middle_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'city' => $user->city,
                    'church_name' => $user->church_name,
                    'about' => $user->about,
                    'birth_date' => $user->birth_date,
                    'avatar' => $user->avatar,
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Update profile error: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating profile'], 500);
        }
    }

    /**
     * Обновление согласия (заглушка)
     */
    public function updateConsent(Request $request)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    /**
     * История согласий (заглушка)
     */
    public function consentHistory(Request $request)
    {
        return response()->json(['consents' => []]);
    }
}