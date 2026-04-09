<?php

namespace App\Filament\Resources\DenominationResource\Pages;

use App\Filament\Resources\DenominationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDenomination extends CreateRecord
{
    protected static string $resource = DenominationResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
