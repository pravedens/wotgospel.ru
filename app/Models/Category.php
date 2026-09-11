<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtmlInput;

class Category extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
    'description' => CleanHtmlInput::class,
];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
