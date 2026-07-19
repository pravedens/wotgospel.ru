<?php
// app/Filament/Resources/BibleTestQuestionResource/Pages/ListBibleTestQuestions.php

namespace App\Filament\Resources\BibleTestQuestionResource\Pages;

use App\Filament\Resources\BibleTestQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBibleTestQuestions extends ListRecords
{
    protected static string $resource = BibleTestQuestionResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}