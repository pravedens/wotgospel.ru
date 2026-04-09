<?php

namespace App\Http\Controllers\Api;

use App\Models\Denomination;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class DenominationController extends Controller
{
    /**
     * Получение всех категорий
     */
    public function index()
    {
        try {
            $denominations = Denomination::withCount('about')
                ->orderBy('title')
                ->get();

            return response()->json($denominations);
        } catch (\Exception $e) {
            Log::error('DenominationController@index error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Получение конкретной категории по slug
     */
    public function show($slug)
    {
        try {
            $denomination = Denomination::where('slug', $slug)
                ->with('about')
                ->first();
                
            if (!$denomination) {
                return response()->json(['error' => 'Denomination not found'], 404);
            }

            return response()->json($denomination);
        } catch (\Exception $e) {
            Log::error('DenominationController@show error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}