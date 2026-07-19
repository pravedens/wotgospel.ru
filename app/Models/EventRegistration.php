<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    protected $table = 'event_registrations';
    
    protected $fillable = [
        'event_id',
        'user_id',
        'selected_service_ids',
        'services_count',
        'status',
        'amount',
        'payment_status',
        'payment_id',
        'processed_by',
        'processed_at',
        'admin_notes',
    ];
    
    protected $casts = [
        'selected_service_ids' => 'array',
        'processed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];
    
    protected $with = ['event.conferenceServices', 'user'];
    
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
    
    public function getSelectedServicesAttribute()
    {
        if (!$this->selected_service_ids) {
            return collect();
        }
        
        return $this->event->conferenceServices
            ->whereIn('id', $this->selected_service_ids)
            ->values();
    }
    
    public function getSelectedServiceIdsArrayAttribute(): array
    {
        return $this->selected_service_ids ?? [];
    }
    
    public function getSelectedServicesDetailsAttribute(): array
    {
        $serviceIds = $this->selected_service_ids ?? [];
        
        if (empty($serviceIds)) {
            return [];
        }
        
        // Принудительная загрузка связей
        if (!$this->relationLoaded('event')) {
            $this->load('event.conferenceServices');
        } elseif ($this->event && !$this->event->relationLoaded('conferenceServices')) {
            $this->event->load('conferenceServices');
        }
        
        if (!$this->event || !$this->event->conferenceServices || $this->event->conferenceServices->isEmpty()) {
            return [];
        }
        
        $result = [];
        foreach ($serviceIds as $serviceId) {
            $service = $this->event->conferenceServices->where('id', $serviceId)->first();
            if ($service) {
                $result[] = [
                    'id' => $service->id,
                    'title' => $service->title,
                    'date' => $service->service_date ? $service->service_date->format('d.m.Y') : '',
                    'time' => $service->start_time ? substr($service->start_time, 0, 5) : '',
                ];
            }
        }
        
        return $result;
    }
    
    public function getSelectedServicesFormattedAttribute(): string
    {
        $services = $this->selected_services_details;
        if (empty($services)) {
            return '-';
        }
        
        $lines = [];
        foreach ($services as $service) {
            $lines[] = trim("{$service['date']} {$service['time']} - {$service['title']}");
        }
        
        return implode("\n", $lines);
    }
    
    public function getSelectedServicesListAttribute(): string
{
    $serviceIds = $this->selected_service_ids ?? [];
    if (empty($serviceIds)) {
        return '-';
    }
    
    // Загружаем служения, если ещё не загружены
    if (!$this->relationLoaded('event')) {
        $this->load('event.conferenceServices');
    } elseif ($this->event && !$this->event->relationLoaded('conferenceServices')) {
        $this->event->load('conferenceServices');
    }
    
    if (!$this->event || !$this->event->conferenceServices) {
        return '-';
    }
    
    $result = [];
    foreach ($serviceIds as $id) {
        $service = $this->event->conferenceServices->where('id', $id)->first();
        if ($service) {
            $date = $service->service_date ? $service->service_date->format('d.m.Y') : 'дата не указана';
            $time = $service->start_time ? substr($service->start_time, 0, 5) : '';
            $result[] = "{$date} {$time} - {$service->title}";
        }
    }
    
    return implode("\n", $result);
}
}