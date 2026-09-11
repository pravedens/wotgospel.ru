<x-filament::page>
    <x-filament::form wire:submit="save">
        {{ $this->form }}
        
        <div class="flex justify-end mt-6">
            <x-filament::button type="submit">
                Сохранить изменения
            </x-filament::button>
        </div>
    </x-filament::form>
</x-filament::page>
