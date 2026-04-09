<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use App\Models\Group;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    /**
     * Получение списка постов с фильтрацией и пагинацией
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'nullable|exists:categories,id',
                'group_id' => 'nullable|exists:groups,id',
                'conference_id' => 'nullable|exists:conferences,id',
                'per_page' => 'nullable|integer|min:1|max:500',
                'page' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Ошибка валидации параметров',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Базовый запрос - убираем все сложности с аутентификацией
            $query = Post::with(['category', 'group', 'conference'])
                ->orderBy('created_at', 'desc');

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('group_id')) {
                $query->where('group_id', $request->group_id);
            }

            if ($request->filled('conference_id')) {
                $query->where('conference_id', $request->conference_id);
            }
            
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('title', 'like', "%{$search}%");
            }

            $perPage = $request->get('per_page', 8);
            $posts = $query->paginate($perPage);

            return response()->json($posts);
            
        } catch (\Exception $e) {
            Log::error('Error in posts index: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'per_page' => $request->get('per_page', 8),
                'total' => 0,
                'last_page' => 1,
                'from' => null,
                'to' => null,
            ]);
        }
    }

    /**
     * Получение одного поста по slug
     */
    public function show($slug)
    {
        try {
            $post = Post::with(['category', 'group', 'conference'])
                ->where('slug', $slug)
                ->first();

            if (!$post) {
                return response()->json([
                    'message' => 'Пост не найден'
                ], 404);
            }

            return response()->json($post);
            
        } catch (\Exception $e) {
            Log::error('Error in post show: ' . $e->getMessage(), [
                'slug' => $slug
            ]);
            
            return response()->json(['message' => 'Ошибка загрузки поста'], 500);
        }
    }

    /**
     * Получение всех категорий
     */
    public function filteredCategories(Request $request)
    {
        try {
            $query = Category::withCount(['posts']);
            
            return $query->having('posts_count', '>', 0)
                ->orderBy('title')
                ->get();
                
        } catch (\Exception $e) {
            Log::error('Error in filteredCategories: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Получение всех групп
     */
    public function filteredGroups(Request $request)
    {
        try {
            $query = Group::withCount(['posts']);
            
            return $query->having('posts_count', '>', 0)
                ->orderBy('title')
                ->get();
                
        } catch (\Exception $e) {
            Log::error('Error in filteredGroups: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Получение всех конференций
     */
    public function filteredConferences(Request $request)
    {
        try {
            $query = Conference::withCount(['posts']);
            
            return $query->having('posts_count', '>', 0)
                ->orderBy('title')
                ->get();
                
        } catch (\Exception $e) {
            Log::error('Error in filteredConferences: ' . $e->getMessage());
            return response()->json([]);
        }
    }
    
    /**
     * Получение рекомендуемых (случайных) постов
     */
    public function recommended(Request $request)
    {
        try {
            $limit = $request->get('limit', 4);
            
            $posts = Post::with(['category', 'group', 'conference'])
                ->inRandomOrder()
                ->limit($limit)
                ->get();
            
            return response()->json($posts);
            
        } catch (\Exception $e) {
            Log::error('Error in recommended posts: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}