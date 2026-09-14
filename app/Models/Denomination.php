<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Denomination extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug'
    ];

    public function about(): HasMany
    {
        return $this->hasMany(About::class);
    }
}
