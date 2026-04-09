<?php

namespace App\Filament\Resources\DenominationResource\Pages;

use App\Filament\Resources\DenominationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDenominations extends ListRecords
{
    protected static string $resource = DenominationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
