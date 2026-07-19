<?php
// app/Filament/Resources/BibleStudentResource/Pages/StudentProgress.php

namespace App\Filament\Resources\BibleStudentResource\Pages;

use App\Filament\Resources\BibleStudentResource;
use App\Models\BibleCourse;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentProgress extends Page
{
    protected static string $resource = BibleStudentResource::class;
    
    protected string $view = 'filament.resources.bible-student-resource.pages.student-progress';
    
    public $record;
    
    public function mount($record): void
    {
        $this->record = \App\Models\User::findOrFail($record);
    }
    
    public function getTitle(): string
    {
        return "Прогресс студента: {$this->record->full_name}";
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Назад к студентам')
                ->url(BibleStudentResource::getUrl())
                ->icon('heroicon-o-arrow-left'),
        ];
    }
    
    public function table(Table $table): Table
    {
        $courses = BibleCourse::where('is_published', true)->orderBy('order')->get();
        
        $data = [];
        foreach ($courses as $course) {
            $progress = $course->getProgressForUser($this->record->id);
            $data[] = [
                'course_title' => $course->title,
                'completed' => $progress['completed'],
                'total' => $progress['total'],
                'percentage' => $progress['percentage'],
            ];
        }
        
        return $table
            ->query(\Illuminate\Support\Collection::make($data))
            ->columns([
                TextColumn::make('course_title')
                    ->label('Курс'),
                
                TextColumn::make('completed')
                    ->label('Пройдено уроков'),
                
                TextColumn::make('total')
                    ->label('Всего уроков'),
                
                TextColumn::make('percentage')
                    ->label('Прогресс')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->color(fn ($state) => match(true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),
            ]);
    }
}