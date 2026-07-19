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
        // Валидация параметров запроса
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:categories,id',
            'group_id' => 'nullable|exists:groups,id',
            'conference_id' => 'nullable|exists:conferences,id',
            'per_page' => 'nullable|integer|min:1|max:500',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации параметров',
                'errors' => $validator->errors()
            ], 422);
        }

        // Базовый запрос с загрузкой связанных данных
        $query = Post::with(['category', 'group', 'conference'])
            ->orderBy('created_at', 'desc');

        // Применение фильтров
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->filled('conference_id')) {
            $query->where('conference_id', $request->conference_id);
        }
        
        // Поиск по заголовку и описанию
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
                //->orWhere('description', 'like', "%{$search}%")
                //->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Пагинация (по умолчанию 8 записей на странице)
        $perPage = $request->get('per_page', 8);
        $posts = $query->paginate($perPage);
        
        // Добавляем информацию об избранном для каждого поста
        if (auth()->check()) {
            $posts->getCollection()->transform(function ($post) {
                $post->is_favorite = $post->is_favorited;
                $post->favorites_count = $post->favorites_count;
                return $post;
            });
        }

        // Возвращаем пагинированный ответ
        return response()->json($posts);
    }

    /**
     * Получение одного поста по slug
     */
    public function show($slug)
    {
        $post = Post::with(['category', 'group', 'conference'])
            ->where('slug', $slug)
            ->first();

        if (!$post) {
            return response()->json([
                'message' => 'Пост не найден'
            ], 404);
        }
        
        // Добавляем специальный URL для просмотра
        $post->word_viewer_url = $post->word_viewer_url ?? null;
    
        // Добавляем информацию об избранном
        if (auth()->check()) {
            $post->is_favorite = $post->is_favorited;
            $post->favorites_count = $post->favorites_count;
        }

        return response()->json($post);
    }

    /**
     * Получение категорий (спикеров) с учетом выбранных фильтров
     */
    public function filteredCategories(Request $request)
    {
        try {
            $query = Category::withCount(['posts' => function($q) use ($request) {
                if ($request->has('group_id')) {
                    $q->where('group_id', $request->group_id);
                }
                if ($request->has('conference_id')) {
                    $q->where('conference_id', $request->conference_id);
                }
                if ($request->has('category_id')) {
                    $q->where('category_id', $request->category_id);
                }
            }]);
            
            return $query->having('posts_count', '>', 0)
                ->orderBy('title')
                ->get();
                
        } catch (\Exception $e) {
            Log::error('Error in filteredCategories: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Получение групп (годов) с учетом выбранных фильтров
     */
    public function filteredGroups(Request $request)
    {
        try {
            $query = Group::withCount(['posts' => function($q) use ($request) {
                if ($request->has('category_id')) {
                    $q->where('category_id', $request->category_id);
                }
                if ($request->has('conference_id')) {
                    $q->where('conference_id', $request->conference_id);
                }
                if ($request->has('group_id')) {
                    $q->where('group_id', $request->group_id);
                }
            }]);
            
            return $query->having('posts_count', '>', 0)
                ->orderBy('title', 'desc') // Года по убыванию (новые сверху)
                ->get();
                
        } catch (\Exception $e) {
            Log::error('Error in filteredGroups: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Получение конференций (мероприятий) с учетом выбранных фильтров
     */
    public function filteredConferences(Request $request)
    {
        try {
            $query = Conference::withCount(['posts' => function($q) use ($request) {
                if ($request->has('category_id')) {
                    $q->where('category_id', $request->category_id);
                }
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
            
            // Получаем случайные посты
            $posts = Post::with(['category', 'group', 'conference'])
                ->inRandomOrder()
                ->limit($limit)
                ->get();
            
            // Добавляем информацию об избранном для каждого поста
            if (auth()->check()) {
                $user = auth()->user();
                $posts->each(function ($post) use ($user) {
                    $post->is_favorite = $user->favorites()
                        ->where('post_id', $post->id)
                        ->exists();
                });
            } else {
                $posts->each(function ($post) {
                    $post->is_favorite = false;
                });
            }
            
            return response()->json($posts);
            
        } catch (\Exception $e) {
            Log::error('Error in recommended posts: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}