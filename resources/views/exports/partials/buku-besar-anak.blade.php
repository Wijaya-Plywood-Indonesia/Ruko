@php
$kodeAkun   = $akun->kode_anak_akun ?? $akun->kode_sub_anak_akun;
$namaAkun   = $akun->nama_anak_akun ?? $akun->nama_sub_anak_akun;
$saldoAwal  = $export->getSaldoAwal($kodeAkun);
$saldoAkhir = $export->getTotalRecursive($akun);
$transaksis = $export->getTransaksiByKode($kodeAkun);
$jumlahTrx  = $transaksis->count();

$children = collect();
if (isset($akun->children))     $children = $children->merge($akun->children);
if (isset($akun->subAnakAkuns)) $children = $children->merge($akun->subAnakAkuns);

$tampilkan = ($jumlahTrx > 0) || ($saldoAwal != 0) || ($saldoAkhir != 0) || $children->count() > 0;

$padding = str_repeat('&nbsp;&nbsp;', $depth);
@endphp

@if($tampilkan)
    <tr>
        <td colspan="8" style="font-weight: bold;">
            {!! $padding !!} {{ $kodeAkun }} - {{ $namaAkun }}
        </td>
        <td style="font-weight: bold; text-align: right;">
            {{ $saldoAkhir }}
        </td>
    </tr>

    @if($children->count())
        @foreach($children as $child)
            @include('exports.partials.buku-besar-anak', ['akun' => $child, 'depth' => $depth + 1])
        @endforeach
    @endif

    @if($jumlahTrx > 0 || $saldoAwal != 0)
        <tr>
            <th style="background-color: #c6e0b4; font-weight: bold; font-size: 10px;">Tanggal</th>
            <th style="background-color: #c6e0b4; font-weight: bold; font-size: 10px;">No.J</th>
            <th style="background-color: #c6e0b4; font-weight: bold; font-size: 10px;">Nama</th>
            <th style="background-color: #c6e0b4; font-weight: bold; font-size: 10px;">Keterangan</th>
            <th style="background-color: #c6e0b4; font-weight: bold; font-size: 10px; text-align: right;">Qty</th>
            <th style="background-color: #c6e0b4; font-weight: bold; font-size: 10px; text-align: right;">Harga</th>
            <th style="background-color: #c6e0b4; font-weight: bold; font-size: 10px; text-align: right;">Debit</th>
            <th style="background-color: #c6e0b4; font-weight: bold; font-size: 10px; text-align: right;">Kredit</th>
            <th style="background-color: #c6e0b4; font-weight: bold; font-size: 10px; text-align: right;">Saldo</th>
        </tr>
        
        <tr>
            <td colspan="4" style="font-weight: bold; font-size: 10px;">Saldo Awal Periode</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td style="text-align: right; font-weight: bold; font-size: 10px;">{{ $saldoAwal }}</td>
        </tr>
        
        @php
            $saldoNormal = strtolower($akun->saldo_normal ?? 'debit');
            $isKredit    = in_array($saldoNormal, ['kredit', 'credit', 'k']);
            $running     = (float) $saldoAwal;
            $totalDebit  = 0.0;
            $totalKredit = 0.0;
            $totalQty    = 0.0;
        @endphp

        @foreach($transaksis as $trx)
            @php
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
                    if ($trx->banyak !== null && $qty > 0) $totalQty += $qty;
                } else {
                    $totalKredit += $nominal;
                    if ($trx->banyak !== null && $qty > 0) $totalQty -= $qty;
                }
            @endphp
            <tr>
                <td style="font-size: 10px;">{{ \Carbon\Carbon::parse($trx->tgl)->format('d/m/Y') }}</td>
                <td style="font-size: 10px;">{{ $trx->jurnal }}</td>
                <td style="font-size: 10px;">{{ $trx->nama }}</td>
                <td style="font-size: 10px;">{{ $trx->keterangan }}</td>
                <td style="text-align: right; font-size: 10px;">{{ $trx->banyak !== null && $qty > 0 ? $qty : '' }}</td>
                <td style="text-align: right; font-size: 10px;">{{ $trx->harga !== null && (float)$trx->harga > 0 ? (float)$trx->harga : '' }}</td>
                <td style="text-align: right; color: #1a6b3c; font-size: 10px;">{{ $isDebit ? $nominal : '' }}</td>
                <td style="text-align: right; color: #b5303a; font-size: 10px;">{{ !$isDebit ? $nominal : '' }}</td>
                <td style="text-align: right; font-size: 10px;">{{ $running }}</td>
            </tr>
        @endforeach
        
        <tr>
            <td colspan="4" style="font-weight: bold; text-align: right; font-size: 10px;">Total Mutasi Bulan Ini</td>
            <td style="font-weight: bold; text-align: right; font-size: 10px;">{{ $totalQty != 0 ? $totalQty : '' }}</td>
            <td></td>
            <td style="font-weight: bold; text-align: right; color: #1a6b3c; font-size: 10px;">{{ $totalDebit }}</td>
            <td style="font-weight: bold; text-align: right; color: #b5303a; font-size: 10px;">{{ $totalKredit }}</td>
            <td style="font-weight: bold; text-align: right; font-size: 10px;">{{ $running }}</td>
        </tr>
        <tr><td colspan="9"></td></tr>
    @endif
@endif
