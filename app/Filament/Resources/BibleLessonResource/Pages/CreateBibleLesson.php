<?php
// app/Filament/Resources/BibleLessonResource/Pages/CreateBibleLesson.php

namespace App\Filament\Resources\BibleLessonResource\Pages;

use App\Filament\Resources\BibleLessonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBibleLesson extends CreateRecord
{
    protected static string $resource = BibleLessonResource::class;
    
        protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}