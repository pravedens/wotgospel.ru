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
use App\Models\MinisterCategory;

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
                'remember' => 'boolean',
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

            if (!$request->remember) {
                $user->tokens()->delete();
            }

            $tokenExpiresAt = $request->remember ? now()->addDays(30) : null;
            $token = $user->createToken('auth_token', ['*'], $tokenExpiresAt)->plainTextToken;

            if ($request->remember) {
                $user->tokens()
                    ->where('name', 'auth_token')
                    ->latest()
                    ->first()
                    ->update(['expires_at' => now()->addDays(30)]);
            }

            return response()->json([
                'success' => true,
                'token' => $token,
                'token_type' => 'Bearer',
                'remember' => $request->remember,
                'user' => $this->formatUserResponse($user),
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
    // app/Http/Controllers/Api/AuthController.php

public function register(Request $request)
{
    try {
        error_log('=== REGISTER REQUEST ===');
        error_log('Email: ' . $request->email);
        error_log('Name: ' . $request->name);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'privacy_accepted' => 'required|accepted',
            'registration_source' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            error_log('Validation failed: ' . json_encode($validator->errors()->toArray()));
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // ✅ Проверяем, существует ли пользователь с таким email
        $existingUser = User::where('email', $request->email)->first();
        
        if ($existingUser) {
            // ✅ Если email уже зарегистрирован
            return response()->json([
                'success' => false,
                'message' => 'Пользователь с таким email уже зарегистрирован',
                'can_reset_password' => true,
                'reset_url' => 'https://wotnt.ru/auth/forgot-password',
                'error_code' => 'user_exists'
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'privacy_accepted' => $request->privacy_accepted,
            'registration_source' => $request->registration_source ?? 'wotnt.ru',
        ]);

        error_log('User created: ID=' . $user->id . ', Email=' . $user->email);
        
        try {
            $user->sendEmailVerificationNotification();
            error_log('SUCCESS: Verification email sent');
        } catch (\Exception $e) {
            error_log('ERROR sending email: ' . $e->getMessage());
        }
        
        event(new Registered($user));
        error_log('Registered event dispatched');

        return response()->json([
            'success' => true,
            'message' => 'Регистрация успешна. Пожалуйста, подтвердите email, перейдя по ссылке в письме.',
            'requires_verification' => true,
        ], 201);

    } catch (\Exception $e) {
        error_log('REGISTRATION EXCEPTION: ' . $e->getMessage());
        Log::error('Registration error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
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
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'user' => $this->formatUserResponse($user),
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
            // ✅ ДОБАВЛЯЕМ АНКЕТНЫЕ ПОЛЯ
            'marital_status' => 'nullable|string|in:single,married,divorced,widowed',
            'gender' => 'nullable|string|in:male,female',
            'ministry' => 'nullable|string|max:255',
            'bible_courses_experience' => 'nullable|string',
            'learning_expectations' => 'nullable|string',
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

        // ✅ ДОБАВЛЯЕМ АНКЕТНЫЕ ПОЛЯ В fill
        $user->fill($request->only([
            'name', 'last_name', 'middle_name', 'phone', 'city', 
            'church_name', 'about', 'birth_date',
            'marital_status', 'gender', 'ministry',
            'bible_courses_experience', 'learning_expectations'
        ]));

        if ($request->filled('email') && $request->email !== $user->email) {
            $user->email = $request->email;
            $user->email_verified_at = null;
            $emailChanged = true;
        }

        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

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
                'marital_status' => $user->marital_status,
                'gender' => $user->gender,
                'ministry' => $user->ministry,
                'bible_courses_experience' => $user->bible_courses_experience,
                'learning_expectations' => $user->learning_expectations,
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
     * Форматирует ответ пользователя с анкетными полями
     */
    private function formatUserResponse(User $user): array
    {
        return [
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
            // ✅ ДОБАВЛЯЕМ АНКЕТНЫЕ ПОЛЯ
            'marital_status' => $user->marital_status,
            'gender' => $user->gender,
            'ministry' => $user->ministry,
            'bible_courses_experience' => $user->bible_courses_experience,
            'learning_expectations' => $user->learning_expectations,
        ];
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
    
    /**
     * Проверка валидности токена (для фронтенда)
     */
    public function checkToken(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalid'
                ], 401);
            }
            
            $token = $user->currentAccessToken();
            if ($token && $token->expires_at && $token->expires_at->isPast()) {
                $token->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Token expired'
                ], 401);
            }
            
            return response()->json([
                'success' => true,
                'user' => $this->formatUserResponse($user),
                'roles' => $user->roles->pluck('name')->toArray(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ============ СОЦСЕТИ ============
    public function getSocialLinks(Request $request)
    {
        return response()->json([
            'success' => true,
            'social_links' => $request->user()->socialLinks,
        ]);
    }

    public function updateSocialLinks(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'social_links' => 'array',
            'social_links.*.platform' => 'required|string|max:50',
            'social_links.*.url' => 'required|url|max:255',
            'social_links.*.sort_order' => 'integer|min:0',
        ]);
        
        $user->socialLinks()->delete();
        
        foreach ($request->social_links as $index => $link) {
            $user->socialLinks()->create([
                'platform' => $link['platform'],
                'url' => $link['url'],
                'sort_order' => $link['sort_order'] ?? $index,
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Социальные сети обновлены',
            'social_links' => $user->socialLinks,
        ]);
    }

    // ============ ВИДИМОСТЬ ПОЛЕЙ ============
    public function getFieldVisibilities(Request $request)
    {
        $user = $request->user();
        
        if ($user->fieldVisibilities()->count() === 0) {
            $user->initializeFieldVisibilities();
        }
        
        $visibilities = $user->fieldVisibilities->mapWithKeys(fn($item) => [$item->field_name => $item->is_visible]);
        
        return response()->json(['success' => true, 'visibilities' => $visibilities]);
    }

    public function updateFieldVisibilities(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'visibilities' => 'required|array',
            'visibilities.*' => 'boolean',
        ]);
        
        foreach ($request->visibilities as $fieldName => $isVisible) {
            $user->fieldVisibilities()->updateOrCreate(
                ['field_name' => $fieldName],
                ['is_visible' => $isVisible]
            );
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Настройки видимости обновлены',
        ]);
    }

    // ============ КАТЕГОРИИ СЛУЖИТЕЛЯ ============
    public function getMinisterCategories(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isMinister()) {
            return response()->json(['success' => false, 'message' => 'Доступно только служителям'], 403);
        }
        
        $allCategories = MinisterCategory::ordered()->get();
        $selectedCategoryIds = $user->ministerCategories->pluck('id')->toArray();
        
        return response()->json([
            'success' => true,
            'all_categories' => $allCategories,
            'selected_categories' => $selectedCategoryIds,
        ]);
    }

    public function updateMinisterCategories(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isMinister()) {
            return response()->json(['success' => false, 'message' => 'Доступно только служителям'], 403);
        }
        
        $request->validate([
            'category_ids' => 'array',
            'category_ids.*' => 'exists:minister_categories,id',
        ]);
        
        $user->ministerCategories()->sync($request->category_ids ?? []);
        
        return response()->json([
            'success' => true,
            'message' => 'Категории обновлены',
            'category_ids' => $request->category_ids ?? [],
        ]);
    }

    // ============================================
    // НАСТРОЙКИ УВЕДОМЛЕНИЙ ДЛЯ СЛУЖИТЕЛЯ
    // ============================================
    public function getMinisterNotificationSettings(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isMinister()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступно только служителям'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'settings' => [
                'email' => (bool) $user->notify_minister_messages_email,
                'webpush' => (bool) $user->notify_minister_messages_webpush,
            ]
        ]);
    }

    public function updateMinisterNotificationSettings(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isMinister()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступно только служителям'
            ], 403);
        }
        
        $validated = $request->validate([
            'settings.email' => 'boolean',
            'settings.webpush' => 'boolean',
        ]);
        
        $user->update([
            'notify_minister_messages_email' => $validated['settings']['email'] ?? $user->notify_minister_messages_email,
            'notify_minister_messages_webpush' => $validated['settings']['webpush'] ?? $user->notify_minister_messages_webpush,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Настройки уведомлений обновлены',
            'settings' => [
                'email' => (bool) $user->notify_minister_messages_email,
                'webpush' => (bool) $user->notify_minister_messages_webpush,
            ]
        ]);
    }
}