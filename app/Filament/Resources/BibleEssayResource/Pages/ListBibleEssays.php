<?php
// app/Filament/Resources/BibleEssayResource/Pages/ListBibleEssays.php

namespace App\Filament\Resources\BibleEssayResource\Pages;

use App\Filament\Resources\BibleEssayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBibleEssays extends ListRecords
{
    protected static string $resource = BibleEssayResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}