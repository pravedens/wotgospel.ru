<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Select;
use App\Models\BibleVerse;

class BibleVerseSelect extends Select
{
    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->searchable()
            ->options(function () {
                return BibleVerse::limit(100)
                    ->get()
                    ->mapWithKeys(fn ($verse) => [
                        $verse->id => "{$verse->book} {$verse->chapter}:{$verse->verse}"
                    ]);
            })
            ->getSearchResultsUsing(function (string $search) {
                if (strlen($search) < 2) {
                    return [];
                }
                
                return BibleVerse::where('book', 'like', "%{$search}%")
                    ->orWhere('text', 'like', "%{$search}%")
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(fn ($verse) => [
                        $verse->id => "{$verse->book} {$verse->chapter}:{$verse->verse}"
                    ]);
            })
            ->getOptionLabelUsing(fn ($value) => $this->getVerseLabel($value));
    }
    
    private function getVerseLabel($value): string
    {
        if (!$value) {
            return '';
        }
        
        $verse = BibleVerse::find($value);
        
        if (!$verse) {
            return '';
        }
        
        return "{$verse->book} {$verse->chapter}:{$verse->verse}";
    }
}