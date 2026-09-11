<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mews\Purifier\Casts\CleanHtmlInput;

class About extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'abouts';

    protected $casts = [
    'content' => CleanHtmlInput::class,
];

    public function denomination(): BelongsTo
    {
        return $this->belongsTo(Denomination::class);
    }

    /**
     * Связь с логами просмотров
     */
    public function viewsLogs(): HasMany
    {
        return $this->hasMany(AboutViewsLog::class, 'about_id');
    }

    /**
     * Увеличить счётчик просмотров (один раз в день с одного IP)
     */
    public function incrementViews(string $ip, string $userAgent): void
    {
        $today = now()->toDateString();

        // Проверяем, был ли уже просмотр сегодня с этого IP
        $existingLog = $this->viewsLogs()
            ->where('ip_address', $ip)
            ->whereDate('viewed_at', $today)
            ->exists();

        if (! $existingLog) {
            // Создаём запись в логе
            $this->viewsLogs()->create([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'viewed_at' => $today,
            ]);

            // Увеличиваем счётчик
            $this->increment('views');
        }
    }
}
