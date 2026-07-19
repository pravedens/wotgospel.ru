<?php
// app/Models/ConferenceService.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ConferenceService extends Model
{
    protected $fillable = [
        'event_id',
        'service_date',
        'title',
        'description',
        'start_time',
        'end_time',
        'speaker',
        'capacity',
        'order',
    ];
    
    protected $casts = [
        'service_date' => 'date',
        'start_time' => 'string',
        'end_time' => 'string',
        'capacity' => 'integer',
        'order' => 'integer',
    ];
    
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
    
    public function getRegisteredCountAttribute(): int
    {
        return $this->event->registrations()
            ->where('status', 'confirmed')
            ->whereJsonContains('selected_service_ids', $this->id)
            ->count();
    }
    
    public function getAvailableCountAttribute(): int
    {
        return max(0, $this->capacity - $this->registered_count);
    }
    
    public function getFormattedDateAttribute(): string
    {
        if (!$this->service_date) return '';
        return Carbon::parse($this->service_date)->format('d.m.Y');
    }
    
    public function getFormattedStartTimeAttribute(): string
    {
        if (!$this->start_time) return '';
        // Если пришло время в формате H:i:s
        return substr($this->start_time, 0, 5);
    }
    
    public function getFormattedEndTimeAttribute(): string
    {
        if (!$this->end_time) return '';
        return substr($this->end_time, 0, 5);
    }
    
    public function getTimeRangeAttribute(): string
    {
        if ($this->formatted_start_time && $this->formatted_end_time) {
            return $this->formatted_start_time . ' - ' . $this->formatted_end_time;
        }
        return $this->formatted_start_time ?: '';
    }
    
    public function getDisplayNameAttribute(): string
    {
        $parts = [];
        
        if ($this->formatted_date) {
            $parts[] = $this->formatted_date;
        }
        
        if ($this->title) {
            $parts[] = $this->title;
        }
        
        if ($this->time_range) {
            $parts[] = $this->time_range;
        }
        
        return implode(', ', $parts);
    }
}