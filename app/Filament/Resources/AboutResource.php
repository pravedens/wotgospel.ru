<?php

namespace App\Filament\Resources;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use App\Filament\Resources\AboutResource\Pages;
use App\Models\About;
use App\Services\ImageOptimizer;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use BackedEnum;
use UnitEnum;

class AboutResource extends Resource
{
    protected static ?string $model = About::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static UnitEnum|string|null $navigationGroup = 'О нас';

    protected static ?string $navigationLabel = 'Статьи';

    protected static ?string $breadcrumb = 'Статьи';

    protected static ?string $pluralModelLabel = 'Статьи';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, $state) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rule('alpha_dash')
                    ->helperText('Автоматически генерируется из названия, но можно изменить'),

                Forms\Components\Textarea::make('description')
                    ->label('Коротко')
                    ->required()
                    ->rows(3)
                    ->autosize()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('content')
                    ->label('Основная статья')
                    ->required()
                    ->rows(3)
                    ->autosize()
                    ->columnSpanFull(),

                Forms\Components\Select::make('denomination_id')
                    ->label('Категория')
                    ->relationship('denomination', 'title')
                    ->required(),

                // ✅ Загрузка на S3 (Яндекс Облако) с оптимизацией
                FileUpload::make('thumbnail')
                    ->label('Картинка')
                    ->image()
                    ->directory('abouts/thumbnails')
                    ->disk('s3')
                    ->visibility('public')
                    ->imageEditor()
                    ->maxSize(5120)
                    ->saveUploadedFileUsing(function (UploadedFile $file, ?Model $record): string {
                        // Оптимизируем и сохраняем изображение в S3
                        $optimizedPath = ImageOptimizer::optimizeAndStore(
                            file: $file,
                            directory: 'abouts/thumbnails',
                            width: 1200,
                            height: 800,
                            quality: 85
                        );
                        
                        if ($optimizedPath) {
                            // Если есть старый файл — удаляем его
                            if ($record && $record->thumbnail) {
                                Storage::disk('s3')->delete($record->thumbnail);
                                \Log::info('Old thumbnail deleted on update', ['path' => $record->thumbnail]);
                            }
                            return $optimizedPath;
                        }
                        
                        // Fallback: сохраняем оригинал
                        \Log::warning('Image optimization failed in Filament, using original', [
                            'original_name' => $file->getClientOriginalName(),
                            'original_size' => $file->getSize()
                        ]);
                        
                        $filename = Str::slug($record?->title ?? 'about') . '-' . uniqid() . '.webp';
                        return $file->storeAs('abouts/thumbnails', $filename, 's3');
                    })
                    ->deleteUploadedFileUsing(function ($file, $record) {
                        if ($record && $record->thumbnail) {
                            Storage::disk('s3')->delete($record->thumbnail);
                            \Log::info('Thumbnail deleted via deleteUploadedFileUsing', ['path' => $record->thumbnail]);
                        }
                    })
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->disk('s3')  // ✅ Используем S3
                    ->size(80)
                    ->circular(),

                TextColumn::make('title')
                    ->label('Название')
                    ->color('success'),

                TextColumn::make('denomination.title')
                    ->label('Категория')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Дата')
                    ->date('d.m.Y'),
            ])
            ->defaultSort('title', 'asc')
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (About $record) {
                        // ✅ Удаляем thumbnail из S3
                        if ($record->thumbnail) {
                            Storage::disk('s3')->delete($record->thumbnail);
                            \Log::info('Thumbnail deleted on record delete', ['path' => $record->thumbnail]);
                        }
                        $record->delete();
                        
                        Notification::make()
                            ->title('Статья удалена')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                if ($record->thumbnail) {
                                    Storage::disk('s3')->delete($record->thumbnail);
                                    \Log::info('Thumbnail deleted on bulk delete', ['path' => $record->thumbnail]);
                                }
                                $record->delete();
                            }
                            
                            Notification::make()
                                ->title('Записи удалены')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        return $data;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAbouts::route('/'),
            'create' => Pages\CreateAbout::route('/create'),
            'edit'   => Pages\EditAbout::route('/{record}/edit'),
        ];
    }
}