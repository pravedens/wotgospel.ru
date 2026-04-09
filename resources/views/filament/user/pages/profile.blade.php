<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
        
        <div class="flex justify-end gap-4 mt-6">
            <x-filament::button
                tag="a"
                href="{{ route('filament.user.pages.dashboard') }}"
                color="gray"
            >
                Вернуться на главную
            </x-filament::button>
            
            <x-filament::button type="submit">
                Сохранить изменения
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>