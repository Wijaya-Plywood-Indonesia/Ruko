<x-filament-panels::page>

{{-- ══════════════════════════════════════════════════════════
     FILTER
══════════════════════════════════════════════════════════ --}}
<div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 mb-6">

    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-5">
        Filter Periode Laba Rugi
    </h3>

    <div class="flex flex-col sm:flex-row items-start sm:items-end gap-6">

        <div class="flex-1 w-full">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Dari Periode</label>
            <input type="month" wire:model.live="periodeAwal"
                min="{{ now()->subYears(5)->format('Y-m') }}"
                max="{{ now()->addYear()->format('Y-m') }}"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700
                       dark:bg-gray-800 dark:text-gray-200 text-sm px-4 py-2.5
                       focus:border-gray-400 dark:focus:border-gray-500 focus:ring-1 focus:ring-gray-200
                       transition-colors cursor-pointer" />
        </div>

        <div class="hidden sm:flex items-center pb-2.5 text-gray-300 dark:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
        </div>

        <div class="flex-1 w-full">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Sampai Periode</label>
            <input type="month" wire:model.live="periodeAkhir"
                min="{{ now()->subYears(5)->format('Y-m') }}"
                max="{{ now()->addYear()->format('Y-m') }}"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700
                       dark:bg-gray-800 dark:text-gray-200 text-sm px-4 py-2.5
                       focus:border-gray-400 dark:focus:border-gray-500 focus:ring-1 focus:ring-gray-200
                       transition-colors cursor-pointer" />
        </div>

        <div class="flex-shrink-0 pb-0.5">
            @if(!$this->periodeValid())
                <div class="flex items-center gap-2 text-red-400 bg-red-50 dark:bg-red-950/30
                            border border-red-100 dark:border-red-900/50 rounded-lg px-4 py-2.5 text-xs">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    "Dari" tidak boleh lebih akhir dari "Sampai"
                </div>
            @else
                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800
                            border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2.5 text-xs">
                    <svg class="w-4 h-4 flex-shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span><strong class="text-gray-700 dark:text-gray-300">{{ $this->jumlahPeriode() }}</strong> bulan ditampilkan</span>
                </div>
            @endif
        </div>
    </div>

    <p class="mt-4 text-xs text-gray-400 dark:text-gray-600">
        Maksimal 12 bulan. Jika rentang melebihi 12 bulan, sistem otomatis membatasi sampai bulan ke-12.
    </p>
</div>

{{-- ══════════════════════════════════════════════════════════
     TABEL
══════════════════════════════════════════════════════════ --}}
@if(!$this->periodeValid())
    <div class="text-center py-16 text-gray-400 dark:text-gray-600">
        <p class="text-sm">Perbaiki periode filter terlebih dahulu.</p>
    </div>

@elseif($sudahFilter && count($laporanData) > 0)
@php
    $r    = $ringkasanPerBulan;
    $buls = $bulanList;
    $pKey = fn(array $p): string => $p['tahun'] . '-' . str_pad($p['bulan'], 2, '0', STR_PAD_LEFT);

    $lastPendapatanIdx = null;
    $lastReturIdx      = null;
    $lastHppIdx        = null;
    $lastBebanIdx      = null;
    $lastLainIdx       = null;

    foreach ($laporanData as $idx => $section) {
        $tipe = $section['tipe'] ?? '';
        if ($tipe === 'pendapatan')                              $lastPendapatanIdx = $idx;
        if ($tipe === 'retur_potongan')                          $lastReturIdx      = $idx;
        if (in_array($tipe, ['hpp', 'beban_produksi']))          $lastHppIdx        = $idx;
        if ($tipe === 'beban_usaha')                             $lastBebanIdx      = $idx;
        if (in_array($tipe, ['pendapatan_lain', 'beban_lain']))  $lastLainIdx       = $idx;
    }
    if ($lastReturIdx === null) $lastReturIdx = $lastPendapatanIdx;
@endphp

<div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden"
     x-data="{ allOpen: false }"
     @laba-rugi-telur-expand.window="allOpen = true; $el.querySelectorAll('[data-collapse]').forEach(el => el.style.display = '')"
     @laba-rugi-telur-collapse.window="allOpen = false; $el.querySelectorAll('[data-collapse]').forEach(el => el.style.display = 'none')">

    {{-- Card header --}}
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
        <div>
            <p class="text-[10px] text-gray-400 dark:text-gray-600 uppercase tracking-widest mb-0.5">INA TELUR</p>
            <h2 class="text-base font-semibold text-gray-700 dark:text-gray-200">Laporan Laba Rugi</h2>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="$dispatch('laba-rugi-telur-collapse')"
                class="px-3 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400
                       border border-gray-200 dark:border-gray-700 rounded-md
                       hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                Collapse All
            </button>
            <button type="button" @click="$dispatch('laba-rugi-telur-expand')"
                class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300
                       bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600
                       rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                Expand All
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse" style="min-width: {{ 300 + count($buls) * 280 }}px">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider w-28">Kode</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Nama Akun</th>
                    @foreach($buls as $periode)
                        <th class="px-4 py-3 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-l border-gray-200 dark:border-gray-700"
                            colspan="2">
                            {{ $this->getNamaBulan($periode['bulan']) }} {{ $periode['tahun'] }}
                        </th>
                    @endforeach
                </tr>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/40">
                    <th colspan="2"></th>
                    @foreach($buls as $periode)
                        <th class="px-4 py-1 text-right text-[9px] font-medium text-gray-400 dark:text-gray-600 uppercase tracking-wider min-w-[120px]">
                            Rincian
                        </th>
                        <th class="px-4 py-1 text-right text-[9px] font-medium text-gray-400 dark:text-gray-600 uppercase tracking-wider min-w-[140px] border-l border-gray-200 dark:border-gray-700">
                            Jumlah
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/80">

                @foreach($laporanData as $idx => $section)
                    @include('filament.pages.partials.laba-rugi-telur-node', [
                        'node'  => $section,
                        'depth' => 0,
                        'buls'  => $buls,
                        'pKey'  => $pKey,
                    ])

                    @if($idx === $lastPendapatanIdx)
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Pendapatan Bruto',
                            'key'   => 'total_pendapatan',
                            'style' => 'pendapatan_bruto',
                            'rumus' => 'Total semua akun pendapatan',
                            'buls'  => $buls, 'r' => $r, 'pKey' => $pKey,
                        ])
                    @endif
                    @if($idx === $lastReturIdx)
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Penjualan Bersih',
                            'key'   => 'penjualan_bersih',
                            'style' => 'penjualan_bersih',
                            'rumus' => 'Pendapatan Bruto − Retur & Potongan',
                            'buls'  => $buls, 'r' => $r, 'pKey' => $pKey,
                        ])
                    @endif
                    @if($idx === $lastHppIdx)
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Total HPP & Biaya Produksi',
                            'key'   => 'total_hpp',
                            'style' => 'total_hpp',
                            'rumus' => 'HPP + Biaya Produksi',
                            'buls'  => $buls, 'r' => $r, 'pKey' => $pKey,
                        ])
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Laba Kotor',
                            'key'   => 'laba_kotor',
                            'style' => 'laba_kotor',
                            'rumus' => 'Penjualan Bersih − Total HPP',
                            'buls'  => $buls, 'r' => $r, 'pKey' => $pKey,
                        ])
                    @endif
                    @if($idx === $lastBebanIdx)
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Total Beban Usaha',
                            'key'   => 'total_beban_usaha',
                            'style' => 'total_beban',
                            'rumus' => 'Total semua akun beban usaha',
                            'buls'  => $buls, 'r' => $r, 'pKey' => $pKey,
                        ])
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Laba (Rugi) Usaha',
                            'key'   => 'laba_usaha',
                            'style' => 'laba_usaha',
                            'rumus' => 'Laba Kotor − Total Beban Usaha',
                            'buls'  => $buls, 'r' => $r, 'pKey' => $pKey,
                        ])
                    @endif
                    @if($idx === $lastLainIdx)
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Laba (Rugi) Sebelum Pajak',
                            'key'   => 'laba_sebelum_pajak',
                            'style' => 'laba_sebelum_pajak',
                            'rumus' => 'Laba Usaha + Pendapatan Lain − Beban Lain',
                            'buls'  => $buls, 'r' => $r, 'pKey' => $pKey,
                        ])
                    @endif
                @endforeach

            </tbody>
        </table>
    </div>
</div>

@elseif($sudahFilter)
<div class="text-center py-20 text-gray-400 dark:text-gray-600">
    <p class="text-sm">Tidak ada data. Pastikan AkunGroup "Laba Rugi" sudah dibuat.</p>
</div>
@endif

</x-filament-panels::page>