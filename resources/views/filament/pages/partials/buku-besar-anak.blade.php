@php
$kodeAkun   = $akun->kode_anak_akun ?? $akun->kode_sub_anak_akun;
$namaAkun   = $akun->nama_anak_akun ?? $akun->nama_sub_anak_akun;
$saldoAwal  = $this->getSaldoAwal($kodeAkun);
$saldoAkhir = $this->getTotalRecursive($akun);
$transaksis = $this->getTransaksiByKode($kodeAkun);
$jumlahTrx  = $transaksis->count();
$depth      = $depth ?? 0;

$children = collect();
if (isset($akun->children))     $children = $children->merge($akun->children);
if (isset($akun->subAnakAkuns)) $children = $children->merge($akun->subAnakAkuns);

// Tampilkan jika: ada transaksi ATAU ada saldo awal ATAU ada children yang punya data
$tampilkan = ($jumlahTrx > 0) || ($saldoAwal != 0) || ($saldoAkhir != 0) || $children->count() > 0;

$saldoClass = $saldoAkhir < 0 ? 'neg' : '';
@endphp

@if($tampilkan)

<style>
.bb-anak { background:var(--bb-surface); border:1px solid var(--bb-border-soft); border-radius:var(--bb-r-md); overflow:hidden; box-shadow:var(--bb-shadow-sm); }
.bb-anak-head { display:flex; align-items:center; justify-content:space-between; padding:.6rem 1rem; background:var(--bb-surface-3); border-bottom:1px solid var(--bb-border-soft); }
.bb-anak-left { display:flex; align-items:center; gap:.5rem; }
.bb-anak-dot { width:7px; height:7px; border-radius:50%; background:var(--bb-accent-mid); flex-shrink:0; }
.bb-anak-code { font-family:'JetBrains Mono',monospace; font-size:.68rem; font-weight:500; color:var(--bb-text-3); background:var(--bb-surface-2); border:1px solid var(--bb-border); padding:2px 7px; border-radius:5px; }
.bb-anak-name { font-size:.82rem; font-weight:700; color:var(--bb-text-1); }
.bb-anak-saldo { font-family:'JetBrains Mono',monospace; font-size:.82rem; font-weight:600; color:var(--bb-text-2); }
.bb-anak-saldo.neg { color:var(--bb-neg); }
.bb-sub-wrap { padding:.5rem 0 .5rem 1.25rem; border-left:2.5px solid var(--bb-border); margin:.5rem .75rem; display:flex; flex-direction:column; gap:.5rem; }
</style>

<div class="bb-anak">

    {{-- Header akun --}}
    <div class="bb-anak-head">
        <div class="bb-anak-left">
            <span class="bb-anak-dot" @if($depth > 0) style="background:var(--bb-amber-border)" @endif></span>
            <span class="bb-anak-code">{{ $kodeAkun }}</span>
            <span class="bb-anak-name">{{ $namaAkun }}</span>
        </div>
        <span class="bb-anak-saldo {{ $saldoClass }}">
            @if($saldoAkhir < 0)–@endif
            Rp {{ number_format(abs($saldoAkhir), 0, ',', '.') }}
        </span>
    </div>

    {{-- Children rekursif --}}
    @if($children->count())
    <div class="bb-sub-wrap">
        @foreach($children as $child)
            @include('filament.pages.partials.buku-besar-anak', ['akun' => $child, 'depth' => $depth + 1])
        @endforeach
    </div>
    @endif

    {{-- Ledger table --}}
    @if($jumlahTrx > 0 || $saldoAwal != 0)
        @include('filament.pages.partials.ledger-table', [
            'transaksis'  => $transaksis,
            'saldoAwal'   => $saldoAwal,
            'saldoNormal' => strtolower($akun->saldo_normal ?? 'debit'),
        ])
    @endif

</div>

@endif