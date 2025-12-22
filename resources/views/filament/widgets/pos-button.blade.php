<x-filament-widgets::widget>
    <x-filament::section class="flex items-center justify-center">
        <x-filament::button
            icon="heroicon-o-calculator"
            color="primary"
            size="xl"
            class="w-full h-32 text-3xl font-bold gap-4"
            x-on:click="window.location.href='{{
                route('filament.admin.resources.penjualans.pos')
            }}'"
        >
            BUKA POINT OF SALES (KASIR)
        </x-filament::button>
    </x-filament::section>
</x-filament-widgets::widget>
