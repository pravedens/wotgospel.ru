<?php
// app/Filament/Resources/BibleCourseResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\BibleCourseResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;

use App\Models\BibleCourse;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use BackedEnum;
use UnitEnum;

class BibleCourseResource extends Resource
{
    protected static ?string $model = BibleCourse::class;
    
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-book-open';
    
    protected static UnitEnum|string|null $navigationGroup = 'Библейская школа';
    
    protected static ?string $navigationLabel = 'Курсы';
    
    protected static ?string $pluralModelLabel = 'Курсы';
    
    protected static ?string $recordTitleAttribute = 'title';
    
    protected static ?int $navigationSort = 1;
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_published', false)->count() ?: null;
    }
    
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Основная информация')
                ->schema([
                    TextInput::make('title')
                        ->label('Название курса')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $operation, $state, callable $set) => 
                            $operation === 'create' ? $set('slug', Str::slug($state)) : null
                        ),
                    
                    TextInput::make('slug')
                        ->label('URL-адрес (slug)')
                        ->required()
                        ->maxLength(255)
                        ->unique(BibleCourse::class, 'slug', ignoreRecord: true)
                        ->helperText('Автоматически генерируется из названия. Можно изменить вручную.'),
                    
                    RichEditor::make('description')
                        ->label('Описание курса')
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike',
                            'h2', 'h3', 'bulletList', 'orderedList',
                            'blockquote', 'link', 'undo', 'redo'
                        ])
                        ->columnSpanFull(),
                    
                    FileUpload::make('image_url')
                        ->label('Обложка курса')
                        ->disk('s3')
                        ->directory('bible-courses')
                        ->visibility('public')
                        ->image()
                        ->maxSize(5120)
                        ->helperText('Рекомендуемый размер: 1200x675px. Максимум 5MB.'),
                    
                    TextInput::make('order')
                        ->label('Порядок сортировки')
                        ->numeric()
                        ->default(0)
                        ->helperText('Меньшее число = выше в списке'),
                        
                    RichEditor::make('what_you_will_learn')
    ->label('Что вы узнаете')
    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
    ->columnSpanFull(),

RichEditor::make('skills')
    ->label('Какие навыки приобретёте')
    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
    ->columnSpanFull(),

TextInput::make('price')
    ->label('Стоимость обучения')
    ->default('Бесплатно')
    ->maxLength(255),

Repeater::make('statuses')
    ->label('Статусы обучения')
    ->schema([
        TextInput::make('name')->label('Название статуса')->required(),
        TextInput::make('percentage')->label('Процент для получения')->numeric()->required(),
        TextInput::make('icon')->label('Иконка (emoji)')->default('📘'),
    ])
    ->default([
        ['name' => 'Ученик', 'percentage' => 0, 'icon' => '📘'],
        ['name' => 'Служитель', 'percentage' => 25, 'icon' => '🙏'],
        ['name' => 'Лидер', 'percentage' => 50, 'icon' => '👑'],
        ['name' => 'Наставник', 'percentage' => 75, 'icon' => '⭐'],
    ])
    ->columnSpanFull(),

Textarea::make('certificate_text')
    ->label('Текст на сертификате')
    ->rows(3)
    ->placeholder('Успешно завершил(а) полный курс обучения'),
                    
                    Toggle::make('is_published')
                        ->label('Опубликован')
                        ->default(false)
                        ->helperText('Неопубликованные курсы не видны ученикам'),
                ])->columns(2),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')
                    ->label('№')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                ImageColumn::make('image_url')
                    ->label('Обложка')
                    ->circular()
                    ->width(50)
                    ->height(50),
                
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('lessons_count')
                    ->label('Уроков')
                    ->counts('lessons')
                    ->sortable(),
                
                IconColumn::make('is_published')
                    ->label('Опубликован')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Статус публикации')
                    ->placeholder('Все курсы')
                    ->trueLabel('Опубликованные')
                    ->falseLabel('Черновики'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('lessons')
                    ->label('Уроки')
                    ->icon('heroicon-o-academic-cap')
                    ->url(fn (BibleCourse $record): string => BibleLessonResource::getUrl('index', ['course_id' => $record->id])),
                Action::make('preview_certificate')
                    ->label('Сертификат')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn ($record) => route('certificate.preview', ['course' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBibleCourses::route('/'),
            'create' => Pages\CreateBibleCourse::route('/create'),
            'edit' => Pages\EditBibleCourse::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withCount('lessons');
    }
}