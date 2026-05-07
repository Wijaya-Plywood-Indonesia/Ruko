<x-filament-panels::page>

    {{-- ══════════════════════════════════════════════════════════
         FILTER — 2 Dropdown Periode
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">

        <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-5">
            Pilih Periode Neraca
        </h3>

        <div class="flex flex-col sm:flex-row items-start sm:items-end gap-6">

            {{-- DARI --}}
            <div class="flex-1 w-full">
                <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">
                    Dari Periode
                </label>
                <input
                    type="month"
                    wire:model.live="periodeAwal"
                    min="{{ now()->subYears(5)->format('Y-m') }}"
                    max="{{ now()->addYear()->format('Y-m') }}"
                    class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600
                           dark:bg-gray-700 dark:text-gray-200
                           text-base px-4 py-3 shadow-sm
                           focus:border-primary-500 focus:ring-2 focus:ring-primary-200
                           transition-colors cursor-pointer" />
            </div>

            {{-- Pemisah --}}
            <div class="hidden sm:flex items-center pb-3 text-gray-400 dark:text-gray-500">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </div>

            {{-- SAMPAI --}}
            <div class="flex-1 w-full">
                <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">
                    Sampai Periode
                </label>
                <input
                    type="month"
                    wire:model.live="periodeAkhir"
                    min="{{ now()->subYears(5)->format('Y-m') }}"
                    max="{{ now()->addYear()->format('Y-m') }}"
                    class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600
                           dark:bg-gray-700 dark:text-gray-200
                           text-base px-4 py-3 shadow-sm
                           focus:border-primary-500 focus:ring-2 focus:ring-primary-200
                           transition-colors cursor-pointer" />
            </div>

            {{-- Info jumlah periode --}}
            <div class="flex-shrink-0 pb-1">
                @if(!$this->periodeValid())
                    <div class="flex items-center gap-2 bg-red-50 dark:bg-red-900/20
                                border border-red-200 dark:border-red-700
                                text-red-700 dark:text-red-400
                                rounded-xl px-4 py-3 text-sm font-medium">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        "Dari" tidak boleh lebih akhir dari "Sampai"
                    </div>
                @else
                    <div class="flex items-center gap-2 bg-primary-50 dark:bg-primary-900/20
                                border border-primary-200 dark:border-primary-700
                                text-primary-700 dark:text-primary-400
                                rounded-xl px-4 py-3 text-sm font-medium">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>
                            <strong>{{ $this->jumlahPeriode() }}</strong>
                            neraca ditampilkan
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
            * Maksimal 12 bulan sekaligus. Jika rentang melebihi 12 bulan, sistem otomatis membatasi sampai bulan ke-12.
        </p>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         KONTEN NERACA
    ══════════════════════════════════════════════════════════ --}}
    @if(!$this->periodeValid())
        <div class="text-center py-16">
            <svg class="mx-auto w-14 h-14 text-red-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400">Perbaiki periode filter terlebih dahulu.</p>
        </div>

    @elseif($this->jumlahPeriode() === 0)
        <div class="text-center py-16">
            <svg class="mx-auto w-14 h-14 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400">Pilih periode untuk menampilkan neraca.</p>
        </div>

    @else
    <div class="space-y-8">
        @foreach($this->neracaMulti as $key => $neraca)
        @php
            $isBalance = abs($neraca['totalAktiva'] - $neraca['totalPasiva']) < 1;
            $fmt = fn(?float $v) => $v !== null ? number_format($v, 0, ',', '.') : '-';

            $flattenSections = null;
            $flattenSections = function(array $sections, int $depth = 0) use (&$flattenSections): array {
                $rows = [];
                foreach ($sections as $section) {
                    $hasSub  = !empty($section['sub_sections']);
                    $hasItem = !empty($section['items']);

                    $rows[] = [
                        'type'  => $depth === 0 ? 'header' : 'subheader',
                        'label' => $section['group'],
                        'depth' => $depth,
                    ];

                    if ($hasSub) {
                        $rows = array_merge($rows, $flattenSections($section['sub_sections'], $depth + 1));
                        $rows[] = [
                            'type'  => 'subtotal',
                            'label' => 'Total ' . $section['group'],
                            'nilai' => $section['total'],
                            'depth' => $depth,
                        ];
                    }

                    if ($hasItem) {
                        foreach ($section['items'] as $item) {
                            $rows[] = [
                                'type'  => 'item',
                                'label' => $item['nama'],
                                'nilai' => $item['nilai'],
                                'depth' => $depth,
                            ];
                        }
                        $rows[] = [
                            'type'  => 'subtotal',
                            'label' => 'Total ' . $section['group'],
                            'nilai' => $section['total'],
                            'depth' => $depth,
                        ];
                    }
                }
                return $rows;
            };

            $aktivaRows = $flattenSections($neraca['aktiva']['sections']);
            $pasivaRows = $flattenSections($neraca['pasiva']['sections']);
            $maxRows    = max(count($aktivaRows), count($pasivaRows), 1);
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

                @if($isBalance)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                             bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                             border border-green-200 dark:border-green-700">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Balance
                </span>
                @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                             bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                             border border-red-200 dark:border-red-700">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Tidak Balance
                </span>
                @endif
            </div>

            {{-- Tabel Neraca Dua Kolom --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse" style="min-width:600px">
                    <thead>
                        <tr>
                            <th class="w-1/2 border border-gray-200 dark:border-gray-700
                                       bg-blue-50 dark:bg-blue-900/20
                                       py-3 px-5 text-center text-sm font-bold
                                       text-blue-700 dark:text-blue-300 uppercase tracking-wide">
                                AKTIVA
                            </th>
                            <th class="w-1/2 border border-gray-200 dark:border-gray-700
                                       bg-green-50 dark:bg-green-900/20
                                       py-3 px-5 text-center text-sm font-bold
                                       text-green-700 dark:text-green-300 uppercase tracking-wide">
                                PASIVA
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @for($i = 0; $i < $maxRows; $i++)
                        @php
                            $aRow    = $aktivaRows[$i] ?? null;
                            $pRow    = $pasivaRows[$i] ?? null;
                            $rowType = $aRow['type'] ?? $pRow['type'] ?? 'item';
                            $isHdr   = $rowType === 'header';
                            $isSub   = $rowType === 'subheader';
                            $isTot   = $rowType === 'subtotal';
                            $aDepth  = $aRow['depth'] ?? 0;
                            $pDepth  = $pRow['depth'] ?? 0;
                            $aIndent = $aDepth > 0 ? 'pl-' . (5 + ($aDepth * 4)) : 'pl-5';
                            $pIndent = $pDepth > 0 ? 'pl-' . (5 + ($pDepth * 4)) : 'pl-5';
                        @endphp

                        <tr class="
                            {{ $isHdr ? 'bg-gray-50 dark:bg-gray-700/50' : '' }}
                            {{ $isSub ? 'bg-gray-50/70 dark:bg-gray-700/30' : '' }}
                            {{ $isTot ? 'bg-gray-100 dark:bg-gray-700' : '' }}
                            {{ !$isHdr && !$isSub && !$isTot ? 'hover:bg-gray-50/50 dark:hover:bg-gray-700/20' : '' }}
                            transition-colors">

                            {{-- Kolom AKTIVA --}}
                            <td class="border border-gray-100 dark:border-gray-700 py-2 pr-5
                                {{ $isHdr ? 'text-center font-bold text-gray-700 dark:text-gray-200 px-5' : '' }}
                                {{ $isSub ? 'font-semibold text-gray-600 dark:text-gray-300 ' . $aIndent : '' }}
                                {{ $isTot ? 'font-semibold text-gray-800 dark:text-gray-100 ' . $aIndent : '' }}
                                {{ !$isHdr && !$isSub && !$isTot ? 'text-gray-700 dark:text-gray-300 ' . $aIndent : '' }}">
                                @if($aRow)
                                    <div class="flex justify-between items-center gap-4">
                                        <span class="{{ $isSub ? 'text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400' : '' }}">
                                            @if(!$isHdr && !$isSub)- @endif{{ $aRow['label'] }}
                                        </span>
                                        @if(isset($aRow['nilai']) && !$isHdr && !$isSub)
                                            <span class="tabular-nums flex-shrink-0
                                                {{ $isTot ? 'border-t border-b border-gray-400 dark:border-gray-500 px-1' : '' }}">
                                                {{ $fmt($aRow['nilai']) }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- Kolom PASIVA --}}
                            <td class="border border-gray-100 dark:border-gray-700 py-2 pr-5
                                {{ $isHdr ? 'text-center font-bold text-gray-700 dark:text-gray-200 px-5' : '' }}
                                {{ $isSub ? 'font-semibold text-gray-600 dark:text-gray-300 ' . $pIndent : '' }}
                                {{ $isTot ? 'font-semibold text-gray-800 dark:text-gray-100 ' . $pIndent : '' }}
                                {{ !$isHdr && !$isSub && !$isTot ? 'text-gray-700 dark:text-gray-300 ' . $pIndent : '' }}">
                                @if($pRow)
                                    <div class="flex justify-between items-center gap-4">
                                        <span class="{{ $isSub ? 'text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400' : '' }}">
                                            @if(!$isHdr && !$isSub)- @endif{{ $pRow['label'] }}
                                        </span>
                                        @if(isset($pRow['nilai']) && !$isHdr && !$isSub)
                                            <span class="tabular-nums flex-shrink-0
                                                {{ $isTot ? 'border-t border-b border-gray-400 dark:border-gray-500 px-1' : '' }}">
                                                {{ $fmt($pRow['nilai']) }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @endfor

                        {{-- Grand Total --}}
                        <tr class="bg-gray-800 dark:bg-gray-900 text-white">
                            <td class="border border-gray-700 px-5 py-3">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-sm uppercase tracking-wide">Total Aktiva</span>
                                    <span class="tabular-nums font-bold text-base
                                                 border-t-2 border-b-4 border-double border-white px-2">
                                        {{ $fmt($neraca['totalAktiva']) }}
                                    </span>
                                </div>
                            </td>
                            <td class="border border-gray-700 px-5 py-3">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-sm uppercase tracking-wide">Total Pasiva</span>
                                    <span class="tabular-nums font-bold text-base
                                                 border-t-2 border-b-4 border-double border-white px-2">
                                        {{ $fmt($neraca['totalPasiva']) }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Card Footer --}}
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/30
                        border-t border-gray-100 dark:border-gray-700
                        text-xs text-gray-400 dark:text-gray-500 flex justify-between">
                <span>Data dari Buku Besar {{ $neraca['label'] }}</span>
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