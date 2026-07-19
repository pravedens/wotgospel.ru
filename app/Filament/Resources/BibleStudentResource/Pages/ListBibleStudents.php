<?php
// app/Filament/Resources/BibleStudentResource/Pages/ListBibleStudents.php

namespace App\Filament\Resources\BibleStudentResource\Pages;

use App\Filament\Resources\BibleStudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBibleStudents extends ListRecords
{
    protected static string $resource = BibleStudentResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}