<x-filament-panels::page>
    {{-- CSS Khusus Sharp UI & Excel Feel --}}
    <style>
        * {
            border-radius: 0 !important;
        }

        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }

        .excel-input:focus {
            border: 1px solid #2563eb !important;
            outline: none;
            box-shadow: inset 0 0 0 1px #2563eb;
        }

        .dark .excel-input:focus {
            border-color: #3b82f6 !important;
        }
    </style>

    <div class="flex flex-col gap-5">

        {{-- HEADER: DATE PICKER & ACTIONS --}}
        <div class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-800 p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-5">
                <div class="flex flex-col gap-1">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pilih Tanggal Laporan:</span>
                    <div class="relative max-w-[200px]" x-data="{
                        init() {
                            flatpickr($refs.input, {
                                dateFormat: 'Y-m-d',
                                altInput: true,
                                altFormat: 'd/m/Y',
                                locale: 'id'
                            })
                        }
                    }">
                        <input x-ref="input" wire:model.live="selectedDate"
                            class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs font-bold outline-none focus:border-blue-500 shadow-sm" />
                    </div>
                </div>

                @if($currentRecord)
                <div class="flex items-center gap-4 border-l border-gray-300 dark:border-gray-700 pl-5 h-10">
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-gray-400 uppercase">Input Oleh:</span>
                        <span class="text-xs font-bold uppercase">{{ $currentRecord->created_by ?? '-' }}</span>
                    </div>
                    @if($isLocked)
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-green-500 uppercase">Divalidasi:</span>
                        <span class="text-xs font-black text-green-600 uppercase italic">{{ $currentRecord->validated_by }}</span>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            <div class="flex gap-2">
                @if($currentRecord && !$isLocked)
                <x-filament::button wire:click="save" icon="heroicon-m-arrow-down-on-square-stack" color="gray" size="sm">
                    Simpan Draft
                </x-filament::button>
                <x-filament::button wire:click="validateData" color="success" icon="heroicon-m-check-badge" size="sm"
                    wire:confirm="Validasi akan mengunci data secara permanen. Lanjutkan?">
                    Validasi Laporan
                </x-filament::button>
                @elseif($isLocked)
                <div class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-400 text-[10px] font-black uppercase">
                    <x-heroicon-s-lock-closed class="w-3 h-3" /> Terkunci (Final)
                </div>
                @endif
            </div>
        </div>

        @if(!$currentRecord)
        {{-- EMPTY STATE --}}
        <div class="py-32 border border-dashed border-gray-300 dark:border-gray-800 flex flex-col items-center justify-center bg-gray-50/30 dark:bg-gray-900/30">
            <x-heroicon-o-calendar class="w-12 h-12 text-gray-200 mb-3" />
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-400">Tidak ada data produksi pada tanggal ini</p>
        </div>
        @else
        {{-- CONTENT TABLES --}}
        <div class="space-y-6 animate-in fade-in duration-500">

            {{-- SECTION I: BAHAN MENTAH --}}
            <div class="bg-white dark:bg-gray-950 border border-gray-300 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-900 px-4 py-2 border-b border-gray-300 dark:border-gray-800 flex justify-between items-center">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 italic">I. Penggunaan Bahan Baku (Mentah)</h3>
                    @if($isLocked) <span class="text-[9px] font-bold text-red-500 uppercase flex items-center gap-1"><x-heroicon-s-lock-closed class="w-2.5 h-2.5" /> Read Only</span> @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-black font-black uppercase text-gray-600 dark:text-gray-400 text-[9px]">
                                <th class="p-4 border-b border-gray-300 dark:border-gray-800">Bahan Baku</th>
                                <th class="p-4 border-b border-gray-300 dark:border-gray-800 text-center w-32 bg-gray-50/50 dark:bg-gray-900/50">Stok Awal</th>
                                <th class="p-4 border-b border-gray-300 dark:border-gray-800 text-center w-32 bg-blue-50 dark:bg-blue-900/10 text-blue-600">Pullet</th>
                                <th class="p-4 border-b border-gray-300 dark:border-gray-800 text-center w-32 bg-blue-50 dark:bg-blue-900/10 text-blue-600">Layer 1</th>
                                <th class="p-4 border-b border-gray-300 dark:border-gray-800 text-center w-32 bg-blue-50 dark:bg-blue-900/10 text-blue-600">Layer 2</th>
                                <th class="p-4 border-b border-gray-300 dark:border-gray-800 text-center w-32 bg-amber-50 dark:bg-amber-900/10 text-amber-600 font-black">Sisa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($mentahState as $idx => $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                <td class="p-4 font-black uppercase border-r border-gray-200 dark:border-gray-800">{{ $item['nama'] }}</td>
                                <td class="p-4 text-center border-r border-gray-200 dark:border-gray-800 font-mono text-gray-400 italic bg-gray-50/30 dark:bg-gray-900/30">{{ number_format($item['awal']) }}</td>
                                <td class="p-0 border-r border-gray-200 dark:border-gray-800">
                                    <input type="number" wire:model.live="mentahState.{{ $idx }}.p" @disabled($isLocked)
                                        class="w-full h-12 border-0 bg-transparent text-center font-bold text-sm excel-input disabled:opacity-50">
                                </td>
                                <td class="p-0 border-r border-gray-200 dark:border-gray-800">
                                    <input type="number" wire:model.live="mentahState.{{ $idx }}.l1" @disabled($isLocked)
                                        class="w-full h-12 border-0 bg-transparent text-center font-bold text-sm excel-input disabled:opacity-50">
                                </td>
                                <td class="p-0 border-r border-gray-200 dark:border-gray-800">
                                    <input type="number" wire:model.live="mentahState.{{ $idx }}.l2" @disabled($isLocked)
                                        class="w-full h-12 border-0 bg-transparent text-center font-bold text-sm excel-input disabled:opacity-50">
                                </td>
                                <td class="p-4 text-center font-black bg-amber-50/30 dark:bg-amber-900/5">{{ number_format($item['akhir']) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SECTION II: HASIL CAMPURAN --}}
            <div class="bg-white dark:bg-gray-950 border border-gray-300 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-900 px-4 py-2 border-b border-gray-300 dark:border-gray-800 flex justify-between items-center">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 italic">II. Hasil Produksi Pakan Campuran</h3>
                    <span class="text-[9px] font-bold text-green-600 bg-green-50 dark:bg-green-900/20 px-2 py-0.5 border border-green-100 dark:border-green-800 uppercase italic">Auto-Sync Enabled</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-black font-black uppercase text-gray-600 dark:text-gray-400 text-[9px]">
                                <th class="p-4 border-b border-gray-300 dark:border-gray-800">Nama Produk</th>
                                <th class="p-4 border-b border-gray-300 dark:border-gray-800 text-center w-28 bg-gray-50/50 dark:bg-gray-900/50">Stok Awal</th>
                                <th class="p-4 border-b border-gray-300 dark:border-gray-800 text-center w-28 bg-green-50/50 dark:bg-green-900/10 text-green-700 font-black">Masuk (Auto)</th>
                                <th class="p-4 border-b border-gray-300 dark:border-gray-800 text-center w-28">Keluar P</th>
                                <th class="p-4 border-b border-gray-300 dark:border-gray-800 text-center w-28">Keluar L1</th>
                                <th class="p-4 border-b border-gray-300 dark:border-gray-800 text-center w-28 bg-amber-50/50 dark:bg-amber-900/10 text-amber-600 font-black">Sisa Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 italic">
                            @foreach($campuranState as $idx => $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                <td class="p-4 font-bold border-r border-gray-200 dark:border-gray-800 text-gray-500 uppercase">{{ $item['nama'] }}</td>
                                <td class="p-4 text-center border-r border-gray-200 dark:border-gray-800 text-gray-400 bg-gray-50/30 dark:bg-gray-900/30">{{ number_format($item['awal']) }}</td>
                                <td class="p-4 text-center border-r border-gray-200 dark:border-gray-800 bg-green-50/30 dark:bg-green-900/5 font-black text-green-600">{{ number_format($item['masuk']) }}</td>
                                <td class="p-0 border-r border-gray-200 dark:border-gray-800">
                                    <input type="number" wire:model.live="campuranState.{{ $idx }}.p" @disabled($isLocked)
                                        class="w-full h-12 border-0 bg-transparent text-center font-bold text-sm excel-input disabled:opacity-50">
                                </td>
                                <td class="p-0 border-r border-gray-200 dark:border-gray-800">
                                    <input type="number" wire:model.live="campuranState.{{ $idx }}.l1" @disabled($isLocked)
                                        class="w-full h-12 border-0 bg-transparent text-center font-bold text-sm excel-input disabled:opacity-50">
                                </td>
                                <td class="p-4 text-center font-black bg-amber-50/30 dark:bg-amber-900/5">{{ number_format($item['akhir']) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- AUDIT & KETERANGAN --}}
            <div class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-800 p-5 shadow-sm border-t-4 border-t-blue-600">
                <div class="flex items-center gap-2 mb-4">
                    <x-heroicon-o-shield-check class="w-4 h-4 text-blue-600" />
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Informasi Audit & Keterangan Laporan</h3>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div class="lg:col-span-3">
                        <textarea wire:model="keterangan" @disabled($isLocked)
                            placeholder="Tuliskan catatan produksi atau kendala harian di sini..."
                            class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 p-4 text-xs font-bold outline-none focus:border-blue-500 min-h-[120px] leading-relaxed shadow-inner disabled:italic disabled:text-gray-500 transition-colors"></textarea>
                    </div>
                    <div class="flex flex-col gap-4">
                        <div class="bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 flex flex-col justify-center">
                            <p class="text-[9px] font-black text-gray-400 uppercase mb-1">Status Verifikasi</p>
                            @if($isLocked)
                            <p class="text-xs font-black text-green-600 uppercase italic flex items-center gap-1.5">
                                <x-heroicon-s-check-circle class="w-4 h-4" /> Valid: {{ $currentRecord->validated_by }}
                            </p>
                            <p class="text-[10px] text-gray-400 mt-1 font-mono">{{ $currentRecord->updated_at->format('d/m/Y H:i') }}</p>
                            @else
                            <p class="text-xs font-black text-red-500 uppercase tracking-widest animate-pulse">Menunggu Validasi</p>
                            @endif
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-700 flex flex-col justify-center">
                            <p class="text-[9px] font-black text-gray-400 uppercase mb-1">Dibuat Pada</p>
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase italic">
                                {{ $currentRecord->created_at->translatedFormat('d M Y, H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        @endif
    </div>

    {{-- Script Flatpickr & Modals --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>

    <x-filament-actions::modals />
</x-filament-panels::page>