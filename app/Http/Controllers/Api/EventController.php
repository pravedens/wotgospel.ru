<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class EventController extends Controller
{
    /**
     * Проверка, может ли пользователь редактировать события
     */
    private function canEditEvents($user): bool
    {
        if (!$user) return false;
        
        return $user->hasRole('super_admin') || 
               $user->hasRole('admin') || 
               $user->can('edit_events') ||
               $user->can('create_events') ||
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
     * Проверка, является ли пользователь членом семьи
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
                ->where('show_in_carousel', true)
                ->whereDate('startDate', '>=', Carbon::now());

            // Логика видимости
if (!$canEdit) {
    if ($this->isPastor($user) || $isMinister) {
        // Пасторы и служители видят: обычные + для членов + для служителей
        $query->where(function($q) {
            $q->where('members_only', false)
              ->orWhere('members_only', true)
              ->orWhere('ministers_only', true);
        });
    } elseif ($isMember) {
        // Члены церкви видят: обычные + для членов (НЕ видят для служителей)
        $query->where(function($q) {
            $q->where('members_only', false)
              ->orWhere('members_only', true);
        })->where('ministers_only', false);
    } else {
        // Обычные пользователи видят только обычные события
        $query->where('members_only', false)
              ->where('ministers_only', false);
    }
}

            $events = $query
                ->orderBy('startDate', 'asc')
                ->orderBy('startTime', 'asc')
                ->limit($limit)
                ->get()
                ->map(function($event) use ($canEdit) {
                    // Формируем URL картинки из поля thumbnail
                    $imageUrl = null;
                    if ($event->thumbnail) {
                        if (filter_var($event->thumbnail, FILTER_VALIDATE_URL)) {
                            $imageUrl = $event->thumbnail;
                        } 
                        elseif (str_starts_with($event->thumbnail, 'events/thumbnails/')) {
                            $imageUrl = "https://storage.yandexcloud.net/wotgospel-media/{$event->thumbnail}";
                        }
                        elseif (str_starts_with($event->thumbnail, 'public/')) {
                            $imageUrl = "https://wotgospel.ru/storage/" . str_replace('public/', '', $event->thumbnail);
                        }
                        else {
                            $imageUrl = "https://storage.yandexcloud.net/wotgospel-media/events/thumbnails/{$event->thumbnail}";
                        }
                    }

                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'slug' => $event->slug,
                        'description' => $event->description,
                        'startDate' => $event->startDate,
                        'startTime' => $event->startTime ? Carbon::parse($event->startTime)->format('H:i') : null,
                        'color' => $event->color ?? '#3b82f6',
                        'members_only' => $event->members_only,
                        'ministers_only' => $event->ministers_only,
                        'can_edit' => $canEdit,
                        'thumbnail' => $event->thumbnail,
                        'image_url' => $imageUrl,
                    ];
                });

            return response()->json($events);

        } catch (\Exception $e) {
            Log::error('Upcoming events error: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Получение событий за месяц
     */
    public function index(Request $request)
    {
        // Вручную получаем пользователя из токена (если он передан)
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

            $query = Event::whereBetween('startDate', [$startDate, $endDate])
                ->orderBy('startDate')
                ->orderBy('startTime');

            // Логика видимости
if (!$canEdit) {
    // Не показываем прошедшие события обычным пользователям
    $query->whereDate('startDate', '>=', Carbon::now()->startOfDay());
    
    // ✅ Пастор и служитель имеют одинаковые права на просмотр
    if ($this->isPastor($user) || $isMinister) {
        // Пасторы и служители видят: обычные + для членов + для служителей
        $query->where(function($q) {
            $q->where('members_only', false)
              ->orWhere('members_only', true)
              ->orWhere('ministers_only', true);
        });
    } elseif ($isMember) {
        // Члены церкви видят: обычные + для членов (НЕ видят для служителей)
        $query->where(function($q) {
            $q->where('members_only', false)
              ->orWhere('members_only', true);
        })->where('ministers_only', false);
    } else {
        // Обычные пользователи видят только обычные события
        $query->where('members_only', false)
              ->where('ministers_only', false);
    }
}

            $events = $query->get();
            
            // Группируем события по дням для календаря
            $eventsByDay = [];
            foreach ($events as $event) {
                $day = Carbon::parse($event->startDate)->day;
                if (!isset($eventsByDay[$day])) {
                    $eventsByDay[$day] = [];
                }
                
                // Форматируем время
                $formattedTime = null;
                if ($event->startTime) {
                    $formattedTime = Carbon::parse($event->startTime)->format('H:i');
                } elseif ($event->time) {
                    $formattedTime = Carbon::parse($event->time)->format('H:i');
                }
                
                $eventsByDay[$day][] = [
                    'id' => $event->id,
                    'title' => $event->title,
                    'slug' => $event->slug,
                    'time' => $formattedTime,
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
                ];
            }

            return response()->json([
                'year' => $year,
                'month' => $month,
                'events' => $eventsByDay,
                'is_admin' => $canEdit,
                'list' => $events->map(function($event) use ($canEdit) {
                    $formattedTime = null;
                    if ($event->startTime) {
                        $formattedTime = Carbon::parse($event->startTime)->format('H:i');
                    } elseif ($event->time) {
                        $formattedTime = Carbon::parse($event->time)->format('H:i');
                    }
                    
                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'slug' => $event->slug,
                        'description' => $event->description,
                        'thumbnail' => $event->thumbnail,
                        'startDate' => $event->startDate,
                        'startTime' => $event->startTime,
                        'time' => $formattedTime,
                        'color' => $event->color ?? '#3b82f6',
                        'show_in_carousel' => $event->show_in_carousel,
                        'is_published' => $event->is_published,
                        'members_only' => $event->members_only,
                        'ministers_only' => $event->ministers_only,
                        'is_past' => Carbon::parse($event->startDate)->isPast(),
                        'can_edit' => $canEdit,
                    ];
                }),
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
     * Получение конкретного события
     */
    public function show(Request $request, $slug)
    {
        // Вручную получаем пользователя из токена (если он передан)
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
            
            $event = Event::where('slug', $slug)->first();

            if (!$event) {
                return response()->json(['message' => 'Событие не найдено'], 404);
            }

            // Проверка на прошедшее событие
            $eventDate = Carbon::parse($event->startDate);
            if ($eventDate->isPast() && !$canEdit) {
                return response()->json(['message' => 'Событие уже прошло'], 410);
            }

            // Проверка прав на просмотр событий только для членов церкви
            if ($event->members_only && !$canEdit && !$isMember) {
                return response()->json(['message' => 'Доступ запрещён. Это событие только для членов церкви.'], 403);
            }

            // Проверка прав на просмотр событий только для служителей
            if ($event->ministers_only && !$canEdit && !$isMinister) {
                return response()->json(['message' => 'Доступ запрещён. Это событие только для служителей.'], 403);
            }

            // Форматируем время
            $formattedTime = null;
            if ($event->startTime) {
                $formattedTime = Carbon::parse($event->startTime)->format('H:i');
            } elseif ($event->time) {
                $formattedTime = Carbon::parse($event->time)->format('H:i');
            }

            return response()->json([
                'id' => $event->id,
                'title' => $event->title,
                'slug' => $event->slug,
                'description' => $event->description,
                'content' => $event->content,
                'info' => $event->info,
                'thumbnail' => $event->thumbnail,
                'startDate' => $event->startDate,
                'startTime' => $event->startTime,
                'time' => $formattedTime,
                'color' => $event->color ?? '#3b82f6',
                'show_in_carousel' => $event->show_in_carousel,
                'is_published' => $event->is_published,
                'members_only' => $event->members_only,
                'ministers_only' => $event->ministers_only,
                'is_past' => $eventDate->isPast(),
                'can_edit' => $canEdit,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ]);

        } catch (\Exception $e) {
            Log::error('Event show error: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка загрузки события'], 500);
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

            $query = Event::where('show_in_carousel', true)
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
                'total' => Event::whereDate('startDate', '>=', Carbon::now())->count(),
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
}