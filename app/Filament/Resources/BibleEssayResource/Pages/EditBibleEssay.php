<?php
// app/Filament/Resources/BibleEssayResource/Pages/EditBibleEssay.php

namespace App\Filament\Resources\BibleEssayResource\Pages;

use App\Filament\Resources\BibleEssayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBibleEssay extends EditRecord
{
    protected static string $resource = BibleEssayResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}