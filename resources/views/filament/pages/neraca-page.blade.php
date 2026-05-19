<x-filament-panels::page>

    {{-- ══════════════════════════════════════════════════════════
         HEADER + TOMBOL EXPORT
    ══════════════════════════════════════════════════════════ --}}
    <div class="flex items-center justify-between mb-2">
        <div>
            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-widest">INA TELUR</p>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Neraca Telur</h1>
        </div>

        @if($this->periodeValid() && $this->jumlahPeriode() > 0)
        <button
            wire:click="exportExcel"
            wire:loading.attr="disabled"
            wire:target="exportExcel"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold
                       bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800
                       text-white shadow-sm transition-colors
                       disabled:opacity-60 disabled:cursor-not-allowed">

            <svg wire:loading wire:target="exportExcel"
                class="animate-spin w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>

            <svg wire:loading.remove wire:target="exportExcel"
                class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>

            <span wire:loading.remove wire:target="exportExcel">
                Export Excel
                <span class="font-normal opacity-75">({{ $this->jumlahPeriode() }} {{ $jenisFilter }})</span>
            </span>
            <span wire:loading wire:target="exportExcel">Mengunduh...</span>
        </button>
        @else
        <button disabled
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold
                       bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500
                       cursor-not-allowed shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Export Excel
        </button>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════
         FILTER (BARU: MENDUKUNG BULANAN & HARIAN)
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">

        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4 mb-5">
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200">
                Pilih Periode Neraca
            </h3>

            {{-- Tombol Toggle Pilihan Bulanan / Harian --}}
            <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700 p-1 rounded-xl">
                <button type="button" wire:click="ubahJenisFilter('bulan')"
                    class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all {{ $jenisFilter === 'bulan' ? 'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-white' : 'text-gray-500' }}">
                    Bulanan
                </button>
                <button type="button" wire:click="ubahJenisFilter('hari')"
                    class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all {{ $jenisFilter === 'hari' ? 'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-white' : 'text-gray-500' }}">
                    Harian
                </button>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-end gap-6">

            {{-- Input Dari Periode / Tanggal --}}
            <div class="flex-1 w-full">
                <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">
                    Dari {{ $jenisFilter === 'hari' ? 'Tanggal' : 'Bulan' }}
                </label>
                <input type="{{ $jenisFilter === 'hari' ? 'date' : 'month' }}" wire:model.live="periodeAwal"
                    min="{{ $jenisFilter === 'hari' ? now()->subYears(5)->format('Y-m-d') : now()->subYears(5)->format('Y-m') }}"
                    max="{{ $jenisFilter === 'hari' ? now()->addYear()->format('Y-m-d') : now()->addYear()->format('Y-m') }}"
                    class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600
                           dark:bg-gray-700 dark:text-gray-200 text-base px-4 py-3 shadow-sm
                           focus:border-primary-500 focus:ring-2 focus:ring-primary-200
                           transition-colors cursor-pointer" />
            </div>

            <div class="hidden sm:flex items-center pb-3 text-gray-400 dark:text-gray-500">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </div>

            {{-- Input Sampai Periode / Tanggal --}}
            <div class="flex-1 w-full">
                <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">
                    Sampai {{ $jenisFilter === 'hari' ? 'Tanggal' : 'Bulan' }}
                </label>
                <input type="{{ $jenisFilter === 'hari' ? 'date' : 'month' }}" wire:model.live="periodeAkhir"
                    min="{{ $jenisFilter === 'hari' ? now()->subYears(5)->format('Y-m-d') : now()->subYears(5)->format('Y-m') }}"
                    max="{{ $jenisFilter === 'hari' ? now()->addYear()->format('Y-m-d') : now()->addYear()->format('Y-m') }}"
                    class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600
                           dark:bg-gray-700 dark:text-gray-200 text-base px-4 py-3 shadow-sm
                           focus:border-primary-500 focus:ring-2 focus:ring-primary-200
                           transition-colors cursor-pointer" />
            </div>

            {{-- Status Validasi Periode --}}
            <div class="flex-shrink-0 pb-1">
                @if(!$this->periodeValid())
                <div class="flex items-center gap-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700
                                text-red-700 dark:text-red-400 rounded-xl px-4 py-3 text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    "Dari" tidak boleh melebihi "Sampai"
                </div>
                @else
                <div class="flex items-center gap-2 bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700
                                text-primary-700 dark:text-primary-400 rounded-xl px-4 py-3 text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span><strong>{{ $this->jumlahPeriode() }}</strong> data {{ $jenisFilter }}an ditampilkan</span>
                </div>
                @endif
            </div>
        </div>

        <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
            * Maksimal 12 bulan untuk filter bulanan, dan maksimal 31 hari untuk filter harian.
        </p>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         KONTEN NERACA
    ══════════════════════════════════════════════════════════ --}}
    @if(!$this->periodeValid())
    <div class="text-center py-16">
        <svg class="mx-auto w-14 h-14 text-red-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-gray-500 dark:text-gray-400">Perbaiki periode filter terlebih dahulu.</p>
    </div>

    @elseif($this->jumlahPeriode() === 0)
    <div class="text-center py-16">
        <svg class="mx-auto w-14 h-14 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-gray-500 dark:text-gray-400">Pilih periode untuk menampilkan neraca.</p>
    </div>

    @else
    <div class="space-y-8">
        @foreach($this->neracaMulti as $key => $neraca)
        @php
        $isBalance = abs($neraca['totalAktiva'] - $neraca['totalPasiva']) < 1;

            $fmt=fn(?float $v)=> $v !== null ? number_format($v, 0, ',', '.') : '-';

            // Format QTY diubah agar lebih robust menolak null/string kosong
            $fmtQty = fn($v) => (is_numeric($v) && $v != 0) ? number_format((float)$v, 0, ',', '.') : null;

            $flattenSections = null;
            $flattenSections = function(array $sections, int $depth = 0) use (&$flattenSections): array {
            $rows = [];
            foreach ($sections as $section) {
            $hasSub = !empty($section['sub_sections']);
            $hasItem = !empty($section['items']);

            $rows[] = [
            'type' => $depth === 0 ? 'header' : 'subheader',
            'label' => $section['group'],
            'kode' => null,
            'depth' => $depth,
            ];

            if ($hasSub) {
            $rows = array_merge($rows, $flattenSections($section['sub_sections'], $depth + 1));
            $rows[] = [
            'type' => 'subtotal',
            'label' => 'Total ' . $section['group'],
            'kode' => null,
            'nilai' => $section['total'],
            'qty' => null,
            'depth' => $depth,
            ];
            }

            if ($hasItem) {
            foreach ($section['items'] as $item) {
            $rows[] = [
            'type' => 'item',
            'label' => $item['nama'],
            'kode' => $item['kode'],
            'nilai' => $item['nilai'],
            'qty' => $item['qty'] ?? null,
            'depth' => $depth,
            ];
            }
            $rows[] = [
            'type' => 'subtotal',
            'label' => 'Total ' . $section['group'],
            'kode' => null,
            'nilai' => $section['total'],
            'qty' => null,
            'depth' => $depth,
            ];
            }
            }
            return $rows;
            };

            $aktivaRowsRaw = $flattenSections($neraca['aktiva']['sections']);
            $pasivaRowsRaw = $flattenSections($neraca['pasiva']['sections']);

            $filterRows = function(array $rows) use ($tampilkanSaldoNol): array {
            if ($tampilkanSaldoNol) return $rows;
            return array_values(array_filter($rows, function($row) {
            if ($row['type'] === 'item' && ($row['nilai'] ?? 0) == 0) {
            return false;
            }
            return true;
            }));
            };

            $aktivaRows = $filterRows($aktivaRowsRaw);
            $pasivaRows = $filterRows($pasivaRowsRaw);
            $maxRows = max(count($aktivaRows), count($pasivaRows), 1);
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">

                {{-- Card Header --}}
                <div class="flex items-center justify-between px-6 py-4
                        border-b border-gray-100 dark:border-gray-700
                        bg-gray-50 dark:bg-gray-800">
                    <div>
                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5">
                            INA TELUR
                        </p>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                            Neraca &mdash; {{ $neraca['label'] }}
                        </h2>
                    </div>

                    <div class="flex items-center gap-3">
                        {{-- Tombol toggle saldo nol --}}
                        <button
                            wire:click="$toggle('tampilkanSaldoNol')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                               transition-colors border
                               {{ $tampilkanSaldoNol
                                   ? 'bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-900 border-gray-700 dark:border-gray-300'
                                   : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:border-gray-400' }}">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                @if($tampilkanSaldoNol)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21" />
                                @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                @endif
                            </svg>
                            {{ $tampilkanSaldoNol ? 'Sembunyikan Saldo Nol' : 'Tampilkan Saldo Nol' }}
                        </button>

                        {{-- Badge balance --}}
                        @if($isBalance)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                                 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                 border border-green-200 dark:border-green-700">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            Balance
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                                 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                 border border-red-200 dark:border-red-700">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Tidak Balance
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Tabel Neraca Dua Kolom --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse" style="min-width:700px">
                        <thead>
                            <tr>
                                <th colspan="3"
                                    class="border border-gray-200 dark:border-gray-700
                                       bg-blue-50 dark:bg-blue-900/20 py-3 px-5
                                       text-center text-sm font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wide">
                                    AKTIVA
                                </th>
                                <th colspan="3"
                                    class="border border-gray-200 dark:border-gray-700
                                       bg-green-50 dark:bg-green-900/20 py-3 px-5
                                       text-center text-sm font-bold text-green-700 dark:text-green-300 uppercase tracking-wide">
                                    PASIVA
                                </th>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-700/40 text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide">
                                <th class="border border-gray-100 dark:border-gray-700 py-1.5 px-4 text-left font-semibold w-[32%]">Akun</th>
                                <th class="border border-gray-100 dark:border-gray-700 py-1.5 px-3 text-right font-semibold w-[7%]">Qty</th>
                                <th class="border border-gray-100 dark:border-gray-700 py-1.5 px-4 text-right font-semibold w-[11%]">Nilai (Rp)</th>
                                <th class="border border-gray-100 dark:border-gray-700 py-1.5 px-4 text-left font-semibold w-[32%]">Akun</th>
                                <th class="border border-gray-100 dark:border-gray-700 py-1.5 px-3 text-right font-semibold w-[7%]">Qty</th>
                                <th class="border border-gray-100 dark:border-gray-700 py-1.5 px-4 text-right font-semibold w-[11%]">Nilai (Rp)</th>
                            </tr>
                        </thead>

                        <tbody>
                            @for($i = 0; $i < $maxRows; $i++)
                                @php
                                $aRow=$aktivaRows[$i] ?? null;
                                $pRow=$pasivaRows[$i] ?? null;

                                $rowType=$aRow['type'] ?? $pRow['type'] ?? 'item' ;
                                $isHdr=$rowType==='header' ;
                                $isSub=$rowType==='subheader' ;
                                $isTot=$rowType==='subtotal' ;
                                $isItem=$rowType==='item' ;

                                $aDepth=$aRow['depth'] ?? 0;
                                $pDepth=$pRow['depth'] ?? 0;
                                $aPl=$aDepth> 0 ? 'pl-' . (4 + ($aDepth * 4)) : 'pl-4';
                                $pPl = $pDepth > 0 ? 'pl-' . (4 + ($pDepth * 4)) : 'pl-4';
                                @endphp

                                <tr class="
                            {{ $isHdr  ? 'bg-gray-50 dark:bg-gray-700/50' : '' }}
                            {{ $isSub  ? 'bg-gray-50/70 dark:bg-gray-700/30' : '' }}
                            {{ $isTot  ? 'bg-gray-100 dark:bg-gray-700' : '' }}
                            {{ $isItem ? 'hover:bg-gray-50/50 dark:hover:bg-gray-700/20' : '' }}
                            transition-colors">

                                    {{-- ── AKTIVA ── --}}
                                    <td class="border border-gray-100 dark:border-gray-700 py-2
                                {{ $isHdr  ? 'text-center font-bold text-gray-700 dark:text-gray-200 px-5' : '' }}
                                {{ $isSub  ? 'font-semibold text-gray-600 dark:text-gray-300 ' . $aPl : '' }}
                                {{ $isTot  ? 'font-semibold text-gray-800 dark:text-gray-100 ' . $aPl : '' }}
                                {{ $isItem ? 'text-gray-700 dark:text-gray-300 ' . $aPl : '' }}">
                                        @if($aRow)
                                        <span class="{{ $isSub ? 'text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400' : '' }}">
                                            @if($isItem)
                                            <span class="inline-flex items-center gap-2">
                                                @if(!empty($aRow['kode']))
                                                <span class="font-mono text-xs text-amber-600 dark:text-amber-400
                                                                 bg-amber-50 dark:bg-amber-900/20
                                                                 border border-amber-200 dark:border-amber-800
                                                                 px-1.5 py-0.5 rounded whitespace-nowrap">
                                                    {{ $aRow['kode'] }}
                                                </span>
                                                @endif
                                                {{ $aRow['label'] }}
                                            </span>
                                            @else
                                            {{ $aRow['label'] }}
                                            @endif
                                        </span>
                                        @endif
                                    </td>

                                    <td class="border border-gray-100 dark:border-gray-700 py-2 px-3 text-right tabular-nums whitespace-nowrap">
                                        @if($aRow && $isItem && !empty($aRow['qty']))
                                        <span class="text-xs text-gray-400 dark:text-gray-500 font-normal">
                                            {{ $fmtQty($aRow['qty']) }}
                                        </span>
                                        @endif
                                    </td>

                                    <td class="border border-gray-100 dark:border-gray-700 py-2 px-4 text-right tabular-nums whitespace-nowrap
                                {{ $isTot ? 'font-semibold text-gray-800 dark:text-gray-100' : 'text-gray-700 dark:text-gray-300' }}">
                                        @if($aRow && isset($aRow['nilai']) && !$isHdr && !$isSub)
                                        <span class="{{ $isTot ? 'border-t border-b border-gray-400 dark:border-gray-500 px-1' : '' }}">
                                            {{ $fmt($aRow['nilai']) }}
                                        </span>
                                        @endif
                                    </td>

                                    {{-- ── PASIVA ── --}}
                                    <td class="border border-gray-100 dark:border-gray-700 py-2
                                {{ $isHdr  ? 'text-center font-bold text-gray-700 dark:text-gray-200 px-5' : '' }}
                                {{ $isSub  ? 'font-semibold text-gray-600 dark:text-gray-300 ' . $pPl : '' }}
                                {{ $isTot  ? 'font-semibold text-gray-800 dark:text-gray-100 ' . $pPl : '' }}
                                {{ $isItem ? 'text-gray-700 dark:text-gray-300 ' . $pPl : '' }}">
                                        @if($pRow)
                                        <span class="{{ $isSub ? 'text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400' : '' }}">
                                            @if($isItem)
                                            <span class="inline-flex items-center gap-2">
                                                @if(!empty($pRow['kode']))
                                                <span class="font-mono text-xs text-amber-600 dark:text-amber-400
                                                                 bg-amber-50 dark:bg-amber-900/20
                                                                 border border-amber-200 dark:border-amber-800
                                                                 px-1.5 py-0.5 rounded whitespace-nowrap">
                                                    {{ $pRow['kode'] }}
                                                </span>
                                                @endif
                                                {{ $pRow['label'] }}
                                            </span>
                                            @else
                                            {{ $pRow['label'] }}
                                            @endif
                                        </span>
                                        @endif
                                    </td>

                                    <td class="border border-gray-100 dark:border-gray-700 py-2 px-3 text-right tabular-nums whitespace-nowrap">
                                        @if($pRow && $isItem && !empty($pRow['qty']))
                                        <span class="text-xs text-gray-400 dark:text-gray-500 font-normal">
                                            {{ $fmtQty($pRow['qty']) }}
                                        </span>
                                        @endif
                                    </td>

                                    <td class="border border-gray-100 dark:border-gray-700 py-2 px-4 text-right tabular-nums whitespace-nowrap
                                {{ $isTot ? 'font-semibold text-gray-800 dark:text-gray-100' : 'text-gray-700 dark:text-gray-300' }}">
                                        @if($pRow && isset($pRow['nilai']) && !$isHdr && !$isSub)
                                        <span class="{{ $isTot ? 'border-t border-b border-gray-400 dark:border-gray-500 px-1' : '' }}">
                                            {{ $fmt($pRow['nilai']) }}
                                        </span>
                                        @endif
                                    </td>
                                </tr>
                                @endfor

                                {{-- Grand Total --}}
                                <tr class="bg-gray-800 dark:bg-gray-900 text-white">
                                    <td colspan="2" class="border border-gray-700 px-5 py-3">
                                        <span class="font-bold text-sm uppercase tracking-wide">Total Aktiva</span>
                                    </td>
                                    <td class="border border-gray-700 px-4 py-3 text-right tabular-nums">
                                        <span class="font-bold text-base border-t-2 border-b-4 border-double border-white px-2">
                                            {{ $fmt($neraca['totalAktiva']) }}
                                        </span>
                                    </td>
                                    <td colspan="2" class="border border-gray-700 px-5 py-3">
                                        <span class="font-bold text-sm uppercase tracking-wide">Total Pasiva</span>
                                    </td>
                                    <td class="border border-gray-700 px-4 py-3 text-right tabular-nums">
                                        <span class="font-bold text-base border-t-2 border-b-4 border-double border-white px-2">
                                            {{ $fmt($neraca['totalPasiva']) }}
                                        </span>
                                    </td>
                                </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Card Footer --}}
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-100 dark:border-gray-700
                        text-xs text-gray-400 dark:text-gray-500 flex justify-between">
                    <span>Data ditarik secara {{ $jenisFilter }}an</span>
                    @if(!$isBalance)
                    <span class="text-red-500 font-medium">
                        Selisih: {{ $fmt(abs($neraca['totalAktiva'] - $neraca['totalPasiva'])) }}
                    </span>
                    @else
                    <span class="text-green-500 font-medium">✓ Aktiva = Pasiva</span>
                    @endif
                </div>

            </div>
            @endforeach
    </div>
    @endif

</x-filament-panels::page>