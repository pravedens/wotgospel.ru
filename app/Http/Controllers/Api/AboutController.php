<?php

namespace App\Http\Controllers\Api;

use App\Models\About;
use App\Models\Denomination;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class AboutController extends Controller
{
    /**
     * Получение всех статей с фильтрацией по категории
     */
    public function index(Request $request)
    {
        try {
            $query = About::with('denomination')
                ->orderBy('created_at', 'desc');

            // Фильтр по категории
            if ($request->has('denomination_id')) {
                $query->where('denomination_id', $request->denomination_id);
            }

            // Фильтр по slug категории
            if ($request->has('denomination_slug')) {
                $query->whereHas('denomination', function ($q) use ($request) {
                    $q->where('slug', $request->denomination_slug);
                });
            }

            $abouts = $query->get();

            return response()->json([
                'abouts' => $abouts,
                'filters' => [
                    'denomination_id' => $request->denomination_id,
                    'denomination_slug' => $request->denomination_slug,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('AboutController@index error: ' . $e->getMessage());
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
            
        if (!$about) {
            return response()->json(['error' => 'About not found'], 404);
        }
        
        // ✅ Увеличиваем счётчик просмотров (один раз в день с одного IP)
        $about->incrementViews($request->ip(), $request->userAgent());
        
        return response()->json($about);
    } catch (\Exception $e) {
        Log::error('AboutController@show error: ' . $e->getMessage());
        return response()->json(['error' => 'Internal server error'], 500);
    }
}

    /**
     * Получение всех категорий
     */
    public function denominations()
    {
        try {
            $denominations = Denomination::withCount('about')
                ->orderBy('title')
                ->get();

            return response()->json($denominations);
        } catch (\Exception $e) {
            Log::error('AboutController@denominations error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Получение статей по категории
     */
    public function byDenomination($slug)
    {
        try {
            $denomination = Denomination::where('slug', $slug)
                ->first();
                
            if (!$denomination) {
                return response()->json(['error' => 'Denomination not found'], 404);
            }

            $abouts = About::with('denomination')
                ->where('denomination_id', $denomination->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'denomination' => $denomination,
                'abouts' => $abouts
            ]);
        } catch (\Exception $e) {
            Log::error('AboutController@byDenomination error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}