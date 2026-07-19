<?php
// app/Filament/Resources/BibleLessonResource/Pages/ListBibleLessons.php

namespace App\Filament\Resources\BibleLessonResource\Pages;

use App\Filament\Resources\BibleLessonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBibleLessons extends ListRecords
{
    protected static string $resource = BibleLessonResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}