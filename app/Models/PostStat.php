<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PostStat extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post_stats';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'ip',
        'user_agent',
        'fingerprint',
        'viewed',
        'liked',
        'viewed_at',
        'viewed_at_date'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'viewed' => 'boolean',
        'liked' => 'boolean',
        'viewed_at' => 'datetime',
        'viewed_at_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'ip',
        'user_agent'
    ];

    /**
     * Get the post that owns the stat.
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Генерация уникального отпечатка устройства
     * Комбинация IP и нормализованного User-Agent
     *
     * @param string $ip
     * @param string $userAgent
     * @return string
     */
    public static function generateFingerprint(string $ip, string $userAgent): string
    {
        // Простой, но надежный fingerprint
    $data = $ip . '|' . $userAgent;
    return hash('sha256', $data);
    }

    /**
     * Нормализация User-Agent для создания единого отпечатка
     * Убираем версии, чтобы разные версии браузеров считались одним устройством
     *
     * @param string $userAgent
     * @return string
     */
    private static function normalizeUserAgent(string $userAgent): string
    {
        // Убираем лишние пробелы
        $ua = preg_replace('/\s+/', ' ', trim($userAgent));
        
        // Нормализуем Chrome (Chrome/90.0.4430.212 -> Chrome/)
        $ua = preg_replace('/Chrome\/[\d.]+/', 'Chrome/', $ua);
        
        // Нормализуем Safari
        $ua = preg_replace('/Safari\/[\d.]+/', 'Safari/', $ua);
        
        // Нормализуем Firefox
        $ua = preg_replace('/Firefox\/[\d.]+/', 'Firefox/', $ua);
        
        // Нормализуем Edge
        $ua = preg_replace('/Edg\/[\d.]+/', 'Edge/', $ua);
        $ua = preg_replace('/Edge\/[\d.]+/', 'Edge/', $ua);
        
        // Нормализуем Opera
        $ua = preg_replace('/OPR\/[\d.]+/', 'Opera/', $ua);
        $ua = preg_replace('/Opera\/[\d.]+/', 'Opera/', $ua);
        
        // Нормализуем мобильные версии
        $ua = preg_replace('/Mobile\/[\d.]+/', 'Mobile/', $ua);
        $ua = preg_replace('/Version\/[\d.]+/', 'Version/', $ua);
        
        // Нормализуем WebKit
        $ua = preg_replace('/AppleWebKit\/[\d.]+/', 'AppleWebKit/', $ua);
        
        // Нормализуем KHTML
        $ua = preg_replace('/KHTML\/[\d.]+/', 'KHTML/', $ua);
        
        // Нормализуем Gecko
        $ua = preg_replace('/Gecko\/[\d.]+/', 'Gecko/', $ua);
        
        // Нормализуем Android
        $ua = preg_replace('/Android [\d.]+/', 'Android/', $ua);
        $ua = preg_replace('/Android\/[\d.]+/', 'Android/', $ua);
        
        // Нормализуем iOS
        $ua = preg_replace('/OS [\d_]+/', 'OS/', $ua);
        $ua = preg_replace('/iPhone OS [\d_]+/', 'iPhone OS/', $ua);
        $ua = preg_replace('/iPad; CPU OS [\d_]+/', 'iPad; CPU OS/', $ua);
        
        // Нормализуем Windows
        $ua = preg_replace('/Windows NT [\d.]+/', 'Windows NT/', $ua);
        
        // Нормализуем Mac
        $ua = preg_replace('/Mac OS X [\d_]+/', 'Mac OS X/', $ua);
        
        // Нормализуем Linux
        $ua = preg_replace('/Linux [\d.]+/', 'Linux/', $ua);
        
        // Убираем множественные пробелы
        $ua = preg_replace('/\s+/', ' ', $ua);
        
        return trim($ua);
    }

    /**
     * Проверка, был ли просмотр сегодня
     *
     * @return bool
     */
    public function isViewedToday(): bool
    {
        return $this->viewed_at && $this->viewed_at->isToday();
    }

    /**
     * Проверка, был ли лайк
     *
     * @return bool
     */
    public function isLiked(): bool
    {
        return $this->liked;
    }

    /**
     * Скоуп для поиска по IP или fingerprint
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ip
     * @param string $fingerprint
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByIpOrFingerprint($query, string $ip, string $fingerprint)
    {
        return $query->where(function($q) use ($ip, $fingerprint) {
            $q->where('ip', $ip)
              ->orWhere('fingerprint', $fingerprint);
        });
    }

    /**
     * Скоуп для постов с лайками
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLiked($query)
    {
        return $query->where('liked', true);
    }

    /**
     * Скоуп для постов с просмотрами
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeViewed($query)
    {
        return $query->where('viewed', true);
    }

    /**
     * Скоуп для просмотров за сегодня
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('viewed_at', now()->toDateString());
    }

    /**
     * Скоуп для просмотров за период
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('viewed_at', [$startDate, $endDate]);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Автоматически устанавливаем viewed_at_date если есть viewed_at
            if ($model->viewed_at && !$model->viewed_at_date) {
                $model->viewed_at_date = $model->viewed_at->toDateString();
            }
        });

        static::updating(function ($model) {
            // Обновляем viewed_at_date при изменении viewed_at
            if ($model->isDirty('viewed_at')) {
                $model->viewed_at_date = $model->viewed_at ? $model->viewed_at->toDateString() : null;
            }
        });
    }
}