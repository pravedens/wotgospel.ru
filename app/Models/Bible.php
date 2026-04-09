<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bible extends Model
{
    use HasFactory;
     
    protected $guarded = [];
    
    protected $casts = [
        'date' => 'date',
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