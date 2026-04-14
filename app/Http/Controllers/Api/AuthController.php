<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserConsent;
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
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('Invalid login attempt', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Неверные email или пароль'
                ], 401);
            }

            // Удаляем старые токены
            $user->tokens()->delete();

            // Создаем новый токен
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'last_name' => $user->last_name,
                    'middle_name' => $user->middle_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'phone' => $user->phone,
                    'city' => $user->city,
                    'church_name' => $user->church_name,
                    'about' => $user->about,
                    'birth_date' => $user->birth_date,
                    'avatar' => $user->avatar,
                    'created_at' => $user->created_at,
                ],
                'roles' => $user->roles->pluck('name')->toArray(),
                'can_access_admin' => $user->canAccessAdmin(),
            ]);

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
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
            'password' => 'required|string|min:8|confirmed',
            'privacy_accepted' => 'required|accepted',
            'registration_source' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
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

        // ✅ НЕ выдаём токен, только отправляем письмо
        event(new Registered($user));

        return response()->json([
            'success' => true,
            'message' => 'Регистрация успешна. Пожалуйста, подтвердите email, перейдя по ссылке в письме.',
            'requires_verification' => true,
            // ❌ НЕТ token, user, roles
        ], 201);

    } catch (\Exception $e) {
        Log::error('Registration error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Ошибка при регистрации'
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
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'last_name' => $user->last_name,
                    'middle_name' => $user->middle_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'phone' => $user->phone,
                    'city' => $user->city,
                    'church_name' => $user->church_name,
                    'about' => $user->about,
                    'birth_date' => $user->birth_date,
                    'avatar' => $user->avatar,
                    'created_at' => $user->created_at,
                ],
                'roles' => $user->roles->pluck('name')->toArray(),
                'can_access_admin' => $user->canAccessAdmin(),
            ]);

        } catch (\Exception $e) {
            Log::error('Get user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки пользователя'
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
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
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
                'current_password' => 'required_with:new_password|current_password',
                'new_password' => 'nullable|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $oldEmail = $user->email;
            $emailChanged = false;

            // Обновляем основные поля
            $user->fill($request->only([
                'name', 'last_name', 'middle_name', 'phone', 'city', 'church_name', 'about', 'birth_date'
            ]));

            // Обновляем email, если изменился
            if ($request->filled('email') && $request->email !== $user->email) {
                $user->email = $request->email;
                $user->email_verified_at = null;
                $emailChanged = true;
            }

            // Обновляем пароль, если указан
            if ($request->filled('new_password')) {
                $user->password = Hash::make($request->new_password);
            }

            $user->save();

            // Отправляем новое письмо подтверждения, если email изменен
            if ($emailChanged) {
                $user->sendEmailVerificationNotification();
            }

            return response()->json([
                'success' => true,
                'message' => 'Профиль успешно обновлен',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'last_name' => $user->last_name,
                    'middle_name' => $user->middle_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'phone' => $user->phone,
                    'city' => $user->city,
                    'church_name' => $user->church_name,
                    'about' => $user->about,
                    'birth_date' => $user->birth_date,
                    'avatar' => $user->avatar,
                ],
                'email_verification_required' => $emailChanged,
            ]);

        } catch (\Exception $e) {
            Log::error('Update profile error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления профиля'
            ], 500);
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
                'success' => true,
                'message' => 'Выход выполнен успешно'
            ]);

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при выходе'
            ], 500);
        }
    }

/**
 * Обновление согласия на обработку персональных данных
 */
public function updateConsent(Request $request)
{
    try {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'policy_version' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Сохраняем согласие
        $consent = UserConsent::create([
            'user_id' => $user->id,
            'consent_type' => 'privacy_policy',
            'policy_version' => $request->policy_version,
            'ip_address' => $request->ip(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Согласие успешно обновлено',
            'consent' => [
                'date' => $consent->created_at->format('d.m.Y H:i:s'),
                'version' => $consent->policy_version,
                'ip' => $consent->ip_address,
            ]
        ]);
        
    } catch (\Exception $e) {
        Log::error('Update consent error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Ошибка обновления согласия'
        ], 500);
    }
}

/**
 * История согласий пользователя
 */
public function consentHistory(Request $request)
{
    try {
        $user = $request->user();
        
        $consents = UserConsent::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($consent) {
                return [
                    'date' => $consent->created_at->format('d.m.Y H:i:s'),
                    'version' => $consent->policy_version,
                    'ip' => $consent->ip_address,
                ];
            });
        
        return response()->json([
            'success' => true,
            'consents' => $consents,
        ]);
        
    } catch (\Exception $e) {
        Log::error('Consent history error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'consents' => []
        ], 500);
    }
}
}