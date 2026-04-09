<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bible;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BibleController extends Controller
{
    /**
     * Получить стих дня (кэшируется на 24 часа)
     */
    public function verseOfTheDay(Request $request)
    {
        $today = now()->toDateString();
        
        // Кэшируем стих на весь день (86400 секунд = 24 часа)
        $verse = Cache::remember("verse_of_the_day_{$today}", 86400, function () {
            // Получаем случайный стих из базы
            $verse = Bible::inRandomOrder()->first();
            
            if (!$verse) {
                return null;
            }
            
            return [
                'id' => $verse->id,
                'title' => $verse->title,
                'description' => strip_tags($verse->description), // убираем HTML-теги
                'slug' => $verse->slug,
            ];
        });
        
        if (!$verse) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Стихи ещё не добавлены',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $verse,
            'message' => 'Стих дня',
        ]);
    }
    
    /**
     * Получить список всех стихов с пагинацией
     */
    public function index(Request $request)
    {
        $verses = Bible::query()
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);
            
        // Очищаем description от HTML для каждого стиха
        $verses->getCollection()->transform(function ($verse) {
            $verse->description = strip_tags($verse->description);
            return $verse;
        });
        
        return response()->json($verses);
    }
    
    /**
     * Получить конкретный стих по slug
     */
    public function show($slug)
    {
        $verse = Bible::where('slug', $slug)->firstOrFail();
        
        // Очищаем description от HTML
        $verse->description = strip_tags($verse->description);
        
        return response()->json($verse);
    }
    
    /**
     * Очистить кэш стиха дня (для админов)
     */
    public function clearCache()
    {
        $today = now()->toDateString();
        Cache::forget("verse_of_the_day_{$today}");
        
        return response()->json([
            'success' => true,
            'message' => 'Кэш стиха дня очищен',
        ]);
    }
}