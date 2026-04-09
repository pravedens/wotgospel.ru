<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conference;
use Illuminate\Http\Request;

class ConferenceController extends Controller
{
    public function index()
    {
        return Conference::orderBy('title')->get();
    }

    public function filtered(Request $request)
    {
        $query = Conference::withCount(['posts' => function($q) use ($request) {
            if ($request->has('category_id')) {
                $q->where('category_id', $request->category_id);
            }
            if ($request->has('group_id')) {
                $q->where('group_id', $request->group_id);
            }
        }]);
        
        return $query->having('posts_count', '>', 0)
                     ->orderBy('title')
                     ->get();
    }
}