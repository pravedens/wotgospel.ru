<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\CommentLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    /**
     * Получить комментарии к посту
     */
    public function index($postId)
    {
        $post = Post::findOrFail($postId);
        
        $comments = PostComment::with(['user', 'replies.user', 'replies.replies'])
            ->where('post_id', $postId)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'comments' => $comments
        ]);
    }
    
    /**
     * Добавить комментарий
     */
    public function store(Request $request, $postId)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Только зарегистрированные пользователи могут оставлять комментарии'
            ], 401);
        }
        
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:post_comments,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $comment = PostComment::create([
            'post_id' => $postId,
            'user_id' => $user->id,
            'parent_id' => $request->parent_id,
            'content' => strip_tags($request->content),
            'is_approved' => true,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Комментарий добавлен',
            'comment' => $comment->load('user')
        ]);
    }
    
    /**
     * Удалить комментарий
     */
    public function destroy(Request $request, $commentId)
    {
        $user = $request->user();
        $comment = PostComment::findOrFail($commentId);
        
        // Проверка прав: автор комментария или админ
        if ($user->id !== $comment->user_id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Нет прав для удаления комментария'
            ], 403);
        }
        
        // Удаляем все дочерние комментарии
        $comment->replies()->delete();
        $comment->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Комментарий удалён'
        ]);
    }
    
    /**
     * Лайк/дизлайк комментария
     */
    public function toggleLike(Request $request, $commentId)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Только зарегистрированные пользователи могут оценивать комментарии'
            ], 401);
        }
        
        $comment = PostComment::findOrFail($commentId);
        $existingLike = CommentLike::where('comment_id', $commentId)
            ->where('user_id', $user->id)
            ->first();
        
        if ($existingLike) {
            $existingLike->delete();
            $comment->decrementLikesCount();
            $liked = false;
        } else {
            CommentLike::create([
                'comment_id' => $commentId,
                'user_id' => $user->id,
            ]);
            $comment->incrementLikesCount();
            $liked = true;
        }
        
        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $comment->fresh()->likes_count
        ]);
    }
}