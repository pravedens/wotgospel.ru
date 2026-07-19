<?php
// app/Filament/Resources/BibleCourseResource/Pages/ListBibleCourses.php

namespace App\Filament\Resources\BibleCourseResource\Pages;

use App\Filament\Resources\BibleCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBibleCourses extends ListRecords
{
    protected static string $resource = BibleCourseResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}