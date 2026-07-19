<?php

namespace App\Filament\Resources\MinisterCategoryResource\Pages;

use App\Filament\Resources\MinisterCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMinisterCategory extends EditRecord
{
    protected static string $resource = MinisterCategoryResource::class;

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
