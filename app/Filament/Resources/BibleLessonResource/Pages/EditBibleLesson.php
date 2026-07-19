<?php
// app/Filament/Resources/BibleLessonResource/Pages/EditBibleLesson.php

namespace App\Filament\Resources\BibleLessonResource\Pages;

use App\Filament\Resources\BibleLessonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBibleLesson extends EditRecord
{
    protected static string $resource = BibleLessonResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
        protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}