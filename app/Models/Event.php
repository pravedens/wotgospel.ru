<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Models\EventAttendee;

class Event extends Model
{
    use HasFactory;
     
    protected $fillable = [
    'title',
    'slug',
    'description',
    'content',
    'info',
    'startDate',
    'startTime',
    'endDate',
    'thumbnail',
    'color',
    'show_in_carousel',
    'is_published',
    'members_only',
    'ministers_only',
    'is_conference', 
    'created_by',
];
    
    /**
     * Поля, которые должны быть преобразованы в даты
     */
    protected $casts = [
        'startDate' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'endDate' => 'date',
        'is_published' => 'boolean',
        'show_in_carousel' => 'boolean',
        'members_only' => 'boolean',
        'is_conference' => 'boolean',
    ];
    
    public function conferenceServices()
    {
        return $this->hasMany(ConferenceService::class)->orderBy('service_date');
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Получить общее количество свободных мест на конференции
     */
    public function getTotalAvailableCapacityAttribute(): int
    {
        $totalCapacity = $this->conferenceServices()->sum('capacity');
        $totalRegistered = $this->registrations()
            ->where('status', 'confirmed')
            ->count();
    
        return max(0, $totalCapacity - $totalRegistered);
    }
    
    /**
     * Получить список служений со статистикой
     */
    public function getConferenceServicesWithStatsAttribute()
    {
        return $this->conferenceServices->map(function ($service) {
            return [
                'id' => $service->id,
                'service_date' => $service->service_date,
                'service_date_formatted' => $service->formatted_date,
                'title' => $service->title,
                'description' => $service->description,
                'speaker' => $service->speaker,
                'start_time' => $service->formatted_start_time,
                'end_time' => $service->formatted_end_time,
                'time_range' => $service->time_range,
                'display_name' => $service->display_name,
                'capacity' => $service->capacity,
                'registered_count' => $service->registered_count,
                'available_count' => $service->available_count,
            ];
        });
    }
    
    /**
     * Проверка, может ли пользователь зарегистрироваться
     */
    public function canUserRegister($user): bool
    {
        if (!$this->is_conference) return false;
        if (!$user) return false;
        if (!$user->hasVerifiedEmail()) return false;
        if ($this->registrations()->where('user_id', $user->id)->exists()) return false;
    
        return true;
    }

    /**
     * Получить статус регистрации пользователя
     */
    public function getUserRegistrationStatus($user): ?string
    {
        if (!$user) return null;
    
        $registration = $this->registrations()
            ->where('user_id', $user->id)
            ->first();
    
        return $registration?->status;
    }

    /**
     * Получить регистрацию пользователя
     */
    public function getUserRegistration($user): ?EventRegistration
    {
        if (!$user) return null;
    
        return $this->registrations()
            ->where('user_id', $user->id)
            ->first();
    }
    
    /**
     * Получить все отображаемые события (включая служения конференций)
     */
    public static function getDisplayableEvents($query)
    {
        $events = $query->get();
        $displayable = [];
    
        foreach ($events as $event) {
            if ($event->is_conference && $event->conferenceServices->count() > 0) {
                // Конференция: добавляем каждое служение как отдельное событие
                foreach ($event->conferenceServices as $service) {
                    $displayable[] = (object) [
                        'id' => $event->id,
                        'title' => $service->title,
                        'slug' => $event->slug,
                        'description' => $service->description,
                        'startDate' => $service->service_date,
                        'startTime' => $service->start_time,
                        'color' => $event->color,
                        'is_conference' => true,
                        'conference_service_id' => $service->id,
                        'show_in_carousel' => $event->show_in_carousel,
                        'is_published' => $event->is_published,
                        'members_only' => $event->members_only,
                        'ministers_only' => $event->ministers_only,
                    ];
                }
            } else {
                // Обычное событие
                $displayable[] = $event;
            }
        }
    
        return $displayable;
    }
    
    /**
     * Автоматически снимает с публикации прошедшие события
     */
    protected static function booted()
    {
        parent::booted();
    
        static::creating(function ($event) {
            if ($event->startDate && (!$event->startWeek || !$event->startDay || !$event->startMonth)) {
                $event->fillDateComponents();
            }
            // ⭐ НЕ снимаем с публикации здесь — пусть это делает команда
        });
    
        static::updating(function ($event) {
            if ($event->isDirty('startDate') && $event->startDate) {
                $event->fillDateComponents();
            }
        });
        
        static::saving(function ($event) {
            // Если это конференция и нет названия — генерируем из первого служения
            if ($event->is_conference && empty($event->title)) {
                $firstService = $event->conferenceServices->first();
                if ($firstService) {
                    $event->title = 'Конференция: ' . $firstService->title;
                    $event->startDate = $firstService->service_date;
                    $event->startTime = $firstService->start_time;
                }
            }
        });
    }
    
    /**
     * Метод для принудительного снятия с публикации
     */
    public function unpublishIfPast(): bool
    {
        if ($this->isPast() && $this->is_published) {
            $this->is_published = false;
            return $this->save();
        }
        
        return false;
    }
    
    /**
     * Скоуп для фильтрации событий по видимости для пользователя
     */
    public function scopeVisibleForUser($query, $user = null)
{
 $isAdmin = $user && (
 $user->can('view_events') ||
 $user->hasRole(['super_admin', 'redactorEvents'])
 );

 if ($isAdmin) {
 return $query; // Админы видят всё
 }

 $isMember = $user && $user->hasRole('member');

 if ($isMember) {
 // Члены видят опубликованные, включая members_only
 return $query->where('is_published', true);
 }

 // Гости и обычные пользователи
 return $query
 ->where('is_published', true)
 ->where('members_only', false);
}

public function canBeViewedBy($user = null): bool
{
 $isAdmin = $user && (
 $user->can('view_events') ||
 $user->hasRole(['super_admin', 'redactorEvents'])
 );

 if ($isAdmin) {
 return true;
 }

 if (!$this->is_published) {
 return false;
 }

 if ($this->members_only) {
 return $user && $user->hasRole('member');
 }

 return true;
}
    
    /**
     * Проверка, должно ли событие быть видимо обычным пользователям
     */
    public function isVisibleToPublic(): bool
    {
        return $this->is_published;
    }
    
    /**
     * Аксессор для определения, является ли событие прошедшим
     */
    public function getIsPastAttribute(): bool
    {
        return $this->isPast();
    }
    
    /**
     * Проверка, прошло ли событие
     */
    public function isPast(): bool
{
    if (!$this->startDate) {
        return false;
    }

    if ($this->startTime) {
        $dateTime = $this->startDate->format('Y-m-d') . ' ' . substr($this->startTime, 0, 5);
        return Carbon::parse($dateTime)->isPast();
    }

    return $this->startDate->isPast();
}
    
    // (событие считается прошедшим для снятия с публикации только после полного окончания)
    public function isPastForUnpublish(): bool
    {
        if (!$this->startDate) {
            return false;
        }
    
        $eventDateTime = Carbon::parse($this->startDate);
    
        if ($this->startTime) {
            // Извлекаем только H:i из строки типа "18:30:00"
            $time = substr($this->startTime, 0, 5); // "18:30"
            $eventDateTime = Carbon::parse($this->startDate->format('Y-m-d') . ' ' . $time);
        } else {
            $eventDateTime->endOfDay();
        }
    
        return $eventDateTime->isPast();
    }
    
    /**
     * Заполняет компоненты даты (день недели, число, месяц)
     */
    protected function fillDateComponents()
    {
        if (!$this->startDate) return;
        
        $date = Carbon::parse($this->startDate);
        
        $this->startWeek = $this->getRussianWeekday($date->dayOfWeek, true);
        $this->startDay = $date->format('d');
        $this->startMonth = $this->getRussianMonth($date->month, 'nominative');
    }
    
    /**
     * Получить русское название месяца
     */
    public function getRussianMonth($month, $case = 'nominative')
    {
        $months = [
            'nominative' => [
                1 => 'Январь', 2 => 'Февраль', 3 => 'Март',
                4 => 'Апрель', 5 => 'Май', 6 => 'Июнь',
                7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь',
                10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
            ],
            'genitive' => [
                1 => 'января', 2 => 'февраля', 3 => 'марта',
                4 => 'апреля', 5 => 'мая', 6 => 'июня',
                7 => 'июля', 8 => 'августа', 9 => 'сентября',
                10 => 'октября', 11 => 'ноября', 12 => 'декабря'
            ]
        ];
        
        return $months[$case][$month] ?? '';
    }
    
    /**
     * Получить русское название дня недели
     */
    public function getRussianWeekday($dayOfWeek, $capitalize = true)
    {
        $weekdays = [
            0 => 'воскресенье',
            1 => 'понедельник',
            2 => 'вторник',
            3 => 'среда',
            4 => 'четверг',
            5 => 'пятница',
            6 => 'суббота'
        ];
        
        $weekday = $weekdays[$dayOfWeek] ?? '';
        
        if ($capitalize) {
            $weekday = mb_convert_case($weekday, MB_CASE_TITLE, "UTF-8");
        }
        
        return $weekday;
    }
    
    /**
     * Аксессор для полной даты в родительном падеже
     */
    public function getFullDateGenitiveAttribute()
    {
        if (!$this->startDate) return null;
        
        $date = Carbon::parse($this->startDate);
        return $this->startDay . ' ' . 
               $this->getRussianMonth($date->month, 'genitive') . ' ' . 
               $date->format('Y');
    }
    
    /**
     * Аксессор для отображения с днем недели
     */
    public function getDisplayDateAttribute()
    {
        if (!$this->startDate) return null;
        
        return $this->startWeek . ', ' . $this->full_date_genitive;
    }
    
    /**
     * Получить дату события в формате дд.мм.гггг
     */
    public function getEventDate()
    {
        return $this->startDate ? Carbon::parse($this->startDate)->format('d.m.Y') : null;
    }
    
    /**
     * Получить время события
     */
    public function getEventTime()
    {
        return $this->startTime ? Carbon::parse($this->startTime)->format('H:i') : null;
    }
    
    /**
     * Получить дату текстом (например: "15 января 2026")
     */
    public function getFullDateAttribute()
    {
        return $this->full_date_genitive;
    }
    
    /**
     * Получить дату и время для отображения
     */
    public function getDisplayDateTimeAttribute()
    {
        $parts = [];
        
        if ($this->startWeek) {
            $parts[] = $this->startWeek;
        }
        
        if ($this->full_date_genitive) {
            $parts[] = $this->full_date_genitive;
        }
        
        if ($this->startTime) {
            $parts[] = 'в ' . $this->getEventTime();
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Аксессор для дня недели с большой буквы
     */
    public function getStartWeekCapitalizedAttribute()
    {
        return $this->startWeek;
    }
    
    /**
     * Аксессор для месяца с большой буквы
     */
    public function getStartMonthCapitalizedAttribute()
    {
        return $this->startMonth;
    }
    
    public function getStartTimeAttribute($value)
{
    return $value; // возвращаем как строку из БД
}
    
    /**
     * Скоуп для поиска событий по дате
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('startDate', $date);
    }
    
    /**
     * Скоуп для будущих событий
     */
    public function scopeUpcoming($query)
    {
        return $query->whereDate('startDate', '>=', now());
    }
    
    /**
     * Скоуп для прошедших событий
     */
    public function scopePast($query)
    {
        return $query->whereDate('startDate', '<', now());
    }
    
    /**
     * Скоуп для сортировки по дате
     */
    public function scopeOrderByDate($query, $direction = 'asc')
    {
        return $query->orderBy('startDate', $direction);
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title) . '-' . uniqid();
            }
        });
    }
    
public function deleteThumbnail(): bool
{
    if (!$this->thumbnail) {
        return false;
    }
    
    if (\Illuminate\Support\Facades\Storage::disk('s3')->exists($this->thumbnail)) {
        $deleted = \Illuminate\Support\Facades\Storage::disk('s3')->delete($this->thumbnail);
        
        if ($deleted) {
            // Опционально: очистить поле thumbnail в БД
            // $this->update(['thumbnail' => null]);
            
            \Log::info('Thumbnail deleted from S3', [
                'event_id' => $this->id,
                'path' => $this->thumbnail
            ]);
        }
        
        return $deleted;
    }
    
    return false;
}

/**
 * Связь с участниками (кто нажал «Я приду»)
 */
public function attendees()
{
    return $this->hasMany(EventAttendee::class);
}

/**
 * Получить количество участников
 */
public function getAttendeesCountAttribute(): int
{
    return $this->attendees()->count();
}

/**
 * Проверить, идёт ли пользователь на событие
 */
public function isUserAttending(?User $user): bool
{
    if (!$user) {
        return false;
    }
    
    return $this->attendees()->where('user_id', $user->id)->exists();
}

/**
 * Добавить участника
 */
public function addAttendee(User $user): void
{
    if (!$this->isUserAttending($user)) {
        $this->attendees()->create(['user_id' => $user->id]);
    }
}

/**
 * Удалить участника
 */
public function removeAttendee(User $user): void
{
    $this->attendees()->where('user_id', $user->id)->delete();
}

/**
 * Получить всех пользователей, кто нажал «Я приду»
 */
public function getAttendingUsers()
{
    return $this->belongsToMany(User::class, 'event_attendees', 'event_id', 'user_id')
        ->withTimestamps();
}
}