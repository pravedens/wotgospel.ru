<?php
// app/Filament/Resources/BibleEnrollmentRequestResource/Pages/ListBibleEnrollmentRequests.php

namespace App\Filament\Resources\BibleEnrollmentRequestResource\Pages;

use App\Filament\Resources\BibleEnrollmentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBibleEnrollmentRequests extends ListRecords
{
    protected static string $resource = BibleEnrollmentRequestResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}