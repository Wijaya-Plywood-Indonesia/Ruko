@php
$saldoNormal = strtolower($saldoNormal ?? 'debit');
$isKredit    = in_array($saldoNormal, ['kredit', 'credit', 'k']);
$running     = (float) $saldoAwal;
$totalDebit  = 0.0;
$totalKredit = 0.0;
$totalQty    = 0.0;

$rows = $transaksis->map(function ($trx) use (&$running, &$totalDebit, &$totalKredit, &$totalQty, $isKredit) {
    $nominal = (float) ($trx->banyak ?? 1) * (float) ($trx->harga ?? 0);
    $isDebit = in_array(strtolower($trx->map), ['d', 'debit']);
    $qty     = (float) ($trx->banyak ?? 0);

    if ($isKredit) {
        $running += $isDebit ? -$nominal : $nominal;
    } else {
        $running += $isDebit ? $nominal : -$nominal;
    }

    if ($isDebit) {
        $totalDebit += $nominal;
        // Hanya hitung qty jika ada banyak (bukan null dan bukan 1 default)
        if ($trx->banyak !== null && $qty > 0) {
            $totalQty += $qty;
        }
    } else {
        $totalKredit += $nominal;
        if ($trx->banyak !== null && $qty > 0) {
            $totalQty -= $qty;
        }
    }

    return (object) [
        'trx'     => $trx,
        'nominal' => $nominal,
        'isDebit' => $isDebit,
        'running' => $running,
    ];
});

$saldoAkhir = $running;
$saldoClass = $saldoAkhir < 0 ? 'lgt-neg' : '';
@endphp

<style>
.lgt-wrap { overflow-x:auto; }
.lgt { width:100%; border-collapse:collapse; font-size:.75rem; }
.lgt th {
    padding:.45rem .75rem;
    text-align:left;
    font-size:.6rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.09em;
    color:var(--bb-text-3);
    background:var(--bb-surface-2);
    border-bottom:1px solid var(--bb-border-soft);
    white-space:nowrap;
}
.lgt th.r, .lgt td.r { text-align:right; }
.lgt td {
    padding:.4rem .75rem;
    border-bottom:1px solid var(--bb-border-soft);
    color:var(--bb-text-2);
    vertical-align:middle;
    white-space:nowrap;
}
.lgt tr:last-child td { border-bottom:none; }
.lgt tr:hover td { background:var(--bb-surface-3); }

.lgt-sa td { background:var(--bb-accent-soft) !important; color:var(--bb-accent-text) !important; font-weight:700; }
.dark .lgt-sa td { background:var(--bb-accent-soft) !important; color:var(--bb-accent-text) !important; }

.lgt-tgl    { width:80px; font-size:.68rem; color:var(--bb-text-3); }
.lgt-jurnal { width:50px; font-family:'JetBrains Mono',monospace; font-size:.68rem; color:var(--bb-text-3); text-align:center; }
.lgt-nama   { max-width:140px; overflow:hidden; text-overflow:ellipsis; font-size:.73rem; color:var(--bb-text-2); }
.lgt-ket    { max-width:200px; overflow:hidden; text-overflow:ellipsis; font-size:.7rem; color:var(--bb-text-3); }
.lgt-qty    { width:60px; font-family:'JetBrains Mono',monospace; font-size:.7rem; color:var(--bb-text-3); text-align:right; }
.lgt-harga  { width:100px; font-family:'JetBrains Mono',monospace; font-size:.7rem; color:var(--bb-text-3); text-align:right; }
.lgt-debit  { width:110px; font-family:'JetBrains Mono',monospace; font-weight:600; color:var(--bb-debit); text-align:right; }
.lgt-kredit { width:110px; font-family:'JetBrains Mono',monospace; font-weight:600; color:var(--bb-kredit); text-align:right; }
.lgt-saldo  { width:120px; font-family:'JetBrains Mono',monospace; font-weight:700; color:var(--bb-text-1); text-align:right; }
.lgt-neg    { color:var(--bb-neg) !important; }

.lgt-foot td {
    padding:.5rem .75rem;
    background:var(--bb-surface-3);
    border-top:1.5px solid var(--bb-border);
    font-family:'JetBrains Mono',monospace;
    font-size:.72rem;
    font-weight:700;
    color:var(--bb-text-1);
}
.lgt-foot-lbl {
    text-align:right;
    font-size:.6rem;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:var(--bb-text-3);
    font-family:'Plus Jakarta Sans',sans-serif;
}
</style>

<div class="lgt-wrap">
<table class="lgt">
    <thead>
        <tr>
            <th class="lgt-tgl">Tanggal</th>
            <th class="lgt-jurnal r">No.J</th>
            <th class="lgt-nama">Nama</th>
            <th class="lgt-ket">Keterangan</th>
            <th class="lgt-qty r">Qty</th>
            <th class="lgt-harga r">Harga</th>
            <th class="lgt-debit r" style="color:var(--bb-debit)">Debit</th>
            <th class="lgt-kredit r" style="color:var(--bb-kredit)">Kredit</th>
            <th class="lgt-saldo r">Saldo</th>
        </tr>
    </thead>
    <tbody>

        {{-- Baris saldo awal --}}
        <tr class="lgt-sa">
            <td class="lgt-tgl" colspan="4"
                style="font-size:.65rem;letter-spacing:.06em;text-transform:uppercase">
                Saldo Awal Periode
            </td>
            <td class="lgt-qty r">—</td>
            <td class="lgt-harga r">—</td>
            <td class="lgt-debit r">—</td>
            <td class="lgt-kredit r">—</td>
            <td class="lgt-saldo r {{ $saldoAwal < 0 ? 'lgt-neg' : '' }}">
                @if($saldoAwal < 0)–@endif
                {{ number_format(abs($saldoAwal), 0, ',', '.') }}
            </td>
        </tr>

        {{-- Baris transaksi --}}
        @forelse($rows as $row)
        <tr>
            <td class="lgt-tgl">
                {{ \Carbon\Carbon::parse($row->trx->tgl)->format('d/m/Y') }}
            </td>
            <td class="lgt-jurnal r">{{ $row->trx->jurnal }}</td>
            <td class="lgt-nama" title="{{ $row->trx->nama }}">
                {{ $row->trx->nama ?? '—' }}
            </td>
            <td class="lgt-ket" title="{{ $row->trx->keterangan }}">
                {{ $row->trx->keterangan ?? '—' }}
            </td>
            <td class="lgt-qty r">
                @if($row->trx->banyak !== null && (float)$row->trx->banyak > 0)
                    {{ (float)$row->trx->banyak == (int)$row->trx->banyak ? number_format((float)$row->trx->banyak, 0, ',', '.') : rtrim(rtrim(number_format((float)$row->trx->banyak, 4, ',', '.'), '0'), ',') }}
                @else
                    —
                @endif
            </td>
            <td class="lgt-harga r">
                @if($row->trx->harga !== null && (float)$row->trx->harga > 0)
                    {{ number_format((float)$row->trx->harga, 0, ',', '.') }}
                @else
                    —
                @endif
            </td>
            <td class="lgt-debit r"
                style="{{ $row->isDebit ? 'color:var(--bb-debit);font-weight:700' : 'opacity:.2' }}">
                @if($row->isDebit)
                    {{ number_format($row->nominal, 0, ',', '.') }}
                @else —
                @endif
            </td>
            <td class="lgt-kredit r"
                style="{{ !$row->isDebit ? 'color:var(--bb-kredit);font-weight:700' : 'opacity:.2' }}">
                @if(!$row->isDebit)
                    {{ number_format($row->nominal, 0, ',', '.') }}
                @else —
                @endif
            </td>
            <td class="lgt-saldo r {{ $row->running < 0 ? 'lgt-neg' : '' }}">
                @if($row->running < 0)–@endif
                {{ number_format(abs($row->running), 0, ',', '.') }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9"
                style="padding:.75rem;text-align:center;color:var(--bb-text-3);font-size:.7rem;font-style:italic">
                Tidak ada mutasi bulan ini
            </td>
        </tr>
        @endforelse

    </tbody>
    <tfoot>
        <tr class="lgt-foot">
            <td colspan="4" class="lgt-foot-lbl">Total Mutasi Bulan Ini</td>
            <td class="lgt-qty r">
                @if($totalQty != 0)
                    <span class="{{ $totalQty < 0 ? 'lgt-neg' : '' }}">
                        {{ (float)$totalQty == (int)$totalQty ? number_format(abs($totalQty), 0, ',', '.') : rtrim(rtrim(number_format(abs($totalQty), 4, ',', '.'), '0'), ',') }}
                    </span>
                @else
                    —
                @endif
            </td>
            <td class="lgt-harga r"></td>
            <td class="lgt-debit r" style="color:var(--bb-debit)">
                {{ number_format($totalDebit, 0, ',', '.') }}
            </td>
            <td class="lgt-kredit r" style="color:var(--bb-kredit)">
                {{ number_format($totalKredit, 0, ',', '.') }}
            </td>
            <td class="lgt-saldo r {{ $saldoAkhir < 0 ? 'lgt-neg' : '' }}">
                @if($saldoAkhir < 0)–@endif
                {{ number_format(abs($saldoAkhir), 0, ',', '.') }}
            </td>
        </tr>
    </tfoot>
</table>
</div>