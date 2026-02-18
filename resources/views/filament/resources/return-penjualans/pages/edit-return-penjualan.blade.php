<x-filament-panels::page>
    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

    </form>

            {{ $this->table }}
</x-filament-panels::page>