<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        return Group::orderBy('title')->get();
    }

    public function filtered(Request $request)
    {
        $query = Group::withCount(['posts' => function($q) use ($request) {
            if ($request->has('category_id')) {
                $q->where('category_id', $request->category_id);
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