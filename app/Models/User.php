<?php

namespace App\Models;

use App\Models\SocialLink;
use App\Models\FieldVisibility;
use App\Models\MinisterCategory;
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
use NotificationChannels\WebPush\HasPushSubscriptions;
use Illuminate\Support\Facades\Log;
use App\Models\EventAttendee;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens, HasRoles, HasFactory, Notifiable, HasPushSubscriptions;

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
        // Email уведомления
        'notify_new_events_email',
        'notify_event_reminder_email',
        'notify_event_day_email',
        // SMS уведомления
        'notify_new_events_push',
        'notify_event_reminder_push',
        'notify_event_day_push',
        // Web Push уведомления
        'notify_new_events_webpush',
        'notify_event_reminder_webpush',
        'notify_event_day_webpush',
        // Согласие
        'notification_consent_given_at',
        'notification_consent_ip',
        'phone_for_notifications',
        // 🆕 Уведомления о сообщениях для служителей
        'notify_minister_messages_email',
        'notify_minister_messages_webpush',
        // 🆕 Библейская школа
        'marital_status',
        'gender',
        'ministry',
        'bible_courses_experience',
        'learning_expectations',
        'notify_teacher_messages_email',
        'notify_teacher_messages_webpush',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'notify_new_events_email' => 'boolean',
            'notify_event_reminder_email' => 'boolean',
            'notify_event_day_email' => 'boolean',
            'notify_new_events_push' => 'boolean',
            'notify_event_reminder_push' => 'boolean',
            'notify_event_day_push' => 'boolean',
            'notify_new_events_webpush' => 'boolean',
            'notify_event_reminder_webpush' => 'boolean',
            'notify_event_day_webpush' => 'boolean',
            'notification_consent_given_at' => 'datetime',
            // 🆕 Уведомления о сообщениях для служителей
            'notify_minister_messages_email' => 'boolean',
            'notify_minister_messages_webpush' => 'boolean',
            'marital_status' => 'string',
            'gender' => 'string',
            'notify_teacher_messages_email' => 'boolean',
            'notify_teacher_messages_webpush' => 'boolean',
        ];
    }
    
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->canAccessAdmin();
        }
        
        if ($panel->getId() === 'user') {
            return true;
        }
        
        return false;
    }
    
    public function canAccessAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin', 'redactorEvents', 'teacher']);
    }

    public function isAnyAdmin(): bool
    {
        foreach ($this->roles as $role) {
            if ($role->name !== 'user') {
                return true;
            }
        }
        return false;
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }
    
    public function socialLinks()
    {
        return $this->hasMany(SocialLink::class)->orderBy('sort_order');
    }

    public function fieldVisibilities()
    {
        return $this->hasMany(FieldVisibility::class);
    }

    public function ministerCategories()
    {
        return $this->belongsToMany(MinisterCategory::class, 'category_user', 'user_id', 'category_id');
    }

    public function isFieldVisible(string $fieldName): bool
    {
        $visibility = $this->fieldVisibilities()->where('field_name', $fieldName)->first();
    
        if (!$visibility) {
            return $fieldName !== 'email';
        }
    
        return $visibility->is_visible;
    }

    public function initializeFieldVisibilities(): void
    {
        $defaultFields = [
            'name' => true,
            'last_name' => false,
            'middle_name' => false,
            'phone' => false,
            'city' => true,
            'church_name' => true,
            'about' => true,
            'birth_date' => false,
            'email' => false,
            'avatar' => true,
        ];
    
        foreach ($defaultFields as $fieldName => $isVisible) {
            $this->fieldVisibilities()->updateOrCreate(
                ['field_name' => $fieldName],
                ['is_visible' => $isVisible]
            );
        }
    }

    protected static function booted()
    {
        static::created(function (User $user) {
            if (Role::where('name', 'user')->exists()) {
                $user->assignRole('user');
            }
        });
    }
    
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }
    
    public function sendEmailVerificationNotification()
    {
        \Log::info('=== sendEmailVerificationNotification CALLED ===', [
            'user_id' => $this->id,
            'email' => $this->email
        ]);
        
        try {
            $this->notify(new \App\Notifications\CustomVerifyEmail());
            \Log::info('=== CustomVerifyEmail SENT ===');
        } catch (\Exception $e) {
            \Log::error('=== NOTIFICATION FAILED ===', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getPhoneAttribute($value)
    {
        if (!$value) return null;
        return $value;
    }
    
    public function consents()
    {
        return $this->hasMany(UserConsent::class);
    }
    
    public function hasConsentedTo(string $consentType = 'privacy_policy', ?string $version = null): bool
    {
        $query = $this->consents()->where('consent_type', $consentType);
        
        if ($version) {
            $query->where('policy_version', $version);
        }
        
        return $query->exists();
    }
    
    public function lastConsentDate(string $consentType = 'privacy_policy'): ?string
    {
        $consent = $this->consents()
            ->where('consent_type', $consentType)
            ->latest()
            ->first();
            
        return $consent?->created_at?->format('d.m.Y H:i:s');
    }
    
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoritePosts()
    {
        return $this->belongsToMany(Post::class, 'favorites')->withTimestamps();
    }
    
    public function isAdmin(): bool
    {
        foreach ($this->roles as $role) {
            if ($role->name !== 'user') {
                return true;
            }
        }
        return false;
    }
    
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }
    
    public function isMember(): bool
    {
        return $this->hasRole('member');
    }
    
    public function isMinister(): bool
    {
        return $this->hasRole('minister');
    }
    
    public function isPastor(): bool
    {
        return $this->hasRole('pastor');
    }
    
    /**
     * Проверяет, может ли пользователь получать уведомления о событии
     * с учётом флагов members_only и ministers_only
     */
    public function canReceiveNotificationForEvent(Event $event): bool
    {
        // Супер-администраторы видят всё
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Администраторы видят всё
        if ($this->hasRole('admin')) {
            return true;
        }
        
        // Пасторы видят всё
        if ($this->isPastor()) {
            return true;
        }
        
        // Служители видят: обычные + события для служителей
        if ($this->isMinister()) {
            // События для служителей
            if ($event->ministers_only) {
                return true;
            }
            // Служители НЕ видят события только для членов церкви
            if ($event->members_only && !$event->ministers_only) {
                return false;
            }
            // Обычные события видят
            return true;
        }
        
        // Члены церкви видят: обычные + события для членов
        if ($this->isMember()) {
            // События для членов
            if ($event->members_only) {
                return true;
            }
            // Члены НЕ видят события только для служителей
            if ($event->ministers_only) {
                return false;
            }
            // Обычные события видят
            return true;
        }
        
        // Обычные пользователи (роль "user") видят только обычные события
        return !$event->members_only && !$event->ministers_only;
    }
    
    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->last_name,
            $this->name,
            $this->middle_name
        ])));
    }
    
    public function getInitialsAttribute(): string
    {
        $parts = array_filter([$this->name, $this->last_name]);
        if (empty($parts)) return 'U';
        
        return collect($parts)
            ->map(fn($part) => mb_substr($part, 0, 1))
            ->join('');
    }
    
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            if (str_starts_with($this->avatar, 'avatars/')) {
                return 'https://storage.yandexcloud.net/wotgospel-media/' . $this->avatar;
            }
            return asset('storage/' . $this->avatar);
        }
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name ?? 'User') . 
               '&background=10b981&color=fff&bold=true&size=128';
    }
    
    public function getFormattedPhoneAttribute(): ?string
    {
        if (!$this->phone) return null;
        
        if (str_contains($this->phone, '+')) {
            return $this->phone;
        }
        
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (strlen($phone) === 11) {
            return '+7 (' . substr($phone, 1, 3) . ') ' . 
                   substr($phone, 4, 3) . '-' . 
                   substr($phone, 7, 2) . '-' . 
                   substr($phone, 9, 2);
        }
        
        return $this->phone;
    }
    
    public function getFavoritesCountAttribute(): int
    {
        return $this->favorites()->count();
    }
    
    public function hasFavorited($postId): bool
    {
        return $this->favorites()
            ->where('post_id', $postId)
            ->exists();
    }
    
    // ============================================
    // МЕТОДЫ ДЛЯ УВЕДОМЛЕНИЙ
    // ============================================
    
    public function canReceiveNotifications(): bool
    {
        if (!$this->id) return false;
        
        return true;
    }
    
    public function wantsNotification(string $type, string $channel): bool
    {
        if (!$this->canReceiveNotifications()) {
            return false;
        }
        
        $settingMap = [
            'new_event' => "notify_new_events_{$channel}",
            'reminder' => "notify_event_reminder_{$channel}",
            'day_before' => "notify_event_day_{$channel}",
            'enrollment_rejected' => "notify_enrollment_rejected_{$channel}",
            'certificate_issued' => "notify_certificate_issued_{$channel}",
        ];
        
        $setting = $settingMap[$type] ?? null;
        
        if (!$setting || !$this->$setting) {
            return false;
        }
        
        if ($channel === 'email') {
            return !is_null($this->email_verified_at);
        }
        
        if ($channel === 'push') {
            return !empty($this->phone_for_notifications);
        }
        
        if ($channel === 'webpush') {
            try {
                return $this->pushSubscriptions()->exists();
            } catch (\Exception $e) {
                Log::warning('Failed to check push subscriptions in wantsNotification: ' . $e->getMessage());
                return false;
            }
        }
        
        return false;
    }
    
    public function hasActiveNotificationSubscriptions(): bool
    {
        return $this->notify_new_events_email ||
               $this->notify_new_events_push ||
               $this->notify_new_events_webpush ||
               $this->notify_event_reminder_email ||
               $this->notify_event_reminder_push ||
               $this->notify_event_reminder_webpush ||
               $this->notify_event_day_email ||
               $this->notify_event_day_push ||
               $this->notify_event_day_webpush;
    }
    
    public function giveNotificationConsent(string $ip): array
    {
        if (!$this->canReceiveNotifications()) {
            return [
                'success' => false,
                'message' => 'Уведомления доступны только для зарегистрированных прихожан церкви.'
            ];
        }
        
        $this->notification_consent_given_at = now();
        $this->notification_consent_ip = $ip;
        $this->save();
        
        return [
            'success' => true,
            'message' => 'Согласие на получение уведомлений успешно подтверждено.'
        ];
    }
    
    public function revokeNotificationConsent(): void
    {
        $this->notification_consent_given_at = null;
        $this->notification_consent_ip = null;
        
        $this->notify_new_events_email = false;
        $this->notify_new_events_push = false;
        $this->notify_new_events_webpush = false;
        $this->notify_event_reminder_email = false;
        $this->notify_event_reminder_push = false;
        $this->notify_event_reminder_webpush = false;
        $this->notify_event_day_email = false;
        $this->notify_event_day_push = false;
        $this->notify_event_day_webpush = false;
        
        $this->save();
    }
    
    public function hasNotificationConsent(): bool
    {
        if (!$this->canReceiveNotifications()) {
            return false;
        }
        
        return !is_null($this->notification_consent_given_at);
    }
    
    public function getSubscribedChannelsFor(string $type): array
    {
        $channels = [];
        
        if ($this->wantsNotification($type, 'email')) {
            $channels[] = 'email';
        }
        
        if ($this->wantsNotification($type, 'push')) {
            $channels[] = 'push';
        }
        
        if ($this->wantsNotification($type, 'webpush')) {
            $channels[] = 'webpush';
        }
        
        return $channels;
    }
    
    public function getUserRoleName(): string
    {
        if ($this->isSuperAdmin()) return 'super_admin';
        if ($this->hasRole('admin')) return 'admin';
        if ($this->hasRole('redactorEvents')) return 'redactorEvents';
        if ($this->hasRole('pastor')) return 'pastor';
        if ($this->hasRole('minister')) return 'minister';
        if ($this->hasRole('member')) return 'member';
        return 'user';
    }
    
    public function getNotificationSettingsAttribute(): array
    {
        $canReceive = $this->canReceiveNotifications();
        
        $hasPushSubscription = false;
        try {
            $hasPushSubscription = $this->pushSubscriptions()->exists();
        } catch (\Exception $e) {
            Log::warning('Failed to check push subscriptions in getNotificationSettingsAttribute: ' . $e->getMessage());
        }
        
        return [
            'notifications_available' => $canReceive,
            'notifications_blocked_reason' => $canReceive ? null : 'Уведомления доступны только для прихожан церкви (роль "member")',
            'user_role' => $this->getUserRoleName(),
            
            // Email
            'notify_new_events_email' => $canReceive ? (bool)$this->notify_new_events_email : false,
            'notify_event_reminder_email' => $canReceive ? (bool)$this->notify_event_reminder_email : false,
            'notify_event_day_email' => $canReceive ? (bool)$this->notify_event_day_email : false,
            
            // SMS
            'notify_new_events_push' => $canReceive ? (bool)$this->notify_new_events_push : false,
            'notify_event_reminder_push' => $canReceive ? (bool)$this->notify_event_reminder_push : false,
            'notify_event_day_push' => $canReceive ? (bool)$this->notify_event_day_push : false,
            
            // Web Push
            'notify_new_events_webpush' => $canReceive ? (bool)$this->notify_new_events_webpush : false,
            'notify_event_reminder_webpush' => $canReceive ? (bool)$this->notify_event_reminder_webpush : false,
            'notify_event_day_webpush' => $canReceive ? (bool)$this->notify_event_day_webpush : false,
            
            'phone_for_notifications' => $canReceive ? $this->phone_for_notifications : null,
            'has_consent' => $canReceive ? $this->hasNotificationConsent() : false,
            'consent_given_at' => $this->notification_consent_given_at?->toIso8601String(),
            'has_push_subscription' => $hasPushSubscription,
            
            // 🆕 Библейская школа
            'notify_enrollment_rejected_email' => $canReceive ? (bool)$this->notify_enrollment_rejected_email : false,
            'notify_enrollment_rejected_webpush' => $canReceive ? (bool)$this->notify_enrollment_rejected_webpush : false,
            'notify_certificate_issued_email' => $canReceive ? (bool)$this->notify_certificate_issued_email : false,
            'notify_certificate_issued_webpush' => $canReceive ? (bool)$this->notify_certificate_issued_webpush : false,
        ];
    }
    
    public function eventNotificationLogs()
    {
        return $this->hasMany(EventNotificationLog::class);
    }
    
    public function hasReceivedNotificationFor(int $eventId, string $type): bool
    {
        return $this->eventNotificationLogs()
            ->where('event_id', $eventId)
            ->where('type', $type)
            ->where('status', 'sent')
            ->exists();
    }
    
    // ============================================
    // СКОУПЫ ДЛЯ УВЕДОМЛЕНИЙ
    // ============================================
    
    public function scopeCanReceiveNotifications($query)
    {
        return $query->whereNotNull('email_verified_at');
    }
    
    public function scopeSubscribedToNewEvents($query, ?string $channel = null)
    {
        return $query->whereNotNull('email_verified_at')
            ->whereNotNull('notification_consent_given_at')
            ->when($channel === 'email', function($q) {
                $q->where('notify_new_events_email', true);
            })
            ->when($channel === 'push', function($q) {
                $q->where('notify_new_events_push', true);
            })
            ->when($channel === 'webpush', function($q) {
                $q->where('notify_new_events_webpush', true);
            })
            ->when(!$channel, function($q) {
                $q->where(function($sub) {
                    $sub->where('notify_new_events_email', true)
                        ->orWhere('notify_new_events_push', true)
                        ->orWhere('notify_new_events_webpush', true);
                });
            });
    }
    
    public function scopeSubscribedToReminders($query, string $type = 'day_before', ?string $channel = null)
    {
        $column = $type === 'day_before' ? 'notify_event_day' : 'notify_event_reminder';
        
        return $query->whereNotNull('email_verified_at')
            ->whereNotNull('notification_consent_given_at')
            ->when($channel === 'email', function($q) use ($column) {
                $q->where($column . '_email', true);
            })
            ->when($channel === 'push', function($q) use ($column) {
                $q->where($column . '_push', true);
            })
            ->when($channel === 'webpush', function($q) use ($column) {
                $q->where($column . '_webpush', true);
            })
            ->when(!$channel, function($q) use ($column) {
                $q->where(function($sub) use ($column) {
                    $sub->where($column . '_email', true)
                        ->orWhere($column . '_push', true)
                        ->orWhere($column . '_webpush', true);
                });
            });
    }
    
    public function scopeWithValidPhoneForNotifications($query)
    {
        return $query->whereNotNull('phone_for_notifications')
                     ->where('phone_for_notifications', '!=', '');
    }
    
    public function scopeWithNotificationConsent($query)
    {
        return $query->whereNotNull('notification_consent_given_at');
    }
    
    public function scopeAdmins($query)
    {
        return $query->whereHas('roles', function($q) {
            $q->where('name', '!=', 'user');
        });
    }
    
    public function scopeRegularUsers($query)
    {
        return $query->whereDoesntHave('roles', function($q) {
            $q->where('name', '!=', 'user');
        })->orWhereHas('roles', function($q) {
            $q->where('name', 'user');
        });
    }
    
    // ============================================
    // СКОУПЫ ДЛЯ СЛУЖИТЕЛЕЙ
    // ============================================

    public function scopeWithMinisterCategories($query)
    {
        return $query->whereHas('ministerCategories');
    }

    public function getHasMinisterCategoriesAttribute(): bool
    {
        return $this->ministerCategories()->exists();
    }

    // ============================================
    // ПУБЛИЧНЫЕ ДАННЫЕ ДЛЯ КАРТОЧКИ
    // ============================================

    public function getPublicMinisterData(): array
    {
        $data = [
            'id' => $this->id,
            'roles' => $this->getRoleNames()->toArray(),
            'full_name' => $this->full_name,
        ];
        
        $fields = ['name', 'last_name', 'middle_name', 'phone', 'city', 'church_name', 'about', 'birth_date'];
        
        foreach ($fields as $field) {
            if ($this->isFieldVisible($field)) {
                $data[$field] = $this->$field;
            }
        }
        
        if ($this->isFieldVisible('email')) {
            $data['email'] = $this->email;
        }
        
        if ($this->isFieldVisible('avatar')) {
            $data['avatar_url'] = $this->avatar_url;
        }
        
        $data['social_links'] = $this->socialLinks;
        $data['minister_categories'] = $this->ministerCategories->map(fn($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'slug' => $cat->slug,
            'icon' => $cat->icon,
            'color' => $cat->color,
        ]);
        
        return $data;
    }

    /**
     * События, на которые пользователь нажал «Я приду»
     */
    public function attendingEvents()
    {
        return $this->belongsToMany(Event::class, 'event_attendees', 'user_id', 'event_id')
            ->withTimestamps();
    }

    public function scopeOrderByMinisterPriority($query)
    {
        return $query
            ->select('users.*')
            ->addSelect(DB::raw('
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM category_user cu 
                        JOIN minister_categories mc ON cu.category_id = mc.id 
                        WHERE cu.user_id = users.id AND mc.name = "Наши пасторы"
                    ) THEN 1
                    WHEN EXISTS (
                        SELECT 1 FROM category_user cu 
                        JOIN minister_categories mc ON cu.category_id = mc.id 
                        WHERE cu.user_id = users.id AND mc.name = "Админ сайта"
                    ) THEN 3
                    ELSE 2
                END as priority_group
            '))
            ->orderBy('priority_group')
            ->orderBy('users.name');
    }

    // ============================================
    // МЕТОДЫ ДЛЯ ОНЛАЙН-БИБЛЕЙСКОЙ ШКОЛЫ
    // ============================================

    /**
     * Проверяет, является ли пользователь гостем (без школьных ролей)
     */
    public function isGuest(): bool
    {
        return !$this->hasAnyRole(['student', 'teacher', 'group_leader', 'pastor', 'super_admin']);
    }

    /**
     * Проверяет, является ли пользователь учеником
     */
    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    /**
     * Проверяет, является ли пользователь учителем
     */
    public function isTeacher(): bool
    {
        return $this->hasRole('teacher');
    }

    /**
     * Проверяет, является ли пользователь лидером группы
     */
    public function isGroupLeader(): bool
    {
        return $this->hasRole('group_leader');
    }

    /**
     * Возвращает наивысшую роль пользователя в иерархии
     */
    public function getHighestRole(): string
    {
        $roles = $this->getRoleNames()->toArray();
        $priority = ['super_admin', 'pastor', 'teacher', 'group_leader', 'student', 'user'];
        
        foreach ($priority as $role) {
            if (in_array($role, $roles)) {
                return $role;
            }
        }
        
        return 'user';
    }

    /**
     * Проверяет, может ли пользователь назначить указанную роль
     */
    public function canAssignRole(string $roleToAssign): bool
    {
        $roleHierarchy = [
            'super_admin' => 5,
            'pastor' => 4,
            'teacher' => 3,
            'group_leader' => 2,
            'student' => 1,
            'user' => 0,
        ];
        
        $currentLevel = $roleHierarchy[$this->getHighestRole()] ?? 0;
        $targetLevel = $roleHierarchy[$roleToAssign] ?? 0;
        
        // Нельзя назначить роль выше или равную своей
        return $targetLevel < $currentLevel;
    }

    /**
     * Проверяет, зачислен ли пользователь в школу (имеет любую школьную роль)
     */
    public function isEnrolledInSchool(): bool
{
    $result = $this->hasRole('student') || $this->hasRole('teacher') || $this->hasRole('group_leader');
    \Log::info('=== isEnrolledInSchool ===', [
        'user_id' => $this->id,
        'roles' => $this->getRoleNames()->toArray(),
        'result' => $result
    ]);
    return $result;
}

    /**
     * Отношение к прогрессу по урокам
     */
    public function bibleProgress()
    {
        return $this->hasMany(BibleUserLessonProgress::class, 'user_id');
    }

    /**
     * Отношение к эссе
     */
    public function bibleEssays()
    {
        return $this->hasMany(BibleEssay::class, 'user_id');
    }

    /**
     * Отношение к проверенным эссе (как проверяющий)
     */
    public function bibleEssaysReviewed()
    {
        return $this->hasMany(BibleEssay::class, 'reviewed_by');
    }

    /**
     * Отношение к группам (Party), в которых состоит пользователь
     */
    public function bibleParties()
    {
        return $this->belongsToMany(BibleParty::class, 'bible_party_user', 'user_id', 'party_id')
            ->withPivot('joined_at', 'is_active')
            ->withTimestamps();
    }
    
    /**
 * Темы, где пользователь является учителем
 */
public function themes()
{
    return $this->hasMany(\App\Models\BibleTheme::class, 'teacher_id');
}
    
    public function assignedCourse()
    {
        return $this->belongsTo(BibleCourse::class, 'assigned_course_id');
    }

    public function availableCourses()
    {
        if ($this->assigned_course_id) {
            return BibleCourse::where('id', $this->assigned_course_id)->get();
        }
        return BibleCourse::where('is_published', true)->orderBy('order')->get();
    }

    /**
     * Возвращает активную группу пользователя (если есть)
     */
    public function bibleActiveParty()
    {
        return $this->bibleParties()->wherePivot('is_active', true)->first();
    }

    /**
     * Отношение к сообщениям в группах
     */
    public function biblePartyMessages()
    {
        return $this->hasMany(BiblePartyMessage::class, 'user_id');
    }

    /**
     * Отношение к комментариям к урокам
     */
    public function bibleLessonComments()
    {
        return $this->hasMany(BibleLessonComment::class, 'user_id');
    }

    /**
     * Отношение к заявке на обучение
     */
    public function bibleEnrollmentRequest()
    {
        return $this->hasOne(BibleEnrollmentRequest::class, 'user_id');
    }

    /**
     * Отношение к сертификатам
     */
    public function bibleCertificates()
    {
        return $this->hasMany(BibleCertificate::class, 'user_id');
    }

    /**
     * Получить прогресс пользователя по курсу
     */
    public function getCourseProgress(int $courseId): array
    {
        $course = BibleCourse::find($courseId);
        if (!$course) {
            return ['completed' => 0, 'total' => 0, 'percentage' => 0];
        }
        
        return $course->getProgressForUser($this->id);
    }
    
    public function getMaritalStatusLabel(): string
    {
        return match($this->marital_status) {
            'single' => 'Холост/Не замужем',
            'married' => 'В браке',
            'divorced' => 'Разведён(а)',
            'widowed' => 'Вдова/Вдовец',
            default => 'Не указано',
        };
    }
    
    public function getGenderLabel(): string
    {
        return match($this->gender) {
            'male' => 'Мужской',
            'female' => 'Женский',
            default => 'Не указано',
        };
    }
}