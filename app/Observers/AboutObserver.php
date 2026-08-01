<?php

namespace App\Observers;

use App\Models\About;
use Illuminate\Support\Facades\Cache;

class AboutObserver
{
    /**
     * Handle the About "created" event.
     */
    public function created(About $about): void
    {
        $this->clearCache($about);
    }

    /**
     * Handle the About "updated" event.
     */
    public function updated(About $about): void
    {
        $this->clearCache($about);
    }

    /**
     * Handle the About "deleted" event.
     */
    public function deleted(About $about): void
    {
        $this->clearCache($about);
    }

    /**
     * Handle the About "restored" event.
     */
    public function restored(About $about): void
    {
        $this->clearCache($about);
    }

    /**
     * Handle the About "forceDeleted" event.
     */
    public function forceDeleted(About $about): void
    {
        $this->clearCache($about);
    }

    /**
     * Очистка кеша
     */
    private function clearCache(About $about): void
    {
        $locale = app()->getLocale();

        // Очищаем кеш категорий
        Cache::forget('denominations_list_'.$locale);

        // Очищаем кеш статей по категории
        if ($about->denomination) {
            Cache::forget('denomination_abouts_'.$about->denomination->slug.'_'.$locale);
        }

        // Очищаем общий кеш списка статей
        Cache::forget('abouts_list_'.md5('[]').'_'.$locale);
    }
}
