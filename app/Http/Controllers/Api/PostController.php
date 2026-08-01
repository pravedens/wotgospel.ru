<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Conference;
use App\Models\Group;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    /**
     * Получение списка постов с фильтрацией и пагинацией
     */
    public function index(Request $request)
    {
        // ✅ Кешируем список постов на 5 минут
        $cacheKey = 'posts_list_'.md5(json_encode($request->all()));

        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return response()->json($cachedData);
        }

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
                'errors' => $validator->errors(),
            ], 422);
        }

        // Базовый запрос с загрузкой связанных данных
        $query = Post::with([
            'category:id,title,slug',
            'group:id,title,slug',
            'conference:id,title,slug',
        ])
            ->select(
                'id', 'title', 'slug', 'description', 'content',
                'thumbnail', 'category_id', 'group_id', 'conference_id',
                'created_at', 'views_count', 'likes_count'
            )
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
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Пагинация (по умолчанию 8 записей на странице)
        $perPage = $request->get('per_page', 8);
        $posts = $query->paginate($perPage);

        // ✅ Оптимизируем проверку избранного
        $user = $request->user();

        if ($user) {
            $userId = $user->id;
            $postIds = $posts->pluck('id')->toArray();

            if (! empty($postIds)) {
                $favorites = DB::table('favorites')
                    ->where('user_id', $userId)
                    ->whereIn('post_id', $postIds)
                    ->pluck('post_id')
                    ->toArray();

                $posts->getCollection()->transform(function ($post) use ($favorites) {
                    $post->is_favorite = in_array($post->id, $favorites);

                    return $post;
                });
            }
        } else {
            $posts->getCollection()->transform(function ($post) {
                $post->is_favorite = false;

                return $post;
            });
        }

        $data = $posts; // результат

        Cache::put($cacheKey, $data, 300);

        return response()->json($data);
    }

    /**
     * Получение одного поста по slug
     */
    public function show($slug)
    {
        $post = Post::with(['category:id,title,slug', 'group:id,title,slug', 'conference:id,title,slug'])
            ->where('slug', $slug)
            ->first();

        if (! $post) {
            return response()->json([
                'message' => 'Пост не найден',
            ], 404);
        }

        // Добавляем специальный URL для просмотра
        $post->word_viewer_url = $post->word_viewer_url ?? null;

        // Добавляем информацию об избранном
        if (Auth::check()) {
            $post->is_favorite = $post->is_favorited;
            $post->favorites_count = $post->favorites_count;
        }

        return response()->json($post);
    }

    /**
     * ✅ НОВЫЙ МЕТОД: Получение всех фильтров в одном запросе
     * Объединяет filteredCategories, filteredGroups, filteredConferences
     * и заменяет отдельные запросы /categories, /groups, /conferences
     */
    public function filters(Request $request)
    {
        try {
            $cacheKey = 'filters_'.md5(json_encode($request->all()).'_'.app()->getLocale());

            // ✅ Проверяем кеш
            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {
                \Log::info('✅ Filters cache HIT');

                return response()->json($cachedData);
            }

            \Log::info('❌ Filters cache MISS');

            $categories = Category::withCount(['posts' => function ($q) use ($request) {
                if ($request->has('group_id')) {
                    $q->where('group_id', $request->group_id);
                }
                if ($request->has('conference_id')) {
                    $q->where('conference_id', $request->conference_id);
                }
            }])
                ->having('posts_count', '>', 0)
                ->orderBy('title')
                ->get(['id', 'title', 'slug']);

            $groups = Group::withCount(['posts' => function ($q) use ($request) {
                if ($request->has('category_id')) {
                    $q->where('category_id', $request->category_id);
                }
                if ($request->has('conference_id')) {
                    $q->where('conference_id', $request->conference_id);
                }
            }])
                ->having('posts_count', '>', 0)
                ->orderBy('title', 'desc')
                ->get(['id', 'title', 'slug']);

            $conferences = Conference::withCount(['posts' => function ($q) use ($request) {
                if ($request->has('category_id')) {
                    $q->where('category_id', $request->category_id);
                }
                if ($request->has('group_id')) {
                    $q->where('group_id', $request->group_id);
                }
            }])
                ->having('posts_count', '>', 0)
                ->orderBy('title')
                ->get(['id', 'title', 'slug']);

            $data = compact('categories', 'groups', 'conferences');

            Cache::put($cacheKey, $data, 3600);
            \Log::info('💾 Filters cache SET');

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error('Error in filters: '.$e->getMessage());

            return response()->json([
                'categories' => [],
                'groups' => [],
                'conferences' => [],
            ]);
        }
    }

    /**
     * ⚠️ DEPRECATED: Используйте filters() вместо этого метода
     * Получение категорий (спикеров) с учетом выбранных фильтров
     */
    public function filteredCategories(Request $request)
    {
        try {
            $query = Category::withCount(['posts' => function ($q) use ($request) {
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
            Log::error('Error in filteredCategories: '.$e->getMessage());

            return response()->json([]);
        }
    }

    /**
     * ⚠️ DEPRECATED: Используйте filters() вместо этого метода
     * Получение групп (годов) с учетом выбранных фильтров
     */
    public function filteredGroups(Request $request)
    {
        try {
            $query = Group::withCount(['posts' => function ($q) use ($request) {
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
                ->orderBy('title', 'desc')
                ->get();

        } catch (\Exception $e) {
            Log::error('Error in filteredGroups: '.$e->getMessage());

            return response()->json([]);
        }
    }

    /**
     * ⚠️ DEPRECATED: Используйте filters() вместо этого метода
     * Получение конференций (мероприятий) с учетом выбранных фильтров
     */
    public function filteredConferences(Request $request)
    {
        try {
            $query = Conference::withCount(['posts' => function ($q) use ($request) {
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
            Log::error('Error in filteredConferences: '.$e->getMessage());

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
            $cacheKey = 'recommended_posts_'.$limit.'_'.app()->getLocale();

            // Кешируем рекомендуемые посты на 10 минут (600 секунд)
            return Cache::remember($cacheKey, 600, function () use ($limit) {
                // Получаем случайные посты
                $posts = Post::with(['category:id,title,slug', 'group:id,title,slug', 'conference:id,title,slug'])
                    ->inRandomOrder()
                    ->limit($limit)
                    ->get();

                // Добавляем информацию об избранном для каждого поста
                if (Auth::check()) {
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
            });

        } catch (\Exception $e) {
            Log::error('Error in recommended posts: '.$e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Очистка кеша рекомендуемых постов
     */
    public function clearRecommendedCache(Request $request)
    {
        try {
            $limit = $request->get('limit', 4);
            $cacheKey = 'recommended_posts_'.$limit.'_'.app()->getLocale();
            Cache::forget($cacheKey);

            return response()->json([
                'success' => true,
                'message' => 'Кеш рекомендуемых постов очищен',
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing recommended cache: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка очистки кеша',
            ], 500);
        }
    }
}
