<?php

namespace App\Filament\Resources\BibleResource\Pages;

use App\Filament\Resources\BibleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBible extends EditRecord
{
    protected static string $resource = BibleResource::class;

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
