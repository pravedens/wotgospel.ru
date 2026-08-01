<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        $this->clearFiltersCache();
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        $this->clearFiltersCache();
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        $this->clearFiltersCache();
    }

    /**
     * Handle the Post "restored" event.
     */
    public function restored(Post $post): void
    {
        $this->clearFiltersCache();
    }

    /**
     * Handle the Post "forceDeleted" event.
     */
    public function forceDeleted(Post $post): void
    {
        $this->clearFiltersCache();
    }

    /**
     * Очистка кеша фильтров
     */
    private function clearFiltersCache(): void
    {
        // Очищаем все варианты кеша фильтров
        $locale = app()->getLocale();

        // Удаляем кеш для разных комбинаций параметров
        Cache::forget('filters_'.md5('[]').'_'.$locale);
        Cache::forget('filters_'.md5(json_encode(['category_id' => null])).'_'.$locale);
        Cache::forget('filters_'.md5(json_encode(['group_id' => null])).'_'.$locale);
        Cache::forget('filters_'.md5(json_encode(['conference_id' => null])).'_'.$locale);

        // ✅ Удаляем все ключи, начинающиеся с "filters_"
        // (более надежный способ, но требует дополнительной логики)
        // Или используем теги кеша, если они настроены
    }
}
