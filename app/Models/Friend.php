<?php
// app/Models/Friend.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Friend extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'slug',
        'description',
        'thumbnail',
        'link',
        'sort_order',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    protected static function booted()
    {
        static::creating(function ($friend) {
            if (empty($friend->slug)) {
                $friend->slug = Str::slug($friend->title) . '-' . uniqid();
            }
        });
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }
    
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) {
            return null;
        }
        
        if (filter_var($this->thumbnail, FILTER_VALIDATE_URL)) {
            return $this->thumbnail;
        }
        
        if (str_starts_with($this->thumbnail, 'friends/')) {
            return 'https://storage.yandexcloud.net/wotgospel-media/' . $this->thumbnail;
        }
        
        return asset('storage/' . $this->thumbnail);
    }
}