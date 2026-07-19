<?php
// app/Filament/Resources/BibleLessonCommentResource/Pages/ListBibleLessonComments.php

namespace App\Filament\Resources\BibleLessonCommentResource\Pages;

use App\Filament\Resources\BibleLessonCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBibleLessonComments extends ListRecords
{
    protected static string $resource = BibleLessonCommentResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}