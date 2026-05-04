<x-filament-panels::page wire:init="initLoad">

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');

:root {
    --bb-bg:            #f0f4f0;
    --bb-surface:       #ffffff;
    --bb-surface-2:     #f7faf7;
    --bb-surface-3:     #edf3ed;
    --bb-border:        #c8ddc8;
    --bb-border-soft:   #deeade;
    --bb-text-1:        #1a2e1a;
    --bb-text-2:        #3d5c3d;
    --bb-text-3:        #7a9a7a;
    --bb-accent:        #2d6a4f;
    --bb-accent-soft:   #d8f3dc;
    --bb-accent-mid:    #95d5b2;
    --bb-accent-text:   #1b4332;
    --bb-amber:         #9c6000;
    --bb-amber-bg:      #fff8e6;
    --bb-amber-border:  #ffd166;
    --bb-debit:         #1a6b3c;
    --bb-kredit:        #b5303a;
    --bb-neg:           #b5303a;
    --bb-shadow-sm: 0 1px 4px rgba(26,46,26,.07);
    --bb-shadow-md: 0 4px 16px rgba(26,46,26,.09);
    --bb-r-sm: 8px; --bb-r-md: 12px; --bb-r-lg: 18px;
}

.dark {
    --bb-bg:            #000000;
    --bb-surface:       #0d0d0d;
    --bb-surface-2:     #141414;
    --bb-surface-3:     #1c1c1c;
    --bb-border:        #2b2b2b;
    --bb-border-soft:   #222222;
    --bb-text-1:        #e8f5e8;
    --bb-text-2:        #a8c8a8;
    --bb-text-3:        #527052;
    --bb-accent:        #74c69d;
    --bb-accent-soft:   #0a1f12;
    --bb-accent-mid:    #2d6a4f;
    --bb-accent-text:   #95d5b2;
    --bb-amber:         #ffb703;
    --bb-amber-bg:      #110900;
    --bb-amber-border:  #7a4f00;
    --bb-debit:         #74c69d;
    --bb-kredit:        #f47478;
    --bb-neg:           #f47478;
    --bb-shadow-sm: 0 1px 4px rgba(0,0,0,.45);
    --bb-shadow-md: 0 4px 16px rgba(0,0,0,.55);
}

.bb { font-family:'Plus Jakarta Sans',sans-serif; font-weight:500; color:var(--bb-text-1); }
.bb-mono { font-family:'JetBrains Mono',monospace; }
@keyframes bb-spin { to { transform:rotate(360deg); } }
@keyframes bb-fade { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }

.bb-loading { display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:60vh; gap:1.5rem; }
.bb-loader-ring { position:relative; width:60px; height:60px; }
.bb-loader-ring::before,
.bb-loader-ring::after { content:''; position:absolute; border-radius:50%; border:3px solid transparent; }
.bb-loader-ring::before { inset:0; border-top-color:var(--bb-accent); animation:bb-spin 1s linear infinite; }
.bb-loader-ring::after  { inset:10px; border-top-color:var(--bb-amber); animation:bb-spin .65s linear infinite reverse; }

.bb-topbar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.875rem; padding:1rem 1.5rem; background:var(--bb-surface); border:1px solid var(--bb-border); border-radius:var(--bb-r-lg); box-shadow:var(--bb-shadow-sm); }
.bb-topbar-icon { width:40px; height:40px; background:var(--bb-accent-soft); border:1.5px solid var(--bb-accent-mid); border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.bb-topbar-icon svg { width:20px; height:20px; color:var(--bb-accent); stroke-width:1.8; }
.bb-topbar-title { font-size:1.1rem; font-weight:800; color:var(--bb-text-1); letter-spacing:-.01em; }
.bb-topbar-sub { font-size:.72rem; font-weight:600; color:var(--bb-text-3); margin-top:1px; }
.bb-period-wrap { display:flex; align-items:center; gap:.6rem; padding:.45rem .9rem; background:var(--bb-surface-2); border:1.5px solid var(--bb-border); border-radius:var(--bb-r-md); transition:border-color .2s; }
.bb-period-wrap:focus-within { border-color:var(--bb-accent); }
.bb-period-lbl { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:var(--bb-text-3); white-space:nowrap; }
.bb-period-input { font-family:'JetBrains Mono',monospace; font-size:.82rem; font-weight:500; color:var(--bb-text-1); background:transparent; border:none; outline:none; cursor:pointer; min-width:120px; }

.bb-induk { background:var(--bb-surface); border:1px solid var(--bb-border); border-radius:var(--bb-r-lg); overflow:hidden; box-shadow:var(--bb-shadow-sm); animation:bb-fade .3s ease both; }
.bb-induk-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; padding:1rem 1.5rem; cursor:pointer; user-select:none; background:linear-gradient(to right,var(--bb-accent-soft),var(--bb-surface)); border-bottom:1.5px solid var(--bb-border); transition:background .2s; }
.bb-induk-head:hover { background:var(--bb-accent-soft); }
.dark .bb-induk-head { background:linear-gradient(to right,var(--bb-accent-soft),transparent); }
.bb-induk-badge { font-family:'JetBrains Mono',monospace; font-size:.7rem; font-weight:600; color:var(--bb-accent-text); background:var(--bb-accent-soft); border:1.5px solid var(--bb-accent-mid); padding:3px 10px; border-radius:20px; }
.bb-induk-name { font-size:1rem; font-weight:700; color:var(--bb-text-1); margin-left:.5rem; }
.bb-induk-saldo-lbl { font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:var(--bb-text-3); margin-bottom:1px; }
.bb-induk-saldo-val { font-family:'JetBrains Mono',monospace; font-size:1rem; font-weight:700; color:var(--bb-accent); }
.bb-induk-saldo-val.neg { color:var(--bb-neg); }
.bb-chevron { width:28px; height:28px; border-radius:50%; background:var(--bb-surface-3); border:1px solid var(--bb-border); display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-left:.75rem; }
.bb-chevron svg { width:14px; height:14px; color:var(--bb-text-3); transition:transform .3s cubic-bezier(.4,0,.2,1); }
.bb-induk-body { padding:1rem 1rem .75rem; background:var(--bb-surface-2); display:flex; flex-direction:column; gap:.625rem; }

.bb-induk:nth-child(1){animation-delay:.04s}
.bb-induk:nth-child(2){animation-delay:.08s}
.bb-induk:nth-child(3){animation-delay:.12s}
.bb-induk:nth-child(4){animation-delay:.16s}
.bb-induk:nth-child(5){animation-delay:.20s}
</style>

<div class="bb space-y-4">

    @if($isLoading)
    <div class="bb-loading">
        <div class="bb-loader-ring"></div>
        <div style="text-align:center">
            <div style="font-size:1rem;font-weight:700;color:var(--bb-text-1)">Memuat Buku Besar…</div>
            <div style="font-size:.76rem;color:var(--bb-text-3);margin-top:4px;font-weight:600">Menghitung saldo secara rekursif</div>
        </div>
    </div>

    @else

    {{-- TOPBAR --}}
    <div class="bb-topbar">
        <div style="display:flex;align-items:center;gap:.75rem">
            <div class="bb-topbar-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                </svg>
            </div>
            <div>
                <div class="bb-topbar-title">Buku Besar</div>
                <div class="bb-topbar-sub">
                    {{ \Carbon\Carbon::parse($filterBulan)->locale('id')->isoFormat('MMMM YYYY') }}
                    &nbsp;·&nbsp; {{ $indukAkuns->count() }} Kelompok Akun
                </div>
            </div>
        </div>
        <div class="bb-period-wrap">
            <span class="bb-period-lbl">Periode</span>
            <svg style="width:13px;height:13px;color:var(--bb-text-3);flex-shrink:0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <input type="month" wire:model.live="filterBulan" class="bb-period-input">
        </div>
    </div>

    {{-- INDUK LIST --}}
    @forelse($indukAkuns as $induk)
    @php
        $totalInduk = $induk->anakAkuns
            ->whereNull('parent')
            ->sum(fn($a) => $this->getTotalRecursive($a));

        // Tampilkan jika ada mutasi bulan ini di akun manapun dalam grup
        $adaMutasiInduk = collect(array_keys($saldoMap))->contains(function ($kode) use ($induk) {
            return $induk->anakAkuns->whereNull('parent')->contains(function ($anak) use ($kode) {
                if (($anak->kode_anak_akun ?? null) === $kode) return true;
                foreach (($anak->subAnakAkuns ?? collect()) as $sub) {
                    if ($sub->kode_sub_anak_akun === $kode) return true;
                }
                foreach (($anak->children ?? collect()) as $child) {
                    if (($child->kode_anak_akun ?? null) === $kode) return true;
                    foreach (($child->subAnakAkuns ?? collect()) as $sub) {
                        if ($sub->kode_sub_anak_akun === $kode) return true;
                    }
                }
                return false;
            });
        });
    @endphp

    @if($adaMutasiInduk || $totalInduk != 0)
    <div x-data="{ open: true }" class="bb-induk">

        <div class="bb-induk-head" @click="open = !open">
            <div style="display:flex;align-items:center">
                <span class="bb-induk-badge">{{ $induk->kode_induk_akun }}</span>
                <span class="bb-induk-name">{{ $induk->nama_induk_akun }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:.75rem">
                <div style="text-align:right">
                    <div class="bb-induk-saldo-lbl">Total Saldo</div>
                    <div class="bb-induk-saldo-val {{ $totalInduk < 0 ? 'neg' : '' }}">
                        Rp {{ number_format(abs($totalInduk), 0, ',', '.') }}
                        @if($totalInduk < 0)<span style="font-size:.62rem">&nbsp;(–)</span>@endif
                    </div>
                </div>
                <div class="bb-chevron">
                    <svg :style="open ? 'transform:rotate(180deg)' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
        </div>

        <div x-show="open" x-collapse class="bb-induk-body">
            @foreach($induk->anakAkuns->whereNull('parent') as $anak)
                @include('filament.pages.partials.buku-besar-anak', ['akun' => $anak, 'depth' => 0])
            @endforeach
        </div>

    </div>
    @endif

    @empty
    <div style="padding:3rem;text-align:center;color:var(--bb-text-3);font-weight:700">
        Tidak ada data untuk periode ini
    </div>
    @endforelse

    @endif
</div>

</x-filament-panels::page>                                  