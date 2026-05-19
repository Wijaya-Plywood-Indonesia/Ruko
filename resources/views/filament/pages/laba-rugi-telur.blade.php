<x-filament-panels::page>

{{-- ══════════════════════════════════════════════════════════
     FILTER DINAMIS HARIAN / BULANAN
══════════════════════════════════════════════════════════ --}}
<div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 mb-6">

    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-4 mb-5">
        <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
            Filter Periode Laba Rugi
        </h3>
        
        {{-- Tombol Toggle Harian & Bulanan --}}
        <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl">
            <button type="button" wire:click="ubahJenisFilter('bulan')"
                class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all {{ $jenisFilter === 'bulan' ? 'bg-white dark:bg-gray-700 shadow-sm text-emerald-600 dark:text-white' : 'text-gray-500' }}">
                Bulanan
            </button>
            <button type="button" wire:click="ubahJenisFilter('hari')"
                class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all {{ $jenisFilter === 'hari' ? 'bg-white dark:bg-gray-700 shadow-sm text-emerald-600 dark:text-white' : 'text-gray-500' }}">
                Harian
            </button>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row items-start sm:items-end gap-6">

        <div class="flex-1 w-full">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">
                Dari {{ ucfirst($jenisFilter) }}
            </label>
            <input type="{{ $jenisFilter === 'hari' ? 'date' : 'month' }}" wire:model.live="periodeAwal"
                min="{{ $jenisFilter === 'hari' ? now()->subYears(5)->format('Y-m-d') : now()->subYears(5)->format('Y-m') }}"
                max="{{ $jenisFilter === 'hari' ? now()->addYear()->format('Y-m-d') : now()->addYear()->format('Y-m') }}"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700
                       dark:bg-gray-800 dark:text-gray-200 text-sm px-4 py-2.5
                       focus:border-emerald-500 dark:focus:border-emerald-500 focus:ring-1 focus:ring-emerald-200
                       transition-colors cursor-pointer" />
        </div>

        <div class="hidden sm:flex items-center pb-2.5 text-gray-300 dark:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
        </div>

        <div class="flex-1 w-full">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">
                Sampai {{ ucfirst($jenisFilter) }}
            </label>
            <input type="{{ $jenisFilter === 'hari' ? 'date' : 'month' }}" wire:model.live="periodeAkhir"
                min="{{ $jenisFilter === 'hari' ? now()->subYears(5)->format('Y-m-d') : now()->subYears(5)->format('Y-m') }}"
                max="{{ $jenisFilter === 'hari' ? now()->addYear()->format('Y-m-d') : now()->addYear()->format('Y-m') }}"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700
                       dark:bg-gray-800 dark:text-gray-200 text-sm px-4 py-2.5
                       focus:border-emerald-500 dark:focus:border-emerald-500 focus:ring-1 focus:ring-emerald-200
                       transition-colors cursor-pointer" />
        </div>

        <div class="flex-shrink-0 pb-0.5">
            @if(!$this->periodeValid())
                <div class="flex items-center gap-2 text-red-400 bg-red-50 dark:bg-red-950/30
                            border border-red-100 dark:border-red-900/50 rounded-lg px-4 py-2.5 text-xs">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    "Dari" tidak boleh lebih akhir dari "Sampai"
                </div>
            @else
                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800
                            border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2.5 text-xs">
                    <svg class="w-4 h-4 flex-shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>
                        <strong class="text-gray-700 dark:text-gray-300">{{ $this->jumlahPeriode() }}</strong>
                        data {{ $jenisFilter }}an ditampilkan
                    </span>
                </div>
            @endif
        </div>
    </div>

    <p class="mt-4 text-xs text-gray-400 dark:text-gray-600">
        Maksimal 12 bulan (mode Bulanan) dan 31 hari berturut-turut (mode Harian).
    </p>
</div>

{{-- ══════════════════════════════════════════════════════════
     TABEL KONTEN
══════════════════════════════════════════════════════════ --}}
@if(!$this->periodeValid())
    <div class="text-center py-16 text-gray-400 dark:text-gray-600">
        <p class="text-sm">Perbaiki periode filter terlebih dahulu.</p>
    </div>

@elseif($sudahFilter && count($laporanData) > 0)
@php
    $r    = $ringkasanPerBulan;
    $buls = $bulanList;
    $pKey = fn(array $p): string => $p['date_string'] ?? ($p['tahun'] . '-' . str_pad($p['bulan'], 2, '0', STR_PAD_LEFT));

    $lastPendapatanIdx = null;
    $lastReturIdx      = null;
    $lastHppIdx        = null;
    $lastBebanIdx      = null;
    $lastLainIdx       = null;

    foreach ($laporanData as $idx => $section) {
        $tipe = $section['tipe'] ?? '';
        if ($tipe === 'pendapatan')                             $lastPendapatanIdx = $idx;
        if ($tipe === 'retur_potongan')                         $lastReturIdx      = $idx;
        if (in_array($tipe, ['hpp', 'beban_produksi']))         $lastHppIdx        = $idx;
        if ($tipe === 'beban_usaha')                            $lastBebanIdx      = $idx;
        if (in_array($tipe, ['pendapatan_lain', 'beban_lain'])) $lastLainIdx       = $idx;
    }
    if ($lastReturIdx === null) $lastReturIdx = $lastPendapatanIdx;

    $hasNilai = function(array $node, array $buls, callable $pKey) use (&$hasNilai): bool {
        foreach ($buls as $p) {
            $k = $pKey($p);
            if (($node['nilai_per_bulan'][$k] ?? 0) != 0) return true;
        }
        foreach ($node['children'] ?? [] as $child) {
            if ($hasNilai($child, $buls, $pKey)) return true;
        }
        return false;
    };
@endphp

<div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden"
     x-data="{ allOpen: false }"
     @laba-rugi-telur-expand.window="
         allOpen = true;
         $el.querySelectorAll('[data-collapse]').forEach(el => el.style.display = '')
     "
     @laba-rugi-telur-collapse.window="
         allOpen = false;
         $el.querySelectorAll('[data-collapse]').forEach(el => el.style.display = 'none')
     ">

    {{-- Card header --}}
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
        <div>
            <h2 class="text-base font-semibold text-gray-700 dark:text-gray-200">Laporan Laba Rugi</h2>
        </div>
        <div class="flex items-center gap-2">
 
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
                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    @endif
                </svg>
                {{ $tampilkanSaldoNol ? 'Sembunyikan Saldo Nol' : 'Tampilkan Saldo Nol' }}
            </button>
 
            {{-- Collapse / Expand --}}
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
 
            {{-- ★ TOMBOL EXPORT EXCEL ★ --}}
            <button
                wire:click="exportExcel"
                wire:loading.attr="disabled"
                wire:target="exportExcel"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                       bg-emerald-600 hover:bg-emerald-700 text-white border border-emerald-600
                       transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
 
                {{-- Spinner --}}
                <svg wire:loading wire:target="exportExcel"
                     class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
 
                {{-- Icon unduh --}}
                <svg wire:loading.remove wire:target="exportExcel"
                     class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
 
                <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                <span wire:loading wire:target="exportExcel">Mengunduh...</span>
            </button>
 
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse" style="min-width: {{ 300 + count($buls) * 280 }}px">
            <thead>
    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80">
        <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wider w-28">Kode</th>
        <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wider">Nama Akun</th>
        @foreach($buls as $periode)
            <th class="px-4 py-3 text-center text-[11px] font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wider border-l border-gray-200 dark:border-gray-700"
                colspan="3">
                {{-- HEADER DINAMIS DARI PROPERTY 'LABEL' --}}
                {{ $periode['label'] }}
            </th>
        @endforeach
    </tr>
    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/40">
        <th colspan="2"></th>
        @foreach($buls as $periode)
            <th class="px-3 py-1 text-right text-[9px] font-medium text-gray-400 dark:text-gray-600 uppercase tracking-wider min-w-[70px]">
                Qty
            </th>
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

                    @if(!$tampilkanSaldoNol && !$hasNilai($section, $buls, $pKey))
                        {{-- skip section saldo nol --}}
                    @else
                        @include('filament.pages.partials.laba-rugi-telur-node', [
                            'node'              => $section,
                            'depth'             => 0,
                            'buls'              => $buls,
                            'pKey'              => $pKey,
                            'tampilkanSaldoNol' => $tampilkanSaldoNol,
                            'hasNilai'          => $hasNilai,
                        ])
                    @endif

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

                    @if($idx === count($laporanData) - 1)
                        @include('filament.pages.partials.laba-rugi-telur-subtotal', [
                            'label' => 'Laba (Rugi) Bersih',
                            'key'   => 'laba_sebelum_pajak',
                            'style' => 'laba_sebelum_pajak',
                            'rumus' => 'Hasil akhir periode',
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