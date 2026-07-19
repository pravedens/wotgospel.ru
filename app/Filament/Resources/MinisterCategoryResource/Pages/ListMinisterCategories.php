<?php

namespace App\Filament\Resources\MinisterCategoryResource\Pages;

use App\Filament\Resources\MinisterCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMinisterCategories extends ListRecords
{
    protected static string $resource = MinisterCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
