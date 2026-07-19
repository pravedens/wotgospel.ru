<?php

namespace App\Filament\Resources\MinisterCategoryResource\Pages;

use App\Filament\Resources\MinisterCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMinisterCategory extends CreateRecord
{
    protected static string $resource = MinisterCategoryResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
