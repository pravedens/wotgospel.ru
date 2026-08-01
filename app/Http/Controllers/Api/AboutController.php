<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\About;
use App\Models\Denomination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AboutController extends Controller
{
    /**
     * Получение всех статей с фильтрацией по категории
     */
    public function index(Request $request)
    {
        try {
            $cacheKey = 'abouts_list_'.md5(json_encode($request->all()).'_'.app()->getLocale());

            $data = Cache::remember($cacheKey, 3600, function () use ($request) {
                $query = About::with('denomination')
                    ->orderBy('created_at', 'desc');

                if ($request->has('denomination_id')) {
                    $query->where('denomination_id', $request->denomination_id);
                }

                if ($request->has('denomination_slug')) {
                    $query->whereHas('denomination', function ($q) use ($request) {
                        $q->where('slug', $request->denomination_slug);
                    });
                }

                $abouts = $query->get();

                return [
                    'abouts' => $abouts,
                    'filters' => [
                        'denomination_id' => $request->denomination_id,
                        'denomination_slug' => $request->denomination_slug,
                    ],
                ];
            });

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error('AboutController@index error: '.$e->getMessage());

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Получение конкретной статьи по slug
     */
    public function show(Request $request, $slug)
    {
        try {
            $about = About::with('denomination')
                ->where('slug', $slug)
                ->first();

            if (! $about) {
                return response()->json(['error' => 'About not found'], 404);
            }

            // ✅ Увеличиваем счётчик просмотров (один раз в день с одного IP)
            $about->incrementViews($request->ip(), $request->userAgent());

            return response()->json($about);
        } catch (\Exception $e) {
            Log::error('AboutController@show error: '.$e->getMessage());

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Получение всех категорий
     */
    public function denominations()
    {
        try {
            $cacheKey = 'denominations_list_'.app()->getLocale();

            \Log::info('🔑 Cache key: '.$cacheKey);

            $denominations = Cache::remember($cacheKey, 86400, function () {
                \Log::info('💾 Cache MISS - generating data');

                return Denomination::withCount('about')
                    ->orderBy('title')
                    ->get();
            });

            \Log::info('✅ Cache result: '.($denominations ? 'HAS DATA' : 'EMPTY'));

            return response()->json($denominations);
        } catch (\Exception $e) {
            Log::error('AboutController@denominations error: '.$e->getMessage());

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Получение статей по категории
     */
    public function byDenomination($slug)
    {
        try {
            $cacheKey = 'denomination_abouts_'.$slug.'_'.app()->getLocale();

            $data = Cache::remember($cacheKey, 86400, function () use ($slug) {
                $denomination = Denomination::where('slug', $slug)->first();

                if (! $denomination) {
                    return null;
                }

                $abouts = About::with('denomination')
                    ->where('denomination_id', $denomination->id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                return [
                    'denomination' => $denomination,
                    'abouts' => $abouts,
                ];
            });

            if (! $data) {
                return response()->json(['error' => 'Denomination not found'], 404);
            }

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error('AboutController@byDenomination error: '.$e->getMessage());

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Очистка кеша (для админов)
     */
    public function clearCache(Request $request)
    {
        try {
            $cacheKeys = [
                'denominations_list_'.app()->getLocale(),
            ];

            // Очищаем кеш категорий
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }

            // Очищаем кеш статей по категориям
            $denominations = Denomination::all();
            foreach ($denominations as $denomination) {
                Cache::forget('denomination_abouts_'.$denomination->slug.'_'.app()->getLocale());
            }

            return response()->json([
                'success' => true,
                'message' => 'Кеш очищен',
            ]);
        } catch (\Exception $e) {
            Log::error('AboutController@clearCache error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка очистки кеша',
            ], 500);
        }
    }
}
