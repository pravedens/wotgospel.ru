<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;
     
    protected $guarded = [];
    
    /**
     * Поля, которые должны быть преобразованы в даты
     */
    protected $casts = [
        'startDate' => 'date',
        'startTime' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'endDate' => 'date',
        'is_published' => 'boolean',
        'show_in_carousel' => 'boolean',
        'members_only' => 'boolean',
    ];
    
    /**
     * Автоматически снимает с публикации прошедшие события
     */
    protected static function booted()
    {
        parent::booted();
        
        // При создании
        static::creating(function ($event) {
            // Если указана дата, но не заполнены компоненты
            if ($event->startDate && (!$event->startWeek || !$event->startDay || !$event->startMonth)) {
                $event->fillDateComponents();
            }
            
            // Если дата прошедшая, автоматически снимаем с публикации
            if ($event->startDate && Carbon::parse($event->startDate)->isPast()) {
                $event->is_published = false;
            }
        });
        
        // При обновлении
        static::updating(function ($event) {
            if ($event->isDirty('startDate') && $event->startDate) {
                $event->fillDateComponents();
            }
        });
        
        // При сохранении проверяем, не нужно ли снять с публикации
        static::saving(function ($event) {
            // Если событие прошедшее и оно было опубликовано, снимаем с публикации
            if ($event->startDate && Carbon::parse($event->startDate)->isPast() && $event->is_published) {
                $event->is_published = false;
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
 $user->hasRole(['super_admin', 'admin'])
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
 $user->hasRole(['super_admin', 'admin'])
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
        return $this->startDate ? Carbon::parse($this->startDate)->isPast() : false;
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
}