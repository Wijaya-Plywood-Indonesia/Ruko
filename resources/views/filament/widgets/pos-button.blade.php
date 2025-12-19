<x-filament-widgets::widget>
    <x-filament::section>
        <x-filament::button
            icon="heroicon-o-calculator"
            color="primary"
            size="xl"
            x-on:click="window.location.href='{{
                route('filament.admin.resources.penjualans.pos')
            }}'"
        >
            Buka POS
        </x-filament::button>
    </x-filament::section>
</x-filament-widgets::widget>
