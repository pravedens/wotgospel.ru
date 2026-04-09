<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::orderBy('title')->get();
    }

    public function filtered(Request $request)
    {
        $query = Category::withCount(['posts' => function($q) use ($request) {
            if ($request->has('group_id')) {
                $q->where('group_id', $request->group_id);
            }
            if ($request->has('conference_id')) {
                $q->where('conference_id', $request->conference_id);
            }
        }]);
        
        return $query->having('posts_count', '>', 0)
                     ->orderBy('title')
                     ->get();
    }
}