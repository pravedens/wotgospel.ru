<?php
// app/Filament/Resources/BiblePartyResource/Pages/EditBibleParty.php

namespace App\Filament\Resources\BiblePartyResource\Pages;

use App\Filament\Resources\BiblePartyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBibleParty extends EditRecord
{
    protected static string $resource = BiblePartyResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}