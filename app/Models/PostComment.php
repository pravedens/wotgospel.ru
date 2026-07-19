<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostComment extends Model
{
    protected $table = 'post_comments';
    
    protected $fillable = [
        'post_id', 'user_id', 'parent_id', 'content', 'is_approved', 'likes_count'
    ];
    
    protected $casts = [
        'is_approved' => 'boolean',
        'likes_count' => 'integer',
    ];
    
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }
    
    public function replies(): HasMany
    {
        return $this->hasMany(PostComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }
    
    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }
    
    public function isLikedByUser(int $userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }
    
    public function incrementLikesCount(): void
    {
        $this->increment('likes_count');
    }
    
    public function decrementLikesCount(): void
    {
        $this->decrement('likes_count');
    }
}