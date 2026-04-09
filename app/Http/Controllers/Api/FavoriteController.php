<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Получить список избранных постов пользователя
     */
    public function index()
    {
        $user = Auth::user();
        
        $favorites = $user->favoritePosts()
            ->with(['category', 'group', 'conference'])
            ->latest()
            ->get();

        return response()->json($favorites);
    }

    /**
     * Добавить пост в избранное
     */
    public function store(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id'
        ]);

        $user = Auth::user();
        $postId = $request->post_id;

        // Проверяем, не добавлено ли уже
        $exists = Favorite::where('user_id', $user->id)
            ->where('post_id', $postId)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Пост уже в избранном'
            ], 400);
        }

        // Добавляем в избранное
        Favorite::create([
            'user_id' => $user->id,
            'post_id' => $postId
        ]);

        // Получаем обновленное количество
        $favoritesCount = Favorite::where('post_id', $postId)->count();

        return response()->json([
            'message' => 'Добавлено в избранное',
            'is_favorite' => true,
            'favorites_count' => $favoritesCount
        ]);
    }

    /**
     * Удалить пост из избранного
     */
    public function destroy($postId)
{
    $user = Auth::user();

    $favorite = Favorite::where('user_id', $user->id)
        ->where('post_id', $postId)
        ->first();

    if (!$favorite) {
        return response()->json([
            'message' => 'Пост не найден в избранном'
        ], 404);
    }

    $favorite->delete();

    return response()->json([
        'message' => 'Удалено из избранного',
        'is_favorite' => false
    ]);
}

    /**
     * Проверить, находится ли пост в избранном у текущего пользователя
     */
    public function check($postId)
    {
        $user = Auth::user();

        $isFavorite = Favorite::where('user_id', $user->id)
            ->where('post_id', $postId)
            ->exists();

        return response()->json([
            'is_favorite' => $isFavorite
        ]);
    }
}