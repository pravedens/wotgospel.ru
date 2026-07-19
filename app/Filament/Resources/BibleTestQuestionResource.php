<?php
// app/Filament/Resources/BibleTestQuestionResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\BibleTestQuestionResource\Pages;
use App\Models\BibleLesson;
use App\Models\BibleTestQuestion;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class BibleTestQuestionResource extends Resource
{
    protected static ?string $model = BibleTestQuestion::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static UnitEnum|string|null $navigationGroup = 'Библейская школа';

    protected static ?string $navigationLabel = 'Вопросы тестов';

    protected static ?string $pluralModelLabel = 'Вопросы тестов';

    protected static ?int $navigationSort = 3;

    /**
     * Безопасно превращает любое значение в текст.
     * Нужно, чтобы числа 1, 2, 3 не попадали в поля как int.
     */
    protected static function normalizeTextState(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value) || is_numeric($value) || is_bool($value)) {
            return trim((string) $value);
        }

        if (! is_array($value)) {
            return '';
        }

        foreach (['text', 'value', 'label', 'verse', 'answer'] as $key) {
            if (array_key_exists($key, $value)) {
                $normalized = self::normalizeTextState($value[$key]);

                if ($normalized !== '') {
                    return $normalized;
                }
            }
        }

        if (isset($value['content']) && is_array($value['content'])) {
            $parts = [];

            foreach ($value['content'] as $item) {
                $part = self::normalizeTextState($item);

                if ($part !== '') {
                    $parts[] = $part;
                }
            }

            return trim(implode("\n", $parts));
        }

        return '';
    }

    /**
     * Безопасно извлекает строковое значение для Select.
     */
    protected static function normalizeSelectValue(mixed $value, string $preferredField = 'text'): ?string
    {
        $normalized = self::normalizeTextState($value);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * Обычные options для Select.
     */
    protected static function repeaterOptionsForSelect(mixed $items, string $field = 'text'): array
    {
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (! is_array($items)) {
            return [];
        }

        $result = [];

        foreach ($items as $item) {
            if (is_array($item) && array_key_exists($field, $item)) {
                $value = self::normalizeSelectValue($item[$field], $field);
            } else {
                $value = self::normalizeSelectValue($item, $field);
            }

            if ($value !== null) {
                $result[$value] = $value;
            }
        }

        return $result;
    }

    /**
     * Безопасные options для Select.
     * Используется там, где значением может быть число.
     */
    protected static function encodedRepeaterOptionsForSelect(mixed $items, string $field = 'text'): array
    {
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (! is_array($items)) {
            return [];
        }

        $result = [];

        foreach ($items as $item) {
            if (is_array($item) && array_key_exists($field, $item)) {
                $value = self::normalizeSelectValue($item[$field], $field);
            } else {
                $value = self::normalizeSelectValue($item, $field);
            }

            if ($value !== null) {
                $result[self::encodeAnswerValue($value)] = $value;
            }
        }

        return $result;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('lesson_id')
                ->label('Урок')
                ->options(
                    BibleLesson::with('course')
                        ->get()
                        ->mapWithKeys(function ($lesson) {
                            $courseTitle = $lesson->course?->title ?? 'Без курса';

                            return [
                                $lesson->id => $courseTitle . ' - ' . $lesson->title,
                            ];
                        })
                )
                ->required()
                ->searchable()
                ->reactive(),

            Select::make('type')
    ->label('Тип вопроса')
    ->options([
        'single_choice' => 'Одиночный выбор',
        'multiple_choice' => 'Множественный выбор',
        'matching' => 'Соответствие',
        'ordering' => 'Порядок событий (Drag & Drop)',
        'odd_one_out' => 'Убрать лишнее',
        'verse_reference' => 'Ввод ссылки на стих',
        'select_verse' => 'Выбор стиха из предложенных',
        'true_false' => 'Правда/Ложь',
        'fill_blank' => 'Заполнить пропуски',
    ])
    ->required()
    ->reactive()
    ->afterStateUpdated(function (callable $set, $state, $get) {
        // Получаем текущий config
        $currentConfig = $get('config');
        
        // Если config уже существует и не null, не трогаем
        if ($currentConfig !== null && $currentConfig !== '') {
            return;
        }
        
        // Инициализация config для разных типов ТОЛЬКО если он пустой
        if ($state === 'verse_reference') {
            $set('config', [
                'expected_book' => '',
                'expected_chapter' => null,
                'expected_verse' => null,
                'accept_alternative_notations' => true,
            ]);
        }
        
        if ($state === 'true_false') {
            $set('config', [
                'statement' => '',
                'correct' => '1',
                'explanation' => '',
            ]);
        }
        
        if ($state === 'single_choice') {
            $set('config', [
                'options' => [],
                'correct' => null,
                'randomize' => true,
            ]);
        }
        
        if ($state === 'multiple_choice') {
            $set('config', [
                'options' => [],
                'randomize' => true,
            ]);
        }
        
        if ($state === 'fill_blank') {
            $set('config', [
                'text' => '',
                'answers' => [],
                'case_sensitive' => false,
            ]);
        }
        
        if ($state === 'odd_one_out') {
            $set('config', [
                'items' => [],
                'correct_odd' => null,
                'explanation' => '',
            ]);
        }
        
        if ($state === 'matching') {
            $set('config', [
                'left' => [],
                'right' => [],
                'matches' => [],
            ]);
        }
        
        if ($state === 'ordering') {
            $set('config', [
                'items' => [],
            ]);
        }
        
        if ($state === 'select_verse') {
            $set('config', [
                'options' => [],
                'correct' => null,
            ]);
        }
    }),
            Textarea::make('question')
                ->label('Текст вопроса')
                ->rows(5)
                ->required()
                ->columnSpanFull(),

            TextInput::make('points')
                ->label('Баллы за вопрос')
                ->numeric()
                ->default(1)
                ->minValue(1),

            TextInput::make('order')
                ->label('Порядок')
                ->numeric()
                ->default(0),

            // ========== 1. Одиночный выбор ==========
            Group::make()
                ->schema([
                    Repeater::make('options')
                        ->label('Варианты ответов')
                        ->schema([
                            TextInput::make('id')
                                ->default(fn () => Str::random(6))
                                ->hidden(),

                            TextInput::make('text')
                                ->label('Текст варианта')
                                ->required()
                                ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                                ->dehydrateStateUsing(fn ($state) => trim((string) $state))
                                ->live(onBlur: true),
                        ])
                        ->addActionLabel('Добавить вариант')
                        ->minItems(2)
                        ->defaultItems(2)
                        ->reorderable()
                        ->live()
                        ->columnSpanFull(),

                    Select::make('correct')
                        ->label('Правильный ответ')
                        ->options(fn ($get) => self::encodedRepeaterOptionsForSelect($get('options'), 'text'))
                        ->formatStateUsing(fn ($state) => self::encodeAnswerValue($state))
                        ->dehydrateStateUsing(fn ($state) => self::decodeAnswerValue($state))
                        ->required()
                        ->live(),

                    Toggle::make('randomize')
                        ->label('Перемешивать варианты')
                        ->default(true),
                ])
                ->statePath('config')
                ->visible(fn ($get) => $get('type') === 'single_choice'),

            // ========== 2. Множественный выбор ==========
Group::make()
    ->schema([
        Repeater::make('options')
            ->label('Варианты ответов')
            ->schema([
                TextInput::make('text')
                    ->label('Текст варианта')
                    ->required()
                    ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                    ->dehydrateStateUsing(fn ($state) => trim((string) $state))
                    ->live(onBlur: true),

                Toggle::make('is_correct')
                    ->label('Правильный ответ')
                    ->default(false),
            ])
            ->addActionLabel('Добавить вариант')
            ->minItems(2)
            ->defaultItems(2)
            ->reorderable()
            ->live()
            ->columnSpanFull(),

        Toggle::make('randomize')
            ->label('Перемешивать варианты')
            ->default(true),
            
        // Скрытое поле для хранения правильных ответов
        TextInput::make('correct')
            ->hidden()
            ->default('[]'),
    ])
    ->statePath('config')
    ->visible(fn ($get) => $get('type') === 'multiple_choice'),

            // ========== 3. Соответствие ==========
            Group::make()
                ->schema([
                    Repeater::make('left')
                        ->label('Левый столбец (что сопоставляем)')
                        ->schema([
                            TextInput::make('text')
                                ->label('Текст')
                                ->required()
                                ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                                ->dehydrateStateUsing(fn ($state) => trim((string) $state))
                                ->reactive(),
                        ])
                        ->addActionLabel('Добавить элемент')
                        ->minItems(1)
                        ->defaultItems(1)
                        ->reorderable()
                        ->columnSpanFull(),

                    Repeater::make('right')
                        ->label('Правый столбец (с чем сопоставляем)')
                        ->schema([
                            TextInput::make('text')
                                ->label('Текст')
                                ->required()
                                ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                                ->dehydrateStateUsing(fn ($state) => trim((string) $state))
                                ->reactive(),
                        ])
                        ->addActionLabel('Добавить элемент')
                        ->minItems(1)
                        ->defaultItems(1)
                        ->reorderable()
                        ->columnSpanFull(),

                    Repeater::make('matches')
                        ->label('Правильные соответствия')
                        ->schema([
                            Select::make('left')
                                ->label('Левый элемент')
                                ->options(fn ($get) => self::encodedRepeaterOptionsForSelect($get('../../left'), 'text'))
                                ->formatStateUsing(fn ($state) => self::encodeAnswerValue($state))
                                ->dehydrateStateUsing(fn ($state) => self::decodeAnswerValue($state))
                                ->required(),

                            Select::make('right')
                                ->label('Правый элемент')
                                ->options(fn ($get) => self::encodedRepeaterOptionsForSelect($get('../../right'), 'text'))
                                ->formatStateUsing(fn ($state) => self::encodeAnswerValue($state))
                                ->dehydrateStateUsing(fn ($state) => self::decodeAnswerValue($state))
                                ->required(),
                        ])
                        ->addActionLabel('Добавить соответствие')
                        ->minItems(1)
                        ->columnSpanFull(),
                ])
                ->statePath('config')
                ->visible(fn ($get) => $get('type') === 'matching'),

            // ========== 4. Порядок событий (Drag & Drop) ==========
            Group::make()
                ->schema([
                    Repeater::make('items')
                        ->label('Элементы для сортировки')
                        ->schema([
                            TextInput::make('text')
                                ->label('Текст')
                                ->required()
                                ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                                ->dehydrateStateUsing(fn ($state) => trim((string) $state)),

                            TextInput::make('correct_order')
                                ->label('Правильный порядок (число)')
                                ->numeric()
                                ->required(),
                        ])
                        ->addActionLabel('Добавить элемент')
                        ->minItems(2)
                        ->reorderable()
                        ->columnSpanFull(),
                ])
                ->statePath('config')
                ->visible(fn ($get) => $get('type') === 'ordering'),

            // ========== 5. Убрать лишнее ==========
            Group::make()
                ->schema([
                    Repeater::make('items')
                        ->label('Элементы списка')
                        ->schema([
                            TextInput::make('text')
                                ->label('Текст')
                                ->required()
                                ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                                ->dehydrateStateUsing(fn ($state) => trim((string) $state))
                                ->reactive(),
                        ])
                        ->addActionLabel('Добавить элемент')
                        ->minItems(3)
                        ->reorderable()
                        ->columnSpanFull(),

                    Select::make('correct_odd')
                        ->label('Лишний элемент')
                        ->options(fn ($get) => self::encodedRepeaterOptionsForSelect($get('items'), 'text'))
                        ->formatStateUsing(fn ($state) => self::encodeAnswerValue($state))
                        ->dehydrateStateUsing(fn ($state) => self::decodeAnswerValue($state))
                        ->required(),

                    Textarea::make('explanation')
                        ->label('Пояснение')
                        ->rows(4)
                        ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                        ->dehydrateStateUsing(fn ($state) => trim((string) $state))
                        ->columnSpanFull(),
                ])
                ->statePath('config')
                ->visible(fn ($get) => $get('type') === 'odd_one_out'),

            // ========== 6. Ввод ссылки на стих ==========
            Group::make()
                ->schema([
                    TextInput::make('expected_book')
                        ->label('Ожидаемая книга')
                        ->required()
                        ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                        ->dehydrateStateUsing(fn ($state) => trim((string) $state)),

                    TextInput::make('expected_chapter')
                        ->label('Ожидаемая глава')
                        ->numeric()
                        ->required(),

                    TextInput::make('expected_verse')
                        ->label('Ожидаемый стих')
                        ->numeric()
                        ->required(),

                    Toggle::make('accept_alternative_notations')
                        ->label('Принимать альтернативные обозначения (Ин., Иоанна)')
                        ->default(true),
                ])
                ->statePath('config')
                ->visible(fn ($get) => $get('type') === 'verse_reference'),

            // ========== 7. Выбор стиха из предложенных ==========
            Group::make()
                ->schema([
                    Repeater::make('options')
                        ->label('Предложенные стихи')
                        ->schema([
                            TextInput::make('verse')
                                ->label('Ссылка на стих (напр., Ин. 3:16)')
                                ->required()
                                ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                                ->dehydrateStateUsing(fn ($state) => trim((string) $state))
                                ->reactive(),

                            Textarea::make('text')
                                ->label('Текст стиха')
                                ->rows(4)
                                ->required()
                                ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                                ->dehydrateStateUsing(fn ($state) => trim((string) $state)),
                        ])
                        ->addActionLabel('Добавить стих')
                        ->minItems(2)
                        ->reorderable()
                        ->columnSpanFull(),

                    Select::make('correct')
                        ->label('Правильный стих')
                        ->options(fn ($get) => self::encodedRepeaterOptionsForSelect($get('options'), 'verse'))
                        ->formatStateUsing(fn ($state) => self::encodeAnswerValue($state))
                        ->dehydrateStateUsing(fn ($state) => self::decodeAnswerValue($state))
                        ->required(),
                ])
                ->statePath('config')
                ->visible(fn ($get) => $get('type') === 'select_verse'),

            // ========== 8. Правда/Ложь ==========
            Group::make()
                ->schema([
                    Textarea::make('statement')
                        ->label('Утверждение')
                        ->rows(4)
                        ->required()
                        ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                        ->dehydrateStateUsing(fn ($state) => trim((string) $state)),

                    Select::make('correct')
                        ->label('Правильный ответ')
                        ->options([
                            '1' => 'Правда',
                            '0' => 'Ложь',
                        ])
                        ->required()
                        ->default('1'),

                    Textarea::make('explanation')
                        ->label('Пояснение')
                        ->rows(4)
                        ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                        ->dehydrateStateUsing(fn ($state) => trim((string) $state))
                        ->columnSpanFull(),
                ])
                ->statePath('config')
                ->visible(fn ($get) => $get('type') === 'true_false'),

            // ========== 9. Заполнить пропуски ==========
            Group::make()
                ->schema([
                    Textarea::make('text')
                        ->label('Текст с пропусками (используйте ______ для пропуска)')
                        ->rows(5)
                        ->required()
                        ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                        ->dehydrateStateUsing(fn ($state) => trim((string) $state)),

                    Repeater::make('answers')
                        ->label('Правильные ответы')
                        ->schema([
                            TextInput::make('answer')
                                ->label('Вариант ответа')
                                ->required()
                                ->formatStateUsing(fn ($state) => self::normalizeTextState($state))
                                ->dehydrateStateUsing(fn ($state) => trim((string) $state)),
                        ])
                        ->addActionLabel('Добавить вариант ответа')
                        ->minItems(1)
                        ->columnSpanFull(),

                    Toggle::make('case_sensitive')
                        ->label('Учитывать регистр')
                        ->default(false),
                ])
                ->statePath('config')
                ->visible(fn ($get) => $get('type') === 'fill_blank'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')
                    ->label('№')
                    ->sortable(),

                TextColumn::make('lesson.title')
                    ->label('Урок')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'single_choice' => 'Одиночный выбор',
                        'multiple_choice' => 'Множественный выбор',
                        'matching' => 'Соответствие',
                        'ordering' => 'Порядок',
                        'odd_one_out' => 'Лишнее',
                        'verse_reference' => 'Ссылка на стих',
                        'select_verse' => 'Выбор стиха',
                        'true_false' => 'Правда/Ложь',
                        'fill_blank' => 'Пропуски',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'single_choice', 'true_false' => 'success',
                        'multiple_choice' => 'primary',
                        'matching', 'ordering' => 'warning',
                        default => 'info',
                    }),

                TextColumn::make('question')
                    ->label('Вопрос')
                    ->html()
                    ->limit(100)
                    ->searchable(),

                TextColumn::make('points')
                    ->label('Баллы')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип вопроса')
                    ->options([
                        'single_choice' => 'Одиночный выбор',
                        'multiple_choice' => 'Множественный выбор',
                        'matching' => 'Соответствие',
                        'ordering' => 'Порядок',
                        'odd_one_out' => 'Убрать лишнее',
                        'verse_reference' => 'Ссылка на стих',
                        'select_verse' => 'Выбор стиха',
                        'true_false' => 'Правда/Ложь',
                        'fill_blank' => 'Пропуски',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Нормализация данных перед сохранением.
     */
    public static function normalizeFormDataBeforeSave(array $data): array
    {
        if (isset($data['config']) && is_string($data['config'])) {
            $decoded = json_decode($data['config'], true);
            $data['config'] = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (! isset($data['config']) || ! is_array($data['config'])) {
            $data['config'] = [];
        }

        if (($data['type'] ?? null) === 'single_choice') {
            $options = $data['config']['options'] ?? [];

            if (! is_array($options)) {
                $options = [];
            }

            $normalizedOptions = [];

            foreach ($options as $option) {
                $text = is_array($option)
                    ? self::normalizeTextState($option['text'] ?? $option)
                    : self::normalizeTextState($option);

                if ($text === '') {
                    continue;
                }

                $normalizedOptions[] = [
                    'id' => is_array($option) && ! empty($option['id'])
                        ? (string) $option['id']
                        : Str::random(6),
                    'text' => $text,
                ];
            }

            $correct = $data['config']['correct'] ?? null;

            if (is_array($correct)) {
                $correct = collect($correct)
                    ->filter(fn ($value) => is_scalar($value))
                    ->first();
            }

            $correct = self::decodeAnswerValue($correct);

            $data['config']['options'] = $normalizedOptions;
            $data['config']['correct'] = $correct !== null ? trim((string) $correct) : null;
            $data['config']['randomize'] = (bool) ($data['config']['randomize'] ?? true);
        }

        if (($data['type'] ?? null) === 'multiple_choice') {
    $options = $data['config']['options'] ?? [];

    if (! is_array($options)) {
        $options = [];
    }

    $correct = [];
    $normalizedOptions = [];

    foreach ($options as $option) {
        $text = is_array($option)
            ? self::normalizeTextState($option['text'] ?? $option)
            : self::normalizeTextState($option);

        if ($text === '') {
            continue;
        }

        $isCorrect = is_array($option) && (bool) ($option['is_correct'] ?? false);

        if ($isCorrect) {
            $correct[] = $text;
        }

        $normalizedOptions[] = [
            'text' => $text,
            'is_correct' => $isCorrect,
        ];
    }

    $data['config']['options'] = $normalizedOptions;
    $data['config']['correct'] = array_values(array_unique($correct));
    $data['config']['randomize'] = (bool) ($data['config']['randomize'] ?? true);
    
    // Удаляем временное поле, если оно есть
    unset($data['config']['_correct']);
}

        if (($data['type'] ?? null) === 'matching') {
            foreach (['left', 'right'] as $side) {
                $items = $data['config'][$side] ?? [];

                if (! is_array($items)) {
                    $items = [];
                }

                $data['config'][$side] = collect($items)
                    ->map(function ($item) {
                        $text = is_array($item)
                            ? self::normalizeTextState($item['text'] ?? $item)
                            : self::normalizeTextState($item);

                        return $text !== '' ? ['text' => $text] : null;
                    })
                    ->filter()
                    ->values()
                    ->all();
            }

            $matches = $data['config']['matches'] ?? [];

            if (! is_array($matches)) {
                $matches = [];
            }

            $data['config']['matches'] = collect($matches)
                ->map(function ($match) {
                    if (! is_array($match)) {
                        return null;
                    }

                    $left = self::decodeAnswerValue($match['left'] ?? null);
                    $right = self::decodeAnswerValue($match['right'] ?? null);

                    if ($left === null || $right === null) {
                        return null;
                    }

                    return [
                        'left' => trim((string) $left),
                        'right' => trim((string) $right),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        if (($data['type'] ?? null) === 'ordering') {
            $items = $data['config']['items'] ?? [];

            if (! is_array($items)) {
                $items = [];
            }

            $data['config']['items'] = collect($items)
                ->map(function ($item) {
                    if (! is_array($item)) {
                        return null;
                    }

                    $text = self::normalizeTextState($item['text'] ?? '');

                    if ($text === '') {
                        return null;
                    }

                    return [
                        'text' => $text,
                        'correct_order' => (int) ($item['correct_order'] ?? 0),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        if (($data['type'] ?? null) === 'odd_one_out') {
            $items = $data['config']['items'] ?? [];

            if (! is_array($items)) {
                $items = [];
            }

            $data['config']['items'] = collect($items)
                ->map(function ($item) {
                    $text = is_array($item)
                        ? self::normalizeTextState($item['text'] ?? $item)
                        : self::normalizeTextState($item);

                    return $text !== '' ? ['text' => $text] : null;
                })
                ->filter()
                ->values()
                ->all();

            $correctOdd = self::decodeAnswerValue($data['config']['correct_odd'] ?? null);

            $data['config']['correct_odd'] = $correctOdd !== null ? trim((string) $correctOdd) : null;
            $data['config']['explanation'] = self::normalizeTextState($data['config']['explanation'] ?? '');
        }

        if (($data['type'] ?? null) === 'verse_reference') {
            $data['config']['expected_book'] = self::normalizeTextState($data['config']['expected_book'] ?? '');
            $data['config']['expected_chapter'] = (int) ($data['config']['expected_chapter'] ?? 0);
            $data['config']['expected_verse'] = (int) ($data['config']['expected_verse'] ?? 0);
            $data['config']['accept_alternative_notations'] = (bool) ($data['config']['accept_alternative_notations'] ?? true);
        }

        if (($data['type'] ?? null) === 'select_verse') {
            $options = $data['config']['options'] ?? [];

            if (! is_array($options)) {
                $options = [];
            }

            $data['config']['options'] = collect($options)
                ->map(function ($option) {
                    if (! is_array($option)) {
                        return null;
                    }

                    $verse = self::normalizeTextState($option['verse'] ?? '');
                    $text = self::normalizeTextState($option['text'] ?? '');

                    if ($verse === '') {
                        return null;
                    }

                    return [
                        'verse' => $verse,
                        'text' => $text,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            $correct = self::decodeAnswerValue($data['config']['correct'] ?? null);

            $data['config']['correct'] = $correct !== null ? trim((string) $correct) : null;
        }

        if (($data['type'] ?? null) === 'true_false') {
            $data['config']['statement'] = self::normalizeTextState($data['config']['statement'] ?? '');
            $data['config']['correct'] = (string) ($data['config']['correct'] ?? '1') === '1' ? '1' : '0';
            $data['config']['explanation'] = self::normalizeTextState($data['config']['explanation'] ?? '');
        }

        if (($data['type'] ?? null) === 'fill_blank') {
            $answers = $data['config']['answers'] ?? [];

            if (! is_array($answers)) {
                $answers = [];
            }

            $data['config']['text'] = self::normalizeTextState($data['config']['text'] ?? '');

            $data['config']['answers'] = collect($answers)
                ->map(function ($answer) {
                    $text = is_array($answer)
                        ? self::normalizeTextState($answer['answer'] ?? $answer)
                        : self::normalizeTextState($answer);

                    return $text !== '' ? ['answer' => $text] : null;
                })
                ->filter()
                ->values()
                ->all();

            $data['config']['case_sensitive'] = (bool) ($data['config']['case_sensitive'] ?? false);
        }

        return $data;
    }

    public static function encodeAnswerValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'answer_')) {
            return $value;
        }

        return 'answer_' . rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    public static function decodeAnswerValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (! str_starts_with($value, 'answer_')) {
            return $value;
        }

        $encoded = substr($value, strlen('answer_'));

        $encoded = strtr($encoded, '-_', '+/');

        $padding = strlen($encoded) % 4;

        if ($padding > 0) {
            $encoded .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($encoded, true);

        return $decoded === false ? null : $decoded;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBibleTestQuestions::route('/'),
            'create' => Pages\CreateBibleTestQuestion::route('/create'),
            'edit' => Pages\EditBibleTestQuestion::route('/{record}/edit'),
        ];
    }
}