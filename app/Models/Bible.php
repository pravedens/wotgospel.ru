<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtmlInput;

class Bible extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        // ✅ Санитизация HTML
    'description' => CleanHtmlInput::class,
    ];

    // Получить стих дня по дате
    public static function getVerseOfTheDay($date = null)
    {
        $date = $date ?? now()->toDateString();

        return static::whereDate('date', $date)->first();
    }

    // Получить случайный стих
    public static function getRandomVerse()
    {
        return static::inRandomOrder()->first();
    }
}
