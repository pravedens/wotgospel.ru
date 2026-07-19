<?php

// app/Filament/Resources/BibleLessonResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\BibleLessonResource\Pages;
use App\Models\BibleCourse;
use App\Models\BibleLesson;
use App\Models\BibleTheme;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class BibleLessonResource extends Resource
{
    protected static ?string $model = BibleLesson::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static UnitEnum|string|null $navigationGroup = 'Библейская школа';

    protected static ?string $navigationLabel = 'Уроки';

    protected static ?string $pluralModelLabel = 'Уроки';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_published', false)->count();

        return $count ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Урок')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Основное')
                        ->schema([
                            Section::make('Основная информация')
                                ->columnSpanFull()
                                ->schema([
                                    Grid::make([
                                        'default' => 1,
                                        'md' => 3,
                                    ])
                                        ->schema([
                                            Select::make('course_id')
                                                ->label('Курс')
                                                ->options(fn (): array => BibleCourse::query()
                                                    ->where('is_published', true)
                                                    ->orderBy('title')
                                                    ->pluck('title', 'id')
                                                    ->all())
                                                ->required()
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->afterStateUpdated(fn (callable $set) => $set('theme_id', null)),

                                            Select::make('theme_id')
                                                ->label('Тема')
                                                ->options(function (callable $get): array {
                                                    $courseId = $get('course_id');

                                                    if (! $courseId) {
                                                        return [];
                                                    }

                                                    return BibleTheme::query()
                                                        ->where('course_id', $courseId)
                                                        ->where('is_published', true)
                                                        ->orderBy('title')
                                                        ->pluck('title', 'id')
                                                        ->all();
                                                })
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->helperText('Сначала выберите курс'),

                                            TextInput::make('order')
                                                ->label('Порядок в курсе')
                                                ->numeric()
                                                ->default(0)
                                                ->helperText('Меньшее число = выше'),
                                        ]),

                                    Grid::make([
                                        'default' => 1,
                                        'md' => 3,
                                    ])
                                        ->schema([
                                            TextInput::make('title')
                                                ->label('Название урока')
                                                ->required()
                                                ->maxLength(255)
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(function (
                                                    callable $set,
                                                    ?string $state,
                                                    string $operation,
                                                ): void {
                                                    if ($operation === 'create' && filled($state)) {
                                                        $set('slug', Str::slug($state));
                                                    }
                                                }),

                                            TextInput::make('slug')
                                                ->label('URL-адрес (slug)')
                                                ->required()
                                                ->maxLength(255)
                                                ->unique(BibleLesson::class, 'slug', ignoreRecord: true),

                                            Toggle::make('is_published')
                                                ->label('Опубликован')
                                                ->default(false),
                                        ]),
                                ]),
                        ]),

                    Tab::make('Дугообразная модель')
                        ->schema([
                            Section::make('Модель обучения')
                                ->columnSpanFull()
                                ->schema([
                                    Hidden::make('scripture_verses'),

                                    Textarea::make('call_question')
                                        ->label('1. Призыв (жизненный вопрос)')
                                        ->rows(3)
                                        ->placeholder('Например: "Почему Бог не отвечает на молитву?"')
                                        ->helperText('Вопрос или ситуация из жизни, которая вовлекает ученика')
                                        ->columnSpanFull(),

                                    Textarea::make('call_answer')
                                        ->label('1.1. Ответ на призыв')
                                        ->rows(4)
                                        ->placeholder('Например: "Бог всегда слышит нас, но отвечает по Своей воле и времени..."')
                                        ->helperText('Библейский ответ на жизненный вопрос')
                                        ->columnSpanFull(),

                                    Repeater::make('scripture_verses_manual')
                                        ->label('2. Писание (стихи из Библии)')
                                        ->schema([
                                            Grid::make([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                                ->schema([
                                                    TextInput::make('reference')
                                                        ->label('Ссылка на стих')
                                                        ->required()
                                                        ->placeholder('Например: Иоанна 3:16'),
                                                        //->debounce(500),

                                                    Textarea::make('text')
                                                        ->label('Текст стиха')
                                                        ->required()
                                                        ->rows(3)
                                                        ->placeholder('Введите текст стиха...'),
                                                        //->debounce(500),
                                                ]),
                                        ])
                                        ->defaultItems(1)
                                        ->addActionLabel('Добавить стих')
                                        ->reorderable()
                                        //->live()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(function (Repeater $component, ?array $state, ?BibleLesson $record): void {
                                            if (! $record || blank($record->scripture_verses)) {
                                                return;
                                            }

                                            $component->state(static::scriptureVersesToRepeaterState($record->scripture_verses));
                                        })
                                        ->afterStateUpdated(function (?array $state, callable $set): void {
                                            $set('scripture_verses', static::repeaterStateToScriptureVerses($state));
                                        })
                                        ->columnSpanFull(),

                                    RichEditor::make('content')
                                        ->label('3. Основной контент урока')
                                        ->toolbarButtons([
                                            'bold',
                                            'italic',
                                            'link',
                                            'attachFiles',
                                        ])
                                        ->fileAttachmentsDisk('s3')
                                        ->fileAttachmentsDirectory('bible-lessons')
                                        ->fileAttachmentsVisibility('public')
                                        ->extraAttributes([
                                            'style' => 'min-height: 400px;',
                                        ])
                                        ->debounce(1000)
                                        ->columnSpanFull(),

                                    Textarea::make('practice_task')
                                        ->label('4. Практическое задание')
                                        ->rows(5)
                                        ->placeholder('Например: "Три дня молитесь за обидчика и запишите ощущения"')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tab::make('Видео')
                        ->schema([
                            Section::make('Видео и файлы')
                                ->columnSpanFull()
                                ->schema([
                                    Repeater::make('videos')
                                        ->label('Видео-лекции')
                                        ->relationship('videos')
                                        ->schema([
                                            Grid::make([
                                                'default' => 1,
                                                'md' => 2,
                                            ])
                                                ->schema([
                                                    TextInput::make('title')
                                                        ->label('Название видео')
                                                        ->maxLength(255)
                                                        ->placeholder('Часть 1: Введение'),

                                                    TextInput::make('order')
                                                        ->label('Порядок')
                                                        ->numeric()
                                                        ->default(0),

                                                    TextInput::make('url')
                                                        ->label('Ссылка на видео')
                                                        ->required()
                                                        ->url()
                                                        ->maxLength(500)
                                                        ->placeholder('https://rutube.ru/...')
                                                        ->helperText('Поддерживаются Rutube, YouTube, VK, Vimeo')
                                                        ->columnSpanFull(),
                                                ]),
                                        ])
                                        ->defaultItems(0)
                                        ->addActionLabel('➕ Добавить видео')
                                        ->reorderable()
                                        ->columnSpanFull(),

                                    FileUpload::make('pdf_conspect_url')
                                        ->label('PDF-конспект')
                                        ->disk('s3')
                                        ->directory('bible-lessons/pdfs')
                                        ->visibility('public')
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->maxSize(10240)
                                        ->helperText('Дополнительный конспект урока в формате PDF, максимум 10MB')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),
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

                TextColumn::make('course.title')
                    ->label('Курс')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('theme.title')
                    ->label('Тема')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('title')
                    ->label('Название урока')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_published')
                    ->label('Опубликован')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('questions_count')
                    ->label('Вопросов')
                    ->counts('questions')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('course_id')
                    ->orderBy('order');
            })
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Курс')
                    ->options(fn (): array => BibleCourse::query()
                        ->orderBy('title')
                        ->pluck('title', 'id')
                        ->all()),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Статус публикации'),
            ])
            ->actions([
                EditAction::make(),

                DeleteAction::make(),

                Action::make('questions')
                    ->label('Вопросы')
                    ->icon('heroicon-o-question-mark-circle')
                    ->url(fn (BibleLesson $record): string => BibleTestQuestionResource::getUrl('index', [
                        'lesson_id' => $record->id,
                    ])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function scriptureVersesToRepeaterState(?string $scriptureVerses): array
    {
        if (blank($scriptureVerses)) {
            return [];
        }

        $blocks = preg_split("/\n\s*\n/", trim($scriptureVerses)) ?: [];

        $manualVerses = [];

        foreach ($blocks as $block) {
            $lines = preg_split("/\r\n|\n|\r/", trim($block)) ?: [];

            if (count($lines) < 2) {
                continue;
            }

            $manualVerses[] = [
                'reference' => trim($lines[0]),
                'text' => trim(implode("\n", array_slice($lines, 1))),
            ];
        }

        return $manualVerses;
    }

    protected static function repeaterStateToScriptureVerses(?array $state): string
    {
        if (blank($state)) {
            return '';
        }

        $textsArray = [];

        foreach ($state as $item) {
            $reference = trim($item['reference'] ?? '');
            $text = trim($item['text'] ?? '');

            if ($reference && $text) {
                $textsArray[] = $reference . "\n" . $text;
            }
        }

        return implode("\n\n", $textsArray);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBibleLessons::route('/'),
            'create' => Pages\CreateBibleLesson::route('/create'),
            'edit' => Pages\EditBibleLesson::route('/{record}/edit'),
        ];
    }
}
