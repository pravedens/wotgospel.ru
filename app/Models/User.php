<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Models\Role;
use Filament\Notifications\Auth\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomVerifyEmail; 

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasRoles, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'middle_name',
        'email',
        'password',
        'avatar',
        'phone',
        'city',
        'church_name',
        'about',
        'birth_date',
        'privacy_accepted', 
        'registration_source',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
        ];
    }
    
    /**
     * Определяет, может ли пользователь получить доступ к панели Filament
     * Для Filament 4 сигнатура: canAccessPanel(Panel $panel): bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Для админ-панели
        if ($panel->getId() === 'admin') {
            return $this->canAccessAdmin();
        }
        
        // Для пользовательской панели доступ разрешен всем
        if ($panel->getId() === 'user') {
            return true;
        }
        
        // Для всех остальных панелей - запрещаем
        return false;
    }
    
    /**
     * Проверка доступа к админ-панели (только admin и super_admin)
     */
    public function canAccessAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Проверка, является ли пользователь администратором (любая роль кроме user)
     */
    public function isAnyAdmin(): bool
    {
        foreach ($this->roles as $role) {
            if ($role->name !== 'user') {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверка, имеет ли пользователь одну из указанных ролей
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    
    /**
     * Booted model events
     */
    protected static function booted()
    {
        static::created(function (User $user) {
            // Назначаем роль 'user' по умолчанию
            if (Role::where('name', 'user')->exists()) {
                $user->assignRole('user');
            }
        });
    }
    
    /**
     * Мутатор для email - всегда в нижнем регистре
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }
    
    /**
     * Отправка уведомления о подтверждении email
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail());
    }
    
    /**
     * Аксессор для телефона
     */
    public function getPhoneAttribute($value)
    {
        if (!$value) return null;
    
        // Если телефон хранится как "+7 (992) 018-34-48", возвращаем как есть
        return $value;
    }
    
    /**
     * Связь с согласиями пользователя
     */
    public function consents()
    {
        return $this->hasMany(UserConsent::class);
    }
    
    /**
     * Проверить, давал ли пользователь согласие на конкретную версию политики
     */
    public function hasConsentedTo(string $consentType = 'privacy_policy', ?string $version = null): bool
    {
        $query = $this->consents()->where('consent_type', $consentType);
        
        if ($version) {
            $query->where('policy_version', $version);
        }
        
        return $query->exists();
    }
    
    /**
     * Получить дату последнего согласия
     */
    public function lastConsentDate(string $consentType = 'privacy_policy'): ?string
    {
        $consent = $this->consents()
            ->where('consent_type', $consentType)
            ->latest()
            ->first();
            
        return $consent?->created_at?->format('d.m.Y H:i:s');
    }
    
    /**
     * Связь с избранными проповедями
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Избранные посты через many-to-many
     */
    public function favoritePosts()
    {
        return $this->belongsToMany(Post::class, 'favorites')->withTimestamps();
    }
    
    /**
     * Проверить, является ли пользователь администратором
     * (имеет любую роль, кроме 'user')
     */
    public function isAdmin(): bool
    {
        foreach ($this->roles as $role) {
            if ($role->name !== 'user') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Проверить, является ли пользователь суперадминистратором
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }
    
    /**
     * Получить полное имя пользователя
     */
    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->last_name,
            $this->name,
            $this->middle_name
        ])));
    }
    
    /**
     * Получить инициалы пользователя для аватара
     */
    public function getInitialsAttribute(): string
    {
        $parts = array_filter([$this->name, $this->last_name]);
        if (empty($parts)) return 'U';
        
        return collect($parts)
            ->map(fn($part) => mb_substr($part, 0, 1))
            ->join('');
    }
    
    /**
 * Получить URL аватара
 */
public function getAvatarUrlAttribute(): ?string
{
    if ($this->avatar) {
        // Если аватар на S3
        if (str_starts_with($this->avatar, 'avatars/')) {
            return 'https://storage.yandexcloud.net/wotgospel-media/' . $this->avatar;
        }
        // Если аватар локально
        return asset('storage/' . $this->avatar);
    }
    
    // Генерируем аватар на основе имени через ui-avatars
    return 'https://ui-avatars.com/api/?name=' . urlencode($this->name ?? 'User') . 
           '&background=10b981&color=fff&bold=true&size=128';
}
    
    /**
     * Отформатированный номер телефона (если хранится в цифрах)
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        if (!$this->phone) return null;
        
        // Если телефон уже отформатирован, возвращаем как есть
        if (str_contains($this->phone, '+')) {
            return $this->phone;
        }
        
        // Форматируем номер +7 (999) 999-99-99
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (strlen($phone) === 11) {
            return '+7 (' . substr($phone, 1, 3) . ') ' . 
                   substr($phone, 4, 3) . '-' . 
                   substr($phone, 7, 2) . '-' . 
                   substr($phone, 9, 2);
        }
        
        return $this->phone;
    }
    
    /**
     * Получить количество избранных проповедей
     */
    public function getFavoritesCountAttribute(): int
    {
        return $this->favorites()->count();
    }
    
    /**
     * Проверить, добавил ли пользователь пост в избранное
     */
    public function hasFavorited($postId): bool
    {
        return $this->favorites()
            ->where('post_id', $postId)
            ->exists();
    }
    
    /**
     * Скоуп для поиска администраторов
     */
    public function scopeAdmins($query)
    {
        return $query->whereHas('roles', function($q) {
            $q->where('name', '!=', 'user');
        });
    }
    
    /**
     * Скоуп для поиска обычных пользователей
     */
    public function scopeRegularUsers($query)
    {
        return $query->whereDoesntHave('roles', function($q) {
            $q->where('name', '!=', 'user');
        })->orWhereHas('roles', function($q) {
            $q->where('name', 'user');
        });
    }
}