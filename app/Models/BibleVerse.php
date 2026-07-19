<?php
// app/Models/BibleVerse.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BibleVerse extends Model
{
    protected $table = 'bible_verses';
    
    protected $fillable = [
        'book',
        'chapter',
        'verse',
        'text',
        'book_abbr',
    ];
    
    protected $casts = [
        'chapter' => 'integer',
        'verse' => 'integer',
    ];
    
    public static function findByReference(string $reference): ?self
    {
        // Парсим "Ин. 3:16" или "Иоанна 3:16"
        $pattern = '/^([\pL\s\.]+?)\s*(\d+):(\d+)$/u';
        
        if (!preg_match($pattern, trim($reference), $matches)) {
            return null;
        }
        
        $book = trim($matches[1]);
        $chapter = (int)$matches[2];
        $verse = (int)$matches[3];
        
        return self::where(function ($query) use ($book) {
            $query->where('book', $book)
                  ->orWhere('book_abbr', $book);
        })
        ->where('chapter', $chapter)
        ->where('verse', $verse)
        ->first();
    }
    
    public function getReference(): string
    {
        return "{$this->book} {$this->chapter}:{$this->verse}";
    }
}