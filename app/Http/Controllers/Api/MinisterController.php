<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MinisterCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MinisterController extends Controller
{
    public function index(Request $request)
    {
        try {
            $cacheKey = 'ministers_list_'.md5(json_encode($request->all()).'_'.app()->getLocale());

            $ministers = Cache::remember($cacheKey, 86400, function () {
                return User::role('minister')
                    ->whereNotNull('email_verified_at')
                    ->whereHas('ministerCategories')
                    ->with(['socialLinks', 'fieldVisibilities', 'ministerCategories'])
                    ->orderByMinisterPriority()
                    ->get();
            });

            $result = $ministers->map(fn ($minister) => $this->formatMinisterForPublic($minister));

            return response()->json(['success' => true, 'ministers' => $result]);

        } catch (\Exception $e) {
            Log::error('MinisterController@index error: '.$e->getMessage());

            return response()->json(['success' => false, 'ministers' => []], 500);
        }
    }

    public function show($id)
    {
        try {
            $cacheKey = 'minister_'.$id.'_'.app()->getLocale();

            $minister = Cache::remember($cacheKey, 86400, function () use ($id) {
                return User::role('minister')
                    ->whereHas('ministerCategories')
                    ->with(['socialLinks', 'fieldVisibilities', 'ministerCategories'])
                    ->findOrFail($id);
            });

            return response()->json([
                'success' => true,
                'minister' => $this->formatMinisterForPublic($minister),
            ]);

        } catch (\Exception $e) {
            Log::error('MinisterController@show error: '.$e->getMessage());

            return response()->json(['success' => false, 'minister' => null], 404);
        }
    }

    public function categories(Request $request)
    {
        try {
            $cacheKey = 'minister_categories_'.app()->getLocale();

            $categories = Cache::remember($cacheKey, 86400, function () {
                return MinisterCategory::ordered()->get();
            });

            return response()->json(['success' => true, 'categories' => $categories]);

        } catch (\Exception $e) {
            Log::error('MinisterController@categories error: '.$e->getMessage());

            return response()->json(['success' => false, 'categories' => []], 500);
        }
    }

    public function byCategory($slug)
    {
        try {
            $cacheKey = 'ministers_by_category_'.$slug.'_'.app()->getLocale();

            $data = Cache::remember($cacheKey, 86400, function () use ($slug) {
                $category = MinisterCategory::where('slug', $slug)->firstOrFail();

                $ministers = $category->users()
                    ->role('minister')
                    ->whereNotNull('email_verified_at')
                    ->whereHas('ministerCategories')
                    ->with(['socialLinks', 'fieldVisibilities', 'ministerCategories'])
                    ->orderByMinisterPriority()
                    ->get();

                $result = $ministers->map(fn ($minister) => $this->formatMinisterForPublic($minister));

                return [
                    'category' => $category,
                    'ministers' => $result,
                ];
            });

            return response()->json([
                'success' => true,
                'category' => $data['category'],
                'ministers' => $data['ministers'],
            ]);

        } catch (\Exception $e) {
            Log::error('MinisterController@byCategory error: '.$e->getMessage());

            return response()->json(['success' => false, 'category' => null, 'ministers' => []], 404);
        }
    }

    /**
     * Очистка кеша (для админов)
     */
    public function clearCache(Request $request)
    {
        try {
            $cacheKeys = [
                'ministers_list_'.md5('[]').'_'.app()->getLocale(),
                'minister_categories_'.app()->getLocale(),
            ];

            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }

            // Очищаем кеш по категориям
            $categories = MinisterCategory::all();
            foreach ($categories as $category) {
                Cache::forget('ministers_by_category_'.$category->slug.'_'.app()->getLocale());
            }

            return response()->json([
                'success' => true,
                'message' => 'Кеш служителей очищен',
            ]);

        } catch (\Exception $e) {
            Log::error('MinisterController@clearCache error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка очистки кеша',
            ], 500);
        }
    }

    // ============ ФОРМАТТЕР ============

    private function formatMinisterForPublic(User $minister): array
    {
        $data = [
            'id' => $minister->id,
            'roles' => $minister->getRoleNames()->toArray(),
        ];

        $fields = ['name', 'last_name', 'middle_name', 'phone', 'city', 'church_name', 'about', 'birth_date'];

        $fullNameParts = [];
        if ($minister->isFieldVisible('last_name')) {
            $fullNameParts[] = $minister->last_name;
        }
        if ($minister->isFieldVisible('name')) {
            $fullNameParts[] = $minister->name;
        }
        if ($minister->isFieldVisible('middle_name')) {
            $fullNameParts[] = $minister->middle_name;
        }
        $data['full_name'] = implode(' ', $fullNameParts) ?: 'Служитель';

        foreach ($fields as $field) {
            if ($minister->isFieldVisible($field)) {
                if ($field === 'birth_date' && $minister->birth_date) {
                    $data[$field] = $minister->birth_date->format('Y-m-d');
                } else {
                    $data[$field] = $minister->$field;
                }
            }
        }

        if ($minister->isFieldVisible('email')) {
            $data['email'] = $minister->email;
        }

        if ($minister->isFieldVisible('avatar')) {
            $data['avatar_url'] = $minister->avatar_url;
        }

        $data['social_links'] = $minister->socialLinks;

        $data['minister_categories'] = $minister->ministerCategories->map(fn ($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'slug' => $cat->slug,
            'icon' => $cat->icon,
            'color' => $cat->color,
        ]);

        return $data;
    }
}
