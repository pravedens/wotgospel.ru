<?php

namespace App\Filament\Resources\BibleResource\Pages;

use App\Filament\Resources\BibleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBibles extends ListRecords
{
    protected static string $resource = BibleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
