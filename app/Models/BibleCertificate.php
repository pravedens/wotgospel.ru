<?php
// app/Models/BibleCertificate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BibleCertificate extends Model
{
    protected $table = 'bible_certificates';
    
    protected $fillable = [
        'user_id',
        'course_id',
        'certificate_uuid',
        'qrcode_url',
        'pdf_url',
        'issued_at',
    ];
    
    protected $casts = [
        'issued_at' => 'datetime',
    ];
    
    protected static function booted()
    {
        static::creating(function ($certificate) {
            if (empty($certificate->certificate_uuid)) {
                $certificate->certificate_uuid = (string) Str::uuid();
            }
        });
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(BibleCourse::class, 'course_id');
    }
    
    public function getVerificationUrl(): string
    {
        return config('app.frontend_url') . '/certificate/verify/' . $this->certificate_uuid;
    }
}