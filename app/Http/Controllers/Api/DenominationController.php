<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Denomination;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DenominationController extends Controller
{
    /**
     * Получение всех категорий
     */
    public function index()
    {
        try {
            $cacheKey = 'denominations_list_'.app()->getLocale();

            // ✅ Проверяем кеш вручную
            $denominations = Cache::get($cacheKey);

            if ($denominations) {
                \Log::info('✅ Denominations cache HIT');

                return response()->json($denominations);
            }

            \Log::info('❌ Denominations cache MISS - generating data');

            $denominations = Denomination::withCount('about')
                ->orderBy('title')
                ->get();

            // ✅ Сохраняем в кеш на 24 часа
            Cache::put($cacheKey, $denominations, 86400);
            \Log::info('💾 Denominations cache saved');

            return response()->json($denominations);

        } catch (\Exception $e) {
            Log::error('DenominationController@index error: '.$e->getMessage());

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Получение конкретной категории по slug
     */
    public function show($slug)
    {
        try {
            $cacheKey = 'denomination_'.$slug.'_'.app()->getLocale();

            $denomination = Cache::remember($cacheKey, 86400, function () use ($slug) {
                return Denomination::where('slug', $slug)
                    ->with('about')
                    ->first();
            });

            if (! $denomination) {
                return response()->json(['error' => 'Denomination not found'], 404);
            }

            return response()->json($denomination);

        } catch (\Exception $e) {
            Log::error('DenominationController@show error: '.$e->getMessage());

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
