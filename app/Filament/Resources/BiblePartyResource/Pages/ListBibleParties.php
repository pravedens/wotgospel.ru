<?php
// app/Filament/Resources/BiblePartyResource/Pages/ListBibleParties.php

namespace App\Filament\Resources\BiblePartyResource\Pages;

use App\Filament\Resources\BiblePartyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBibleParties extends ListRecords
{
    protected static string $resource = BiblePartyResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}