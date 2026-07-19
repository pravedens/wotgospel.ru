<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friend;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index(Request $request)
    {
        $friends = Friend::active()
            ->ordered()
            ->get()
            ->map(function ($friend) {
                return [
                    'id' => $friend->id,
                    'title' => $friend->title,
                    'slug' => $friend->slug,
                    'description' => $friend->description,
                    'thumbnail' => $friend->thumbnail,
                    'thumbnail_url' => $friend->thumbnail_url,
                    'link' => $friend->link,
                    'sort_order' => $friend->sort_order,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $friends
        ]);
    }
    
    public function show($slug)
    {
        $friend = Friend::where('slug', $slug)
            ->active()
            ->firstOrFail();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $friend->id,
                'title' => $friend->title,
                'slug' => $friend->slug,
                'description' => $friend->description,
                'thumbnail' => $friend->thumbnail,
                'thumbnail_url' => $friend->thumbnail_url,
                'link' => $friend->link,
            ]
        ]);
    }
}
