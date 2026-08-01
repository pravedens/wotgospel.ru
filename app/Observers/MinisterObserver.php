<?php

namespace App\Observers;

use App\Models\MinisterCategory;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class MinisterObserver
{
    /**
     * Handle the User "saved" event.
     */
    public function saved(User $user): void
    {
        if ($user->hasRole('minister')) {
            $this->clearCache();
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        if ($user->hasRole('minister')) {
            $this->clearCache();
        }
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        if ($user->hasRole('minister')) {
            $this->clearCache();
        }
    }

    /**
     * Handle the User "forceDeleted" event.
     */
    public function forceDeleted(User $user): void
    {
        if ($user->hasRole('minister')) {
            $this->clearCache();
        }
    }

    /**
     * Очистка кеша служителей
     */
    private function clearCache(): void
    {
        $locale = app()->getLocale();

        // Очищаем кеш списка служителей
        Cache::forget('ministers_list_'.md5('[]').'_'.$locale);

        // Очищаем кеш категорий служителей
        Cache::forget('minister_categories_'.$locale);

        // Очищаем кеш по категориям
        $categories = MinisterCategory::all();
        foreach ($categories as $category) {
            Cache::forget('ministers_by_category_'.$category->slug.'_'.$locale);
        }
    }
}
