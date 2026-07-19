<?php
// app/Models/AboutViewsLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutViewsLog extends Model
{
    protected $table = 'about_views_logs';
    
    protected $fillable = [
        'about_id',
        'ip_address',
        'user_agent',
        'viewed_at',
    ];
    
    protected $casts = [
        'viewed_at' => 'date',
    ];
    
    public function about()
    {
        return $this->belongsTo(About::class, 'about_id');
    }
}