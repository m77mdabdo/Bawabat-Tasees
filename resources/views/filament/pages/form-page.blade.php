<x-filament-panels::page>
    <form wire:submit="save" style="display:grid;gap:1.5rem">
        {{ $this->form }}
        <div>
            @foreach ($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>
</x-filament-panels::page>
