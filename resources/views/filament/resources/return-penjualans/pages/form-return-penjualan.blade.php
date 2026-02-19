
@php
    // if($penjualanTerpilih) dd($this->getTable())

    
@endphp

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
    @if ($this->penjualanTerpilih && isset($this->penjualanTerpilih->id))        
        {{-- Di form-return-penjualan.blade.php --}}
            <div class="mt-6" wire:key="info-nota-{{ $penjualanTerpilih->id }}">
                {{ $this->infoNota }}
            </div>
            <div class="mb-6">
            <x-filament::section
            icon="heroicon-m-information-circle"
            icon-color="info"
            >
                <x-slot name="heading">
                    Daftar Barang Penjualan
                </x-slot>
                
                <x-slot name="description">
                    Berikut adalah detail item yang ada pada nota ini.
                </x-slot>

            {{-- Render Tabel Utama --}}
                <div wire:key="wrapper-nota">
                    {{ $this->table }}
                </div>

                {{-- Render Tabel Retur --}}
            </x-filament::section>        
        </div>
        <x-filament::section
        icon="heroicon-m-information-circle"
        icon-color="info"
        >
            <x-slot name="heading">
                List Barang Retur
            </x-slot>
            
            <x-slot name="description">
                Berikut adalah detail item retur untuk nota ini.
            </x-slot>
            @livewire('past-return-penjualan', ['no_nota' => $penjualanTerpilih->no_nota])
            @livewire('temporary_return_cart')
        </x-filament::section>        

        <div class="mt-6">
            {{ $this->footerActions }}
        </div>
@endif

</x-filament-panels::page>


