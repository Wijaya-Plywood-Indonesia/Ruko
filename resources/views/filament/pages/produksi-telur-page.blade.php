<x-filament-panels::page>
    <!-- CSS Khusus mereplikasi grid Microsoft Excel Premium (Mendukung Light & Dark Mode) -->
    <style>
        /* ─── CSS Variables untuk Tema Excel ─── */
        :root {
            --excel-header-bg: #c6efce;
            --excel-header-text: #1a3a2a;
            --excel-even-bg: #E2EFDA;
            --excel-even-text: #1a3a2a;
            --excel-odd-bg: #FCE4D6;
            --excel-odd-text: #3a1a0a;

            --excel-even-sub: #E2EFDA;
            --excel-even-sub-text: #2d6a4f;
            --excel-odd-sub: #FCE4D6;
            --excel-odd-sub-text: #7c3912;

            --excel-border: #c0c0c0;
        }

        /* Override Variable saat masuk ke Dark Mode - Warna Header/Footer tetap Excel Light */
        .dark {
            --excel-header-bg: #c6efce;
            /* Tetap hijau pastel */
            --excel-header-text: #1a3a2a;

            --excel-even-bg: #E2EFDA;
            /* Tetap hijau muda pastel */
            --excel-even-text: #1a3a2a;
            --excel-odd-bg: #FCE4D6;
            /* Tetap oranye muda pastel */
            --excel-odd-text: #3a1a0a;

            --excel-even-sub: #E2EFDA;
            --excel-even-sub-text: #2d6a4f;
            --excel-odd-sub: #FCE4D6;
            --excel-odd-sub-text: #7c3912;

            --excel-border: #9ca3af;
            /* Border diperjelas di dark mode */
        }

        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }

        .excel-grid input:focus {
            outline: 2px solid #107c41 !important;
            box-shadow: none !important;
            z-index: 10;
            background-color: transparent !important;
            /* ← ikuti warna cell */
        }

        .dark .excel-grid input:focus {
            background-color: transparent !important;
            /* ← tidak lagi putih */
            color: inherit !important;
            /* ← ikuti warna teks parent */
            outline: 2px solid #22c55e !important;
        }

        .excel-grid th,
        .excel-grid td {
            border: 1px solid var(--excel-border) !important;
        }
    </style>

    <div class="space-y-4 w-full min-w-0 overflow-hidden">
        @if(empty($kandangs) || (is_array($kandangs) && count($kandangs) === 0))
        <div class="flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
            <x-heroicon-o-building-office-2 class="w-12 h-12 text-gray-300 dark:text-gray-700 mb-4" />
            <h3 class="text-lg font-black text-gray-700 dark:text-gray-200">Data Kandang Belum Tersedia</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Belum ada data kandang yang dapat ditampilkan saat ini.</p>
        </div>
        @else

        @if($is_validated)
        <div class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-400 text-xs font-semibold inline-flex items-center gap-1.5 rounded-none">
            <x-heroicon-o-shield-check class="w-3.5 h-3.5" />
            Divalidasi oleh <span class="font-black mx-1">{{ $namaValidator ?: 'Unknown' }}</span>
            pada {{ $waktuValidasi }}
            @if($namaPenyimpan)
            &nbsp;·&nbsp; Diinput oleh <span class="font-black mx-1">{{ $namaPenyimpan }}</span>
            @endif
        </div>
        @elseif($isDraftSaved && $isCreator)
        <div class="px-3 py-1.5 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/50 text-blue-800 dark:text-blue-400 text-xs font-semibold inline-flex items-center gap-1.5 rounded-none">
            <x-heroicon-o-clock class="w-3.5 h-3.5" />
            Disimpan oleh <span class="font-black mx-1">{{ $namaUserLogin }}</span>
            — Menunggu validasi dari petugas lain
        </div>
        @elseif($isDraftSaved && !$isCreator)
        <div class="px-3 py-1.5 bg-violet-50 dark:bg-violet-950/30 border border-violet-200 dark:border-violet-800/50 text-violet-800 dark:text-violet-400 text-xs font-semibold inline-flex items-center gap-1.5 rounded-none">
            <x-heroicon-o-pencil-square class="w-3.5 h-3.5" />
            Diinput oleh <span class="font-black mx-1">{{ $namaPenyimpan }}</span>
            — Silakan periksa dan validasi
        </div>
        @elseif(!$isEditable)
        <div class="px-3 py-1.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 text-amber-800 dark:text-amber-400 text-xs font-semibold inline-flex items-center gap-1.5 rounded-none">
            <x-heroicon-o-lock-closed class="w-3.5 h-3.5" />
            Terkunci — Anda tidak memiliki akses edit
        </div>
        @endif

        <div class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 p-4 flex flex-col md:flex-row gap-4 justify-between items-center rounded-none shadow-sm">
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="space-y-0.5">
                    <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider">Tanggal</label>
                    <input
                        type="date"
                        wire:model.live="tanggal"
                        class="border border-gray-300 dark:border-gray-700 px-2.5 py-1 text-xs font-bold bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-white focus:outline-none focus:border-gray-500 rounded-none shadow-inner"
                        {{ !$isEditable ? 'disabled' : '' }}>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 w-full md:max-w-xl">
                <div class="bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-none border border-gray-200 dark:border-gray-700 flex flex-col justify-center">
                    <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Butir</span>
                    <div class="text-lg font-black text-gray-800 dark:text-gray-100 mt-0.5">
                        {{ number_format($grandTotal['butir']) }} <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">Btr</span>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-none border border-gray-200 dark:border-gray-700 flex flex-col justify-center">
                    <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Kilo</span>
                    <div class="text-lg font-black text-gray-800 dark:text-gray-100 mt-0.5">
                        {{ number_format($grandTotal['kilo'], 1) }} <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">Kg</span>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-none border border-gray-200 dark:border-gray-700 flex flex-col justify-center">
                    <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Tray</span>
                    <div class="text-lg font-black text-gray-800 dark:text-gray-100 mt-0.5">
                        {{ number_format($grandTotal['tray']) }} <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">Tray</span>
                    </div>
                </div>
            </div>

            <div class="flex gap-2 w-full md:w-auto justify-end">
                @if($showSaveButton)
                <button
                    type="button"
                    x-data
                    x-on:click="if (confirm('Apakah Anda yakin ingin menyimpan data ini?\nPastikan semua data sudah benar.')) { $wire.save() }"
                    class="px-5 py-1.5 text-xs font-bold text-white transition shadow-sm border rounded-none hover:brightness-110"
                    style="background-color:#107c41; border-color:#107c41;">
                    Simpan
                </button>
                @endif
                @if($showValidateButton)
                <button
                    type="button"
                    wire:click="validateProduksi"
                    wire:confirm="Data yang sudah divalidasi tidak dapat diubah kecuali oleh Super Admin. Lanjutkan?"
                    class="px-5 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition rounded-none shadow-sm">
                    Validasi
                </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto w-full border border-gray-300 dark:border-gray-750 bg-white dark:bg-black rounded-none">
            @php
            $lebarMinimal = 48 + (count($kandangs) * 240) + 20;
            @endphp
            <table id="excel-table" class="text-sm border-collapse excel-grid" style="min-width: {{ $lebarMinimal }}px;">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-900">
                        <th class="px-2 py-2 text-center w-12 font-bold text-gray-500 dark:text-gray-400 bg-gray-200 dark:bg-gray-800" rowspan="2">No</th>
                        @foreach($kandangs as $index => $kandang)
                        @php
                        $idKandang = $kandang['id'];
                        $isEven = ($index % 2 === 0);
                        $headerBgVar = $isEven ? 'var(--excel-even-bg)' : 'var(--excel-odd-bg)';
                        $headerColorVar = $isEven ? 'var(--excel-even-text)' : 'var(--excel-odd-text)';
                        @endphp
                        <th class="px-3 py-2 border shadow-sm" style="background-color: {{ $headerBgVar }}; color: {{ $headerColorVar }};" colSpan="3">
                            <div class="flex items-center justify-between gap-2 w-full">
                                <span class="font-black uppercase text-[13px] tracking-wider whitespace-nowrap">{{ $kandang['nama_kandang'] }}</span>
                                <div class="w-32">
                                    <select wire:model.live="kandangPakan.{{ $idKandang }}" class="w-full text-center text-xs font-bold border border-gray-300 dark:border-slate-600 dark:text-white bg-white dark:bg-slate-800 py-1 px-1 rounded shadow-sm" {{ !$isEditable ? 'disabled' : '' }}>
                                        <option value="">Pilih Pakan</option>
                                        @foreach($allPakan as $pakan)
                                        <option value="{{ $pakan['id'] }}">{{ $pakan['nama_barang'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </th>
                        @endforeach
                    </tr>
                    <tr class="bg-gray-100/50">
                        @foreach($kandangs as $index => $kandang)
                        <th class="py-1 text-center font-bold text-xs"
                            style="background-color: var(--excel-even-sub); color: var(--excel-even-sub-text);">Butir</th>
                        <th class="py-1 text-center font-bold text-xs"
                            style="background-color: var(--excel-even-sub); color: var(--excel-even-sub-text);">Kilo</th>
                        <th class="py-1 text-center font-bold text-xs"
                            style="background-color: var(--excel-even-sub); color: var(--excel-even-sub-text);">Tray</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-300 dark:divide-gray-850">
                    @for($rowIdx = 0; $rowIdx < $maxRows; $rowIdx++)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                        <td class="text-center font-black text-gray-900 bg-white border border-gray-300 dark:border-gray-700 py-1.5 text-xs dark:bg-gray-800 dark:text-gray-300">
                            {{ $rowIdx + 1 }}
                        </td>
                        @foreach($kandangs as $index => $kandang)
                        @php
                        $idKandang = $kandang['id'];
                        $isEven = ($index % 2 === 0);
                        $cellBgBtr = $isEven ? 'var(--excel-even-btr)' : 'var(--excel-odd-btr)';
                        $cellBgKg = $isEven ? 'var(--excel-even-kg)' : 'var(--excel-odd-kg)';
                        $cellBgTray = $isEven ? 'var(--excel-even-tray)' : 'var(--excel-odd-tray)';
                        @endphp
                        <td class="p-0.5 border border-gray-300 dark:border-gray-750" style="background-color: {{ $cellBgBtr }};">
                            <input type="number" wire:model.live="gridData.{{ $idKandang }}.{{ $rowIdx }}.butir" placeholder="0" data-row="{{ $rowIdx }}" data-col="{{ $idKandang }}_butir" class="w-full text-center text-[13px] font-bold border-0 bg-transparent py-1 px-0.5 outline-none focus:ring-0 rounded-none text-gray-900 dark:text-white" {{ !$isEditable ? 'disabled' : '' }}>
                        </td>
                        <td class="p-0.5 border border-gray-300 dark:border-gray-750" style="background-color: {{ $cellBgKg }};">
                            <input type="number" step="0.01" wire:model.live="gridData.{{ $idKandang }}.{{ $rowIdx }}.kilo" placeholder="0.0" data-row="{{ $rowIdx }}" data-col="{{ $idKandang }}_kilo" class="w-full text-center text-[13px] font-bold border-0 bg-transparent py-1 px-0.5 outline-none focus:ring-0 rounded-none text-gray-900 dark:text-white" {{ !$isEditable ? 'disabled' : '' }}>
                        </td>
                        <td class="p-0.5 border border-gray-300 dark:border-gray-700" style="background-color: {{ $cellBgTray }};">
                            <input type="number" step="0.1" wire:model.live="gridData.{{ $idKandang }}.{{ $rowIdx }}.tray" placeholder="0" data-row="{{ $rowIdx }}" data-col="{{ $idKandang }}_tray" class="w-full text-center text-[13px] font-bold border-0 bg-transparent py-1 px-0.5 outline-none focus:ring-0 rounded-none text-gray-900 dark:text-white" {{ !$isEditable ? 'disabled' : '' }}>
                        </td>
                        @endforeach
                        </tr>
                        @endfor
                </tbody>
                <tfoot>
                    <tr class="font-black border-t-2 border-b-4 border-gray-400 dark:border-gray-700 bg-gray-250 dark:bg-gray-900">
                        <td class="text-center font-bold text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700 text-xs py-2">TOTAL</td>
                        @foreach($kandangs as $index => $kandang)
                        @php
                        $idKandang = $kandang['id'];
                        $isEven = ($index % 2 === 0);
                        $totalBgVar = $isEven ? 'var(--excel-even-bg)' : 'var(--excel-odd-bg)';
                        $totalColorVar = $isEven ? 'var(--excel-even-text)' : 'var(--excel-odd-text)';
                        @endphp
                        <td class="py-2.5 text-center text-xs font-black border border-gray-300 dark:border-gray-750" style="background-color: {{ $totalBgVar }}; color: {{ $totalColorVar }};">
                            {{ number_format($kandangTotals[$idKandang]['butir'] ?? 0) }}
                        </td>
                        <td class="py-2.5 text-center text-xs font-black border border-gray-300 dark:border-gray-750" style="background-color: {{ $totalBgVar }}; color: {{ $totalColorVar }};">
                            {{ number_format($kandangTotals[$idKandang]['kilo'] ?? 0, 1) }}
                        </td>
                        <td class="py-2.5 text-center text-xs font-black border border-gray-300 dark:border-gray-750" style="background-color: {{ $totalBgVar }}; color: {{ $totalColorVar }};">
                            {{ number_format($kandangTotals[$idKandang]['tray'] ?? 0, 1) }}
                        </td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

    <script>
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const target = e.target;
                if (target.tagName === 'INPUT' && target.hasAttribute('data-row') && target.hasAttribute('data-col')) {
                    e.preventDefault();
                    const currentRow = parseInt(target.getAttribute('data-row'));
                    const colId = target.getAttribute('data-col');
                    const targetRow = e.shiftKey ? currentRow - 1 : currentRow + 1;
                    const nextInput = document.querySelector(`input[data-row="${targetRow}"][data-col="${colId}"]`);
                    if (nextInput) {
                        nextInput.focus();
                        nextInput.select();
                    }
                }
            }
        });
    </script>
</x-filament-panels::page>