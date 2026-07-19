<?php
// app/Filament/Resources/BibleEnrollmentRequestResource/Pages/EditBibleEnrollmentRequest.php

namespace App\Filament\Resources\BibleEnrollmentRequestResource\Pages;

use App\Filament\Resources\BibleEnrollmentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBibleEnrollmentRequest extends EditRecord
{
    protected static string $resource = BibleEnrollmentRequestResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}