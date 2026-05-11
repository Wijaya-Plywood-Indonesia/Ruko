<x-filament-panels::page>

{{-- ══════════════════════════════════════════════════════════
     FILTER
══════════════════════════════════════════════════════════ --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-5">Filter Periode</h3>

    <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">

        {{-- Tahun --}}
        <div>
            <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Tahun</label>
            <select wire:model.live="tahun"
                class="rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200
                       px-4 py-3 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-colors">
                @foreach(range(now()->year, now()->year - 5) as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>

        {{-- Dari Bulan --}}
        <div>
            <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Dari Bulan</label>
            <select wire:model.live="bulan_dari"
                class="rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200
                       px-4 py-3 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-colors">
                @foreach([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $num => $nama)
                    <option value="{{ $num }}">{{ $nama }}</option>
                @endforeach
            </select>
        </div>

        <div class="hidden sm:flex pb-3 text-gray-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
        </div>

        {{-- Sampai Bulan --}}
        <div>
            <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Sampai Bulan</label>
            <select wire:model.live="bulan_sampai"
                class="rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200
                       px-4 py-3 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-colors">
                @foreach([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $num => $nama)
                    <option value="{{ $num }}">{{ $nama }}</option>
                @endforeach
            </select>
        </div>

        {{-- Info --}}
        <div class="pb-1">
            <div class="flex items-center gap-2 bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700
                        text-primary-700 dark:text-primary-400 rounded-xl px-4 py-3 text-sm font-medium">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>
                    <strong>{{ count($bulanList) }}</strong> bulan ditampilkan
                </span>
            </div>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     TABEL LABA RUGI
══════════════════════════════════════════════════════════ --}}
@if($sudahFilter && count($laporanData) > 0)
@php
    $r    = $ringkasanPerBulan;
    $buls = $bulanList;

    $lastPendapatanIdx = null;
    $lastReturIdx      = null;
    $lastHppIdx        = null;
    $lastBebanIdx      = null;
    $lastLainIdx       = null;

    foreach ($laporanData as $idx => $section) {
        $tipe = $section['tipe'] ?? '';
        if ($tipe === 'pendapatan')                              $lastPendapatanIdx = $idx;
        if (in_array($tipe, ['retur_potongan']))                 $lastReturIdx      = $idx;
        if (in_array($tipe, ['hpp', 'beban_produksi']))          $lastHppIdx        = $idx;
        if (in_array($tipe, ['beban_usaha']))                    $lastBebanIdx      = $idx;
        if (in_array($tipe, ['pendapatan_lain', 'beban_lain']))  $lastLainIdx       = $idx;
    }
    if ($lastReturIdx === null) $lastReturIdx = $lastPendapatanIdx;
@endphp

<div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden"
     x-data="{ allOpen: false }"
     @laba-rugi-telur-expand.window="allOpen = true; $el.querySelectorAll('[data-collapse]').forEach(el => el.style.display = '')"
     @laba-rugi-telur-collapse.window="allOpen = false; $el.querySelectorAll('[data-collapse]').forEach(el => el.style.display = 'none')">

    {{-- Header --}}
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
        <div>
            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5">INA TELUR</p>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Laporan Laba Rugi</h2>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="$dispatch('laba-rugi-telur-collapse')"
                class="px-3 py-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800
                       border border-gray-300 dark:border-gray-700 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Collapse All
            </button>
            <button type="button" @click="$dispatch('laba-rugi-telur-expand')"
                class="px-3 py-1.5 text-xs font-semibold text-white bg-gray-800 dark:bg-primary-600
                       rounded-lg shadow-sm hover:bg-gray-700 dark:hover:bg-primary-500 transition-colors">
                Expand All
            </button>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse" style="min-width: {{ 300 + count($buls) * 180 }}px">
            <thead>
                <tr class="bg-amber-400 dark:bg-amber-500">
                    <th class="px-4 py-3 text-left text-xs font-extrabold text-amber-950 uppercase tracking-widest w-32">
                        KODE
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-extrabold text-amber-950 uppercase tracking-widest">
                        NAMA AKUN
                    </th>
                    @foreach($buls as $bulan)
                        <th class="px-4 py-3 text-right text-xs font-extrabold text-amber-950 uppercase tracking-widest min-w-[180px]">
                            {{ $this->getNamaBulan($bulan) }} {{ $tahun }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">

                @foreach($laporanData as $idx => $section)
                    @include('filament.pages.partials.laba-rugi-telur-node', [
                        'node'  => $section,
                        'depth' => 0,
                        'buls'  => $buls,
                    ])

                    @if($idx === $lastPendapatanIdx)
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Pendapatan Bruto',
                            'key'   => 'total_pendapatan',
                            'style' => 'pendapatan_bruto',
                            'rumus' => 'Total semua akun pendapatan',
                            'buls'  => $buls, 'r' => $r,
                        ])
                    @endif

                    @if($idx === $lastReturIdx)
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Penjualan Bersih',
                            'key'   => 'penjualan_bersih',
                            'style' => 'penjualan_bersih',
                            'rumus' => 'Pendapatan Bruto − Retur & Potongan',
                            'buls'  => $buls, 'r' => $r,
                        ])
                    @endif

                    @if($idx === $lastHppIdx)
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Total HPP & Biaya Produksi',
                            'key'   => 'total_hpp',
                            'style' => 'total_hpp',
                            'rumus' => 'HPP + Biaya Produksi',
                            'buls'  => $buls, 'r' => $r,
                        ])
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Laba Kotor',
                            'key'   => 'laba_kotor',
                            'style' => 'laba_kotor',
                            'rumus' => 'Penjualan Bersih − Total HPP',
                            'buls'  => $buls, 'r' => $r,
                        ])
                    @endif

                    @if($idx === $lastBebanIdx)
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Total Beban Usaha',
                            'key'   => 'total_beban_usaha',
                            'style' => 'total_beban',
                            'rumus' => 'Total semua akun beban usaha',
                            'buls'  => $buls, 'r' => $r,
                        ])
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Laba (Rugi) Usaha',
                            'key'   => 'laba_usaha',
                            'style' => 'laba_usaha',
                            'rumus' => 'Laba Kotor − Total Beban Usaha',
                            'buls'  => $buls, 'r' => $r,
                        ])
                    @endif

                    @if($idx === $lastLainIdx)
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Laba (Rugi) Sebelum Pajak',
                            'key'   => 'laba_sebelum_pajak',
                            'style' => 'laba_sebelum_pajak',
                            'rumus' => 'Laba Usaha + Pendapatan Lain − Beban Lain',
                            'buls'  => $buls, 'r' => $r,
                        ])
                    @endif

                @endforeach

            </tbody>
        </table>
    </div>
</div>

@elseif($sudahFilter)
<div class="text-center py-20">
    <svg class="mx-auto w-14 h-14 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
    </svg>
    <p class="text-gray-500 dark:text-gray-400">Tidak ada data. Pastikan AkunGroup "Laba Rugi" sudah dibuat.</p>
</div>
@endif

</x-filament-panels::page>