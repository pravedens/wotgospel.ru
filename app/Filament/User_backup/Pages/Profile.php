<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Profile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    
    protected static string $view = 'filament.user.pages.profile';
    
    protected static ?string $navigationLabel = 'Мой профиль';
    
    protected static ?string $title = 'Личный кабинет';
    
    protected static ?string $slug = 'profile';
    
    protected static bool $shouldRegisterNavigation = true; // Показываем в навигации
    
    protected static ?int $navigationSort = 2; // Больше число = ниже в меню
    
    /**
     * Обязательный метод для Filament
     * Используется в пользовательском меню и других местах
     */
    public static function getLabel(): string
    {
        return 'Мой профиль';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Auth::user()->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Имя')
                            ->required(),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Фамилия'),
                        Forms\Components\TextInput::make('middle_name')
                            ->label('Отчество'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                    ])->columns(2),
                
                Forms\Components\Section::make('Личные данные')
                    ->schema([
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Дата рождения')
                            ->displayFormat('d.m.Y'),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->mask('+7 (999) 999-99-99')
                            ->placeholder('+7 (999) 999-99-99'),
                        Forms\Components\TextInput::make('city')
                            ->label('Город'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Информация о церкви')
                    ->schema([
                        Forms\Components\TextInput::make('church_name')
                            ->label('Название церкви'),
                        Forms\Components\Textarea::make('about')
                            ->label('О себе')
                            ->rows(4)
                            ->autosize(),
                    ]),
                
                Forms\Components\Section::make('Аватар')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Фото профиля')
                            ->image()
                            ->disk('public')
                            ->directory('avatars')
                            ->imageEditor()
                            ->circleCropper()
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Смена пароля')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Текущий пароль')
                            ->password()
                            ->requiredWith('new_password')
                            ->currentPassword(),
                        
                        Forms\Components\TextInput::make('new_password')
                            ->label('Новый пароль')
                            ->password()
                            ->confirmed(),
                        
                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Подтверждение нового пароля')
                            ->password(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        $user = Auth::user();
        
        $user->fill([
            'name' => $data['name'],
            'last_name' => $data['last_name'] ?? null,
            'middle_name' => $data['middle_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'church_name' => $data['church_name'] ?? null,
            'about' => $data['about'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'avatar' => $data['avatar'] ?? $user->avatar,
        ]);
        
        if (!empty($data['new_password'])) {
            $user->password = Hash::make($data['new_password']);
        }
        
        $user->save();
        
        Notification::make()
            ->title('Профиль обновлён')
            ->success()
            ->send();
        
        $this->form->fill($user->toArray());
        
        // Редирект на дашборд
        $this->redirect(Filament::getUrl());
    }

}