<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => $this->content,
            'info' => $this->info,
            'thumbnail' => $this->thumbnail ? url('/storage/' . $this->thumbnail) : null,
            'startDate' => $this->startDate,
            'startTime' => $this->startTime,
            'startWeek' => $this->startWeek,
            'startDay' => $this->startDay,
            'startMonth' => $this->startMonth,
            'display_date' => $this->display_date,
            'display_date_time' => $this->display_date_time,
            'event_date' => $this->event_date,
            'event_time' => $this->event_time,
            'color' => $this->color ?? '#3b82f6',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}