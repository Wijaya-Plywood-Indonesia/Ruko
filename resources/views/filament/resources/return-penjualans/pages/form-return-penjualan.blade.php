<x-filament-panels::page>
    <style>
        .hide-datalist-arrow::-webkit-calendar-picker-indicator {
            display: none !important;
        }
    </style>

    <form wire:submit.prevent="submit">
        {{ $this->form }}
    </form>

    {{-- 🔥 Tampilkan Infolist jika data ditemukan --}}
    @if ($penjualanTerpilih)
        <div class="mt-6">
            {{ $this->infoNota }}
        </div>
    @endif
</x-filament-panels::page>