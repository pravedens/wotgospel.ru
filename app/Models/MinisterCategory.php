<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MinisterCategory extends Model
{
    protected $table = 'minister_categories';
    
    protected $fillable = ['name', 'slug', 'icon', 'color', 'description', 'sort_order'];
    
    protected $casts = [
        'sort_order' => 'integer',
    ];
    
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'category_user', 'category_id', 'user_id');
    }
    
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}