<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use App\Services\NotificationService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageOptimizer;

class EventController extends Controller
{
    protected $notificationService;

    /**
     * Конструктор с внедрением NotificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Проверка, может ли пользователь редактировать события
     */
    private function canEditEvents($user): bool
    {
        if (!$user) return false;
        
        return $user->hasRole('super_admin') || 
               $user->hasRole('admin') || 
               $user->hasRole('redactorEvents') ||
               $user->can('edit_events') ||
               $user->can('create_events') ||
               $user->can('update_event') || 
               $user->can('delete_events');
    }
    
    /**
     * Проверка, является ли пользователь пастором
     */
    private function isPastor($user): bool
    {
        return $user && $user->hasRole('pastor');
    }

    /**
     * Проверка, является ли пользователь прихожанином
     */
    private function isMember($user): bool
    {
        return $user && $user->hasRole('member');
    }

    /**
     * Проверка, является ли пользователь служителем
     */
    private function isMinister($user): bool
    {
        return $user && $user->hasRole('minister');
    }

    /**
     * Ближайшие события (для карусели)
     */
    public function upcoming(Request $request)
    {
        try {
            $limit = $request->get('limit', 5);
            $user = $request->user();
            $canEdit = $this->canEditEvents($user);
            $isMember = $this->isMember($user);
            $isMinister = $this->isMinister($user);

            $query = Event::query()
                ->with('conferenceServices')
                ->where('is_published', true)
                ->where('show_in_carousel', true)
                ->whereDate('startDate', '>=', Carbon::now());

            // Фильтры по правам доступа
            if (!$canEdit) {
                if ($this->isPastor($user) || $isMinister) {
                    $query->where(function($q) {
                        $q->where('members_only', false)
                          ->orWhere('members_only', true)
                          ->orWhere('ministers_only', true);
                    });
                } elseif ($isMember) {
                    $query->where(function($q) {
                        $q->where('members_only', false)
                          ->orWhere('members_only', true);
                    })->where('ministers_only', false);
                } else {
                    $query->where('members_only', false)
                          ->where('ministers_only', false);
                }
            }

            $events = $query->orderBy('startDate', 'asc')
                ->orderBy('startTime', 'asc')
                ->limit($limit)
                ->get();
            
            // Разворачиваем конференции в их служения
            $result = [];
            foreach ($events as $event) {
                if ($event->is_conference && $event->conferenceServices->count() > 0) {
                    foreach ($event->conferenceServices as $service) {
                        if (count($result) >= $limit) break;
                        $result[] = $this->formatEventForCarousel($event, $service);
                    }
                } else {
                    $result[] = $this->formatEventForCarousel($event);
                }
                if (count($result) >= $limit) break;
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Upcoming events error: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Форматирование события для карусели
     */
    private function formatEventForCarousel($event, $service = null)
    {
        $isService = $service !== null;
        $date = $isService ? $service->service_date : $event->startDate;
        $time = $isService ? $service->start_time : $event->startTime;
        $title = $isService ? $service->title : $event->title;
        $description = $isService ? ($service->description ?? $event->description) : $event->description;
        
        $imageUrl = null;
        if ($event->thumbnail) {
            if (filter_var($event->thumbnail, FILTER_VALIDATE_URL)) {
                $imageUrl = $event->thumbnail;
            } elseif (str_starts_with($event->thumbnail, 'events/thumbnails/')) {
                $imageUrl = "https://storage.yandexcloud.net/wotgospel-media/{$event->thumbnail}";
            } elseif (str_starts_with($event->thumbnail, 'public/')) {
                $imageUrl = "https://wotgospel.ru/storage/" . str_replace('public/', '', $event->thumbnail);
            } else {
                $imageUrl = "https://storage.yandexcloud.net/wotgospel-media/events/thumbnails/{$event->thumbnail}";
            }
        }

        return [
            'id' => $event->id,
            'title' => $title,
            'slug' => $event->slug,
            'description' => $description,
            'startDate' => $date,
            'startTime' => $time,
            'time' => $time ? substr($time, 0, 5) : null,
            'color' => $event->color ?? '#3b82f6',
            'members_only' => $event->members_only,
            'ministers_only' => $event->ministers_only,
            'can_edit' => $this->canEditEvents($event->creator),
            'thumbnail' => $event->thumbnail,
            'image_url' => $imageUrl,
            'is_conference' => $event->is_conference,
            'conference_service_id' => $isService ? $service->id : null,
        ];
    }
    
    /**
     * Получение событий за месяц
     */
    public function index(Request $request)
    {
        $token = $request->bearerToken();
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
                $request->setUserResolver(fn () => $user);
            }
        }
        
        try {
            $month = $request->month ?? now()->month;
            $year = $request->year ?? now()->year;

            $startDate = Carbon::create($year, $month, 1)->startOfDay();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

            $user = $request->user();
            $canEdit = $this->canEditEvents($user);
            $isMember = $this->isMember($user);
            $isMinister = $this->isMinister($user);

            // Базовый запрос
            $query = Event::whereBetween('startDate', [$startDate, $endDate])
                ->with('conferenceServices')
                ->orderBy('startDate')
                ->orderBy('startTime');

            // Фильтры по правам доступа
            if (!$canEdit) {
                $query->where('is_published', true)
                      ->whereDate('startDate', '>=', Carbon::now()->startOfDay());
                
                if ($this->isPastor($user) || $isMinister) {
                    $query->where(function($q) {
                        $q->where('members_only', false)
                          ->orWhere('members_only', true)
                          ->orWhere('ministers_only', true);
                    });
                } elseif ($isMember) {
                    $query->where(function($q) {
                        $q->where('members_only', false)
                          ->orWhere('members_only', true);
                    })->where('ministers_only', false);
                } else {
                    $query->where('members_only', false)
                          ->where('ministers_only', false);
                }
            }

            $events = $query->get();
            
            $eventsByDay = [];
            $listEvents = [];
            
            foreach ($events as $event) {
                if ($event->is_conference && $event->conferenceServices->count() > 0) {
                    foreach ($event->conferenceServices as $service) {
                        $serviceDate = Carbon::parse($service->service_date);
                        $day = $serviceDate->day;
                        
                        if ($serviceDate->month != $month || $serviceDate->year != $year) {
                            continue;
                        }
                        
                        $calendarEvent = [
                            'id' => $event->id,
                            'title' => $service->title,
                            'slug' => $event->slug,
                            'time' => $service->start_time ? substr($service->start_time, 0, 5) : null,
                            'color' => $event->color ?? '#3b82f6',
                            'description' => $service->description ?? $event->description,
                            'startDate' => $service->service_date,
                            'startTime' => $service->start_time,
                            'show_in_carousel' => $event->show_in_carousel,
                            'is_published' => $event->is_published,
                            'members_only' => $event->members_only,
                            'ministers_only' => $event->ministers_only,
                            'is_past' => $serviceDate->isPast(),
                            'can_edit' => $canEdit,
                            'is_conference' => true,
                            'conference_service_id' => $service->id,
                            'conference_title' => $event->title,
                            'attendees_count' => $event->attendees_count,
                            'is_cancelled' => (!$event->is_published && !Carbon::parse($event->startDate)->isPast()),
                        ];
                        
                        if (!isset($eventsByDay[$day])) {
                            $eventsByDay[$day] = [];
                        }
                        $eventsByDay[$day][] = $calendarEvent;
                        
                        $listEvents[] = [
                            'id' => $event->id,
                            'title' => $service->title,
                            'slug' => $event->slug,
                            'description' => $service->description ?? $event->description,
                            'thumbnail' => $event->thumbnail,
                            'startDate' => $service->service_date,
                            'startTime' => $service->start_time,
                            'time' => $service->start_time ? substr($service->start_time, 0, 5) : null,
                            'color' => $event->color ?? '#3b82f6',
                            'show_in_carousel' => $event->show_in_carousel,
                            'is_published' => $event->is_published,
                            'members_only' => $event->members_only,
                            'ministers_only' => $event->ministers_only,
                            'is_past' => $serviceDate->isPast(),
                            'can_edit' => $canEdit,
                            'is_conference' => true,
                            'conference_service_id' => $service->id,
                            'conference_title' => $event->title,
                            'attendees_count' => $event->attendees_count,
                            'is_cancelled' => (!$event->is_published && !Carbon::parse($event->startDate)->isPast()),
                        ];
                    }
                } else {
                    $day = Carbon::parse($event->startDate)->day;
                    $localTime = null;
                    if ($event->startTime) {
                        $localTime = Carbon::parse($event->startTime)->format('H:i');
                    }
                    
                    $calendarEvent = [
                        'id' => $event->id,
                        'title' => $event->title,
                        'slug' => $event->slug,
                        'time' => $localTime,
                        'color' => $event->color ?? '#3b82f6',
                        'description' => $event->description,
                        'startDate' => $event->startDate,
                        'startTime' => $event->startTime,
                        'show_in_carousel' => $event->show_in_carousel,
                        'is_published' => $event->is_published,
                        'members_only' => $event->members_only,
                        'ministers_only' => $event->ministers_only,
                        'is_past' => Carbon::parse($event->startDate)->isPast(),
                        'can_edit' => $canEdit,
                        'is_conference' => false,
                        'attendees_count' => $event->attendees_count,
                        'is_cancelled' => (!$event->is_published && !Carbon::parse($event->startDate)->isPast()),
                    ];
                    
                    if (!isset($eventsByDay[$day])) {
                        $eventsByDay[$day] = [];
                    }
                    $eventsByDay[$day][] = $calendarEvent;
                    
                    $listEvents[] = [
                        'id' => $event->id,
                        'title' => $event->title,
                        'slug' => $event->slug,
                        'description' => $event->description,
                        'thumbnail' => $event->thumbnail,
                        'startDate' => $event->startDate,
                        'startTime' => $event->startTime,
                        'time' => $localTime,
                        'color' => $event->color ?? '#3b82f6',
                        'show_in_carousel' => $event->show_in_carousel,
                        'is_published' => $event->is_published,
                        'members_only' => $event->members_only,
                        'ministers_only' => $event->ministers_only,
                        'is_past' => $event->isPast(),
                        'can_edit' => $canEdit,
                        'is_conference' => false,
                        'attendees_count' => $event->attendees_count,
                        'is_cancelled' => (!$event->is_published && !Carbon::parse($event->startDate)->isPast()),
                    ];
                }
            }
            
            foreach ($eventsByDay as $day => $dayEvents) {
                usort($dayEvents, function($a, $b) {
                    return ($a['time'] ?? '99:99') <=> ($b['time'] ?? '99:99');
                });
                $eventsByDay[$day] = $dayEvents;
            }
            
            usort($listEvents, function($a, $b) {
                return ($a['startDate'] ?? '') <=> ($b['startDate'] ?? '');
            });

            return response()->json([
                'year' => $year,
                'month' => $month,
                'events' => $eventsByDay,
                'is_admin' => $canEdit,
                'list' => $listEvents,
            ]);

        } catch (\Exception $e) {
            Log::error('Events index error: ' . $e->getMessage());
            return response()->json([
                'year' => $request->year ?? now()->year,
                'month' => $request->month ?? now()->month,
                'events' => [],
                'is_admin' => false,
                'list' => [],
            ], 500);
        }
    }
    
    /**
     * Создание нового события
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$this->canEditEvents($user)) {
                return response()->json(['message' => 'У вас нет прав на создание событий'], 403);
            }
            
            $validated = $request->validate([
                'title' => 'required_if:is_conference,false|string|max:255',
                'slug' => 'nullable|string|unique:events,slug',
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'info' => 'nullable|string',
                'startDate' => 'required_if:is_conference,false|date',
                'startTime' => 'nullable|date_format:H:i',
                'thumbnail' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'color' => 'nullable|string',
                'show_in_carousel' => 'boolean',
                'is_published' => 'boolean',
                'members_only' => 'boolean',
                'ministers_only' => 'boolean',
                'is_conference' => 'boolean',
                'conference_services' => 'nullable|json',
            ]);
            
            $eventData = [
                'title' => $validated['title'] ?? 'Конференция',
                'description' => $validated['description'] ?? ($request->is_conference ? 'Конференция' : null),
                'content' => $validated['content'] ?? null,
                'info' => $validated['info'] ?? null,
                'startDate' => $validated['startDate'] ?? now(),
                'startTime' => $validated['startTime'] ?? null,
                'color' => $validated['color'] ?? '#3b82f6',
                'show_in_carousel' => $validated['show_in_carousel'] ?? false,
                'is_published' => $validated['is_published'] ?? true,
                'members_only' => $validated['members_only'] ?? false,
                'ministers_only' => $validated['ministers_only'] ?? false,
                'is_conference' => $validated['is_conference'] ?? false,
                'created_by' => $user->id,
            ];
            
            if (empty($validated['slug'])) {
                $eventData['slug'] = Str::slug($eventData['title']) . '-' . uniqid();
            } else {
                $eventData['slug'] = $validated['slug'];
            }
            
            if ($request->is_conference && $request->conference_services) {
                $services = json_decode($request->conference_services, true);
                
                if (!empty($services)) {
                    $firstService = $services[0];
                    $eventData['title'] = 'Конференция: ' . ($firstService['title'] ?? 'Мероприятие');
                    $eventData['startDate'] = $firstService['service_date'] ?? now();
                    $eventData['startTime'] = $firstService['start_time'] ?? null;
                    $eventData['description'] = 'Конференция с ' . count($services) . ' служениями';
                    
                    $servicesList = '';
                    foreach ($services as $i => $s) {
                        $servicesList .= ($i+1) . '. ' . ($s['title'] ?? 'Служение') . 
                                        ' — ' . ($s['service_date'] ?? 'дата не указана');
                        if (!empty($s['start_time'])) {
                            $servicesList .= ' в ' . $s['start_time'];
                        }
                        if (!empty($s['speaker'])) {
                            $servicesList .= ' (спикер: ' . $s['speaker'] . ')';
                        }
                        $servicesList .= "\n";
                    }
                    $eventData['content'] = "📅 Программа конференции:\n\n" . $servicesList;
                }
            }
            
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                $optimizedPath = ImageOptimizer::optimizeAndStore(
                    file: $file,
                    directory: 'events/thumbnails',
                    width: 1200,
                    height: 800,
                    quality: 85
                );
                
                if ($optimizedPath) {
                    $thumbnailPath = $optimizedPath;
                    \Log::info('Thumbnail uploaded for event', ['path' => $thumbnailPath]);
                } else {
                    $filename = Str::slug($eventData['title']) . '-' . uniqid() . '.webp';
                    $path = $file->storeAs('events/thumbnails', $filename, 's3');
                    $thumbnailPath = $path;
                    \Log::warning('Image optimization failed, using original', ['path' => $path]);
                }
            }
            
            $eventData['thumbnail'] = $thumbnailPath;
            
            $event = Event::create($eventData);
            
            if ($request->is_conference && $request->conference_services) {
                $services = json_decode($request->conference_services, true);
                foreach ($services as $service) {
                    $event->conferenceServices()->create([
                        'service_date' => $service['service_date'] ?? null,
                        'title' => $service['title'] ?? 'Служение',
                        'description' => $service['description'] ?? null,
                        'start_time' => $service['start_time'] ?? null,
                        'speaker' => $service['speaker'] ?? null,
                        'capacity' => $service['capacity'] ?? 0,
                    ]);
                }
            }
            
            if (isset($this->notificationService)) {
                $this->notificationService->notifyNewEvent($event);
            }
            
            return response()->json([
                'message' => 'Событие успешно создано',
                'event' => $event->load('conferenceServices')
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Event store error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ошибка создания события: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обновление события
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$this->canEditEvents($user)) {
                return response()->json(['message' => 'У вас нет прав на редактирование событий'], 403);
            }
            
            $event = Event::findOrFail($id);
            
            // 🆕 Сохраняем original значения ДО изменения
            $originalIsPublished = $event->is_published;
            $originalStatus = $event->status ?? 'active';
            
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'slug' => 'sometimes|string|unique:events,slug,' . $id,
                'description' => 'nullable|string',
                'content' => 'nullable|string',
                'info' => 'nullable|string',
                'startDate' => 'sometimes|date',
                'startTime' => 'nullable|date_format:H:i',
                'thumbnail' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'color' => 'nullable|string',
                'show_in_carousel' => 'boolean',
                'is_published' => 'boolean',
                'members_only' => 'boolean',
                'ministers_only' => 'boolean',
                'is_conference' => 'boolean',
                'conference_services' => 'nullable|json',
            ]);
            
            $eventData = collect($validated)
                ->except(['conference_services'])
                ->toArray();
            
            // 🆕 Проверяем, было ли событие опубликовано и стало неопубликованным (отмена)
            $newIsPublished = $request->input('is_published', $event->is_published);
            
            if ($originalIsPublished === true && $newIsPublished === false) {
                // Отправляем уведомления об отмене всем, кто записался
                \Log::info('Event being unpublished (cancelled)', [
                    'event_id' => $event->id,
                    'event_title' => $event->title
                ]);
                
                $this->notificationService->sendEventCancellationNotifications($event);
                
                // Обновляем статус
                $eventData['status'] = 'cancelled';
            } 
            // Если событие снова опубликовали
            elseif ($originalIsPublished === false && $newIsPublished === true) {
                $eventData['status'] = 'active';
            }
            
            // Если это конференция и переданы служения — обновляем их
            if ($request->has('is_conference') && $request->is_conference && $request->has('conference_services')) {
                $services = json_decode($request->conference_services, true);
                
                if (!empty($services)) {
                    $firstService = $services[0];
                    $eventData['startDate'] = $firstService['service_date'] ?? $event->startDate;
                    $eventData['startTime'] = $firstService['start_time'] ?? null;
                    $eventData['description'] = 'Конференция с ' . count($services) . ' служениями';
                    
                    $servicesList = '';
                    foreach ($services as $i => $s) {
                        $servicesList .= ($i+1) . '. ' . ($s['title'] ?? 'Служение') . 
                                        ' — ' . ($s['service_date'] ?? 'дата не указана');
                        if (!empty($s['start_time'])) {
                            $servicesList .= ' в ' . $s['start_time'];
                        }
                        if (!empty($s['speaker'])) {
                            $servicesList .= ' (спикер: ' . $s['speaker'] . ')';
                        }
                        $servicesList .= "\n";
                    }
                    $eventData['content'] = "📅 Программа конференции:\n\n" . $servicesList;
                    
                    $event->conferenceServices()->delete();
                    foreach ($services as $service) {
                        $event->conferenceServices()->create([
                            'service_date' => $service['service_date'] ?? null,
                            'title' => $service['title'] ?? 'Служение',
                            'description' => $service['description'] ?? null,
                            'start_time' => $service['start_time'] ?? null,
                            'speaker' => $service['speaker'] ?? null,
                            'capacity' => $service['capacity'] ?? 0,
                        ]);
                    }
                }
            }
            
            if ($request->hasFile('thumbnail')) {
                $event->deleteThumbnail();
                
                $file = $request->file('thumbnail');
                $optimizedPath = ImageOptimizer::optimizeAndStore(
                    file: $file,
                    directory: 'events/thumbnails',
                    width: 1200,
                    height: 800,
                    quality: 85
                );
                
                if ($optimizedPath) {
                    $eventData['thumbnail'] = $optimizedPath;
                } else {
                    $filename = Str::slug($eventData['title'] ?? $event->title) . '-' . uniqid() . '.webp';
                    $path = $file->storeAs('events/thumbnails', $filename, 's3');
                    $eventData['thumbnail'] = $path;
                }
            }
            
            $event->update($eventData);
            
            return response()->json([
                'message' => 'Событие успешно обновлено',
                'event' => $event->load('conferenceServices')
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Event update error: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка обновления события: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Удаление события
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$this->canEditEvents($user)) {
                return response()->json(['message' => 'У вас нет прав на удаление событий'], 403);
            }
            
            $event = Event::findOrFail($id);
            
            $event->deleteThumbnail();
            
            $event->delete();
            
            \Log::info('Event deleted successfully', [
                'event_id' => $id,
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'message' => 'Событие успешно удалено'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Событие не найдено'], 404);
        } catch (\Exception $e) {
            \Log::error('Event destroy error: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка удаления события: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Получение конкретного события
     */
    public function show(Request $request, $slug)
    {
        $token = $request->bearerToken();
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
                $request->setUserResolver(fn () => $user);
            }
        }

        try {
            $user = $request->user();
            $canEdit = $this->canEditEvents($user);
            $isMember = $this->isMember($user);
            $isMinister = $this->isMinister($user);
            
            $event = Event::where('slug', $slug)->with('conferenceServices')->first();

            if (!$event) {
                return response()->json(['message' => 'Событие не найдено'], 404);
            }

            if (!$event->is_published && !$canEdit) {
                return response()->json(['message' => 'Событие не опубликовано'], 404);
            }

            if ($event->is_conference) {
                $hasUpcomingService = $event->conferenceServices()
                    ->where('service_date', '>=', Carbon::now())
                    ->exists();
                
                if (!$hasUpcomingService && !$canEdit && !$event->is_past) {
                    // Можно вернуть 410 или просто показать информацию
                }
            } else {
                if ($event->startTime) {
    $eventDate = Carbon::parse($event->startDate->format('Y-m-d') . ' ' . substr($event->startTime, 0, 5));
} else {
    $eventDate = Carbon::parse($event->startDate);
}
                //$eventDate = Carbon::parse($event->startDate);
                if ($eventDate->isPast() && !$canEdit) {
                    return response()->json(['message' => 'Событие уже прошло'], 410);
                }
            }

            if ($event->members_only && !$canEdit && !$isMember) {
                return response()->json(['message' => 'Доступ запрещён. Это событие только для прихожан.'], 403);
            }

            if ($event->ministers_only && !$canEdit && !$isMinister) {
                return response()->json(['message' => 'Доступ запрещён. Это событие только для служителей.'], 403);
            }

            $localTime = null;
            if (!$event->is_conference && $event->startTime) {
                $localTime = substr($event->startTime, 0, 5);
            }

            $response = [
                'id' => $event->id,
                'title' => $event->title,
                'slug' => $event->slug,
                'description' => $event->description,
                'content' => $event->content,
                'info' => $event->info,
                'thumbnail' => $event->thumbnail,
                'startDate' => $event->startDate
                    ? Carbon::parse($event->startDate)->format('Y-m-d')
                    : null,
                'startTime' => $event->is_conference ? null : $event->startTime,
                'time' => $localTime,
                'color' => $event->color ?? '#3b82f6',
                'show_in_carousel' => $event->show_in_carousel,
                'is_published' => $event->is_published,
                'members_only' => $event->members_only,
                'ministers_only' => $event->ministers_only,
                'is_past' => $event->isPast(),
                'can_edit' => $canEdit,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
                'is_conference' => $event->is_conference,
                // 🆕 Добавляем для кнопки «Я приду»
                'attendees_count' => $event->attendees_count,
                'user_attending' => $event->isUserAttending($user),
                'status' => $event->status ?? 'active',
                'is_cancelled' => ($event->status === 'cancelled' || (!$event->is_published && !$event->isPast())),
            ];
            
            if ($event->is_conference) {
                $eventModel = $event;
                $response['conference_services'] = $event->conferenceServices->map(function ($service) use ($eventModel) {
                    $registeredCount = 0;
                    try {
                        $registeredCount = $eventModel->registrations()
                            ->where('status', 'confirmed')
                            ->whereJsonContains('selected_service_ids', $service->id)
                            ->count();
                    } catch (\Exception $e) {
                        $registeredCount = 0;
                    }
                    
                    return [
                        'id' => $service->id,
                        'service_date' => $service->service_date ? $service->service_date->toIso8601String() : null,
                        'service_date_formatted' => $service->service_date ? $service->service_date->format('d.m.Y H:i') : null,
                        'title' => $service->title,
                        'description' => $service->description,
                        'speaker' => $service->speaker,
                        'start_time' => $service->start_time ? substr($service->start_time, 0, 5) : null,
                        'capacity' => $service->capacity,
                        'registered_count' => $registeredCount,
                        'available_count' => max(0, $service->capacity - $registeredCount),
                    ];
                });
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Event show error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'Ошибка загрузки события: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Статистика для карусели
     */
    public function carouselStats(Request $request)
    {
        try {
            $user = $request->user();
            $canEdit = $this->canEditEvents($user);
            $isMember = $this->isMember($user);
            $isMinister = $this->isMinister($user);
            $limit = config('app.carousel.events_limit', 6);

            $query = Event::where('is_published', true)
                ->where('show_in_carousel', true)
                ->whereDate('startDate', '>=', Carbon::now());

            if (!$canEdit) {
                if ($this->isPastor($user) || $isMinister) {
                    $query->where(function($q) {
                        $q->where('members_only', false)
                          ->orWhere('members_only', true)
                          ->orWhere('ministers_only', true);
                    });
                } elseif ($isMember) {
                    $query->where(function($q) {
                        $q->where('members_only', false)
                          ->orWhere('members_only', true);
                    })->where('ministers_only', false);
                } else {
                    $query->where('members_only', false)
                          ->where('ministers_only', false);
                }
            }

            $inCarousel = $query->count();

            return response()->json([
                'total' => Event::where('is_published', true)->whereDate('startDate', '>=', Carbon::now())->count(),
                'in_carousel' => $inCarousel,
                'limit' => $limit,
                'available' => max(0, $limit - $inCarousel),
            ]);

        } catch (\Exception $e) {
            Log::error('Carousel stats error: ' . $e->getMessage());
            return response()->json([
                'total' => 0,
                'in_carousel' => 0,
                'limit' => 6,
                'available' => 5,
            ], 500);
        }
    }
    
    /**
     * Добавить или удалить участника (кнопка «Я приду»)
     * POST - добавить, DELETE - удалить
     */
    public function attend(Request $request, $slug)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Необходимо авторизоваться'
                ], 401);
            }
            
            $event = Event::where('slug', $slug)->firstOrFail();
            
            // Проверяем, не прошло ли событие
            if ($event->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя записаться на прошедшее событие'
                ], 400);
            }
            
            // Проверяем права доступа к событию
            if (!$event->canBeViewedBy($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'У вас нет доступа к этому событию'
                ], 403);
            }
            
            $method = $request->method();
            
            if ($method === 'POST') {
                // Добавляем участника
                if ($event->isUserAttending($user)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Вы уже записаны на это событие'
                    ], 400);
                }
                
                $event->addAttendee($user);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Вы записаны на событие',
                    'attending' => true,
                    'attendees_count' => $event->attendees_count
                ]);
            }
            
            if ($method === 'DELETE') {
                // Удаляем участника
                if (!$event->isUserAttending($user)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Вы не записаны на это событие'
                    ], 400);
                }
                
                $event->removeAttendee($user);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Вы отменили запись на событие',
                    'attending' => false,
                    'attendees_count' => $event->attendees_count
                ]);
            }
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Событие не найдено'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Event attend error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сервера'
            ], 500);
        }
    }

    /**
     * Получить количество участников события
     */
    public function getAttendeesCount($slug)
    {
        try {
            $event = Event::where('slug', $slug)->firstOrFail();
            
            return response()->json([
                'success' => true,
                'attendees_count' => $event->attendees_count,
                'event_id' => $event->id
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Событие не найдено'
            ], 404);
        }
    }
}