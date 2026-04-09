<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CarouselStats extends BaseWidget
{
    protected function getStats(): array
    {
        $limit = config('app.carousel.events_limit', 5);
        $inCarousel = Event::where('show_in_carousel', true)->count();
        $available = $limit - $inCarousel;
        
        return [
            Stat::make('События в карусели', "{$inCarousel} из {$limit}")
                ->description($available > 0 ? "Осталось мест: {$available}" : 'Лимит достигнут')
                ->descriptionIcon($available > 0 ? 'heroicon-m-arrow-up' : 'heroicon-m-exclamation-triangle')
                ->color($available > 0 ? 'success' : 'danger')
                ->chart([$inCarousel, $limit]),
                
            Stat::make('Всего событий', Event::count())
                ->description('В базе данных')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}