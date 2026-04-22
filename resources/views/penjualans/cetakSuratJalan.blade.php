<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Jalan</title>

<style>
/* =====================
   KERTAS & PRINT
===================== */
@page {
    size: 210mm 330mm;
    margin: 5mm;
}

body {
    font-family: Arial, sans-serif;
    font-size: 16px;      /* ISI 16px */
    line-height: 1.4;
    margin: 0;
}

.page {
    width: 210mm;
    height: 330mm;       /* FIX → cegah page ke-2 */
    box-sizing: border-box;
}

/* =====================
   SATU COPY
===================== */
.sj {     /* 2 copy = 1 halaman */
    padding: 4mm;
    box-sizing: border-box;
    overflow: hidden;
}

/* =====================
   GARIS POTONG
===================== */
.cut-line {
    border-top: 1px dashed #000;
    margin: 10px 0 6px 0;   /* 10px dari tabel */
}

/* =====================
   UTIL
===================== */
.text-center { text-align: center; }
.text-right  { text-align: right; }
.mb-2 { margin-bottom: 6px; }

/* =====================
   JUDUL
===================== */
h2.title {
    font-size: 24px;     /* JUDUL 24px */
    font-weight: bold;
    margin: 0 0 6px 0;
}

/* =====================
   TABEL UMUM
===================== */
table {
    width: 100%;
    border-collapse: collapse;
}

td, th {
    padding: 4px;
    vertical-align: top;
}

/* =====================
   TABEL BARANG
===================== */
.table-barang th,
.table-barang td {
    border: 1px solid #000;
    padding: 6px 6px;      /* baris lebih lega */
    line-height: 1.3;
    vertical-align: middle;
}

.table-barang th {
    text-align: center;
    font-weight: bold;
}

/* lebar kolom */
.table-barang th:nth-child(1),
.table-barang td:nth-child(1) { width: 5%; }

.table-barang th:nth-child(2),
.table-barang td:nth-child(2) { width: 60%; }

.table-barang th:nth-child(3),
.table-barang td:nth-child(3) { width: 10%; }

.table-barang th:nth-child(4),
.table-barang td:nth-child(4) { width: 7%; }

.table-barang th:nth-child(5),
.table-barang td:nth-child(5) { width: 18%; }

/* =====================
   TANDA TANGAN
===================== */
.ttd {
    margin-top: 10px;
    text-align: center;
}
</style>
</head>

<body onload="window.print()">

<div class="page">

<!-- ================= COPY 1 ================= -->
<div class="sj">

<h2 class="title text-center">SURAT JALAN</h2>

<table class="mb-2">
<tr>
    <td width="50%">
        <strong>No:</strong> {{ $penjualan->no_nota }}<br>
        <strong>Tanggal:</strong> {{ $penjualan->tanggal->format('d-M-y') }}
    </td>
    <td width="50%">
        <strong>Pengiriman:</strong><br>
        Sopir : {{ $penjualan->nama_sopir }}<br>
        Mobil : {{ $penjualan->kendaraan }}<br>
        No Plat : {{ $penjualan->plat_kendaraan }}
    </td>
</tr>
<tr>
    <td>
        <strong>Kepada:</strong><br>
        {{ $penjualan->nama_customer }}<br>
        {{ $penjualan->alamat ?? '' }}
    </td>
    <td></td>
</tr>
</table>

<table class="table-barang">
<thead>
<tr>
    <th>No</th>
    <th>Nama Barang</th>
    <th>Satuan</th>
    <th>Qty</th>
    <th>Ket</th>
</tr>
</thead>
<tbody>
@foreach ($penjualan->details as $i => $detail)
<tr>
    <td class="text-center">{{ $i + 1 }}</td>
    <td>{{ $detail->barang->nama_barang }}</td>
    <td class="text-center">{{ $detail->satuan }}</td>
    <td class="text-center">{{ number_format($detail->qty,2 ) }}</td>
    <td>{{ $detail->keterangan ?? '' }}</td>
</tr>
@endforeach
<tr>
    <td colspan="3" class="text-right"><strong>Total</strong></td>
    <td class="text-center"><strong>{{ number_format($penjualan->details->sum('qty'), 2) }}</strong></td>
    <td></td>
</tr>
</tbody>
</table>

<table class="ttd">
<tr>
    <td width="25%"><strong>Penerima</strong></td>
    <td width="25%"><strong>Sopir</strong></td>
    <td width="25%"><strong>Cek</strong></td>
    <td width="25%"><strong>Hormat Kami</strong></td>
</tr>
<tr><td colspan="4" style="height:18px;"></td></tr>
<tr>
    <td>( __________ )</td>
    <td>{{ $penjualan->nama_sopir ?? '( __________ )' }}</td>
    <td>( __________ )</td>
    <td>{{ $penjualan->user->name ?? '-' }}</td>
</tr>
</table>

</div>

<div class="cut-line"></div>
<!-- ================= COPY 2 ================= -->
<div class="sj">

<h2 class="title text-center">SURAT JALAN — ARSIP</h2>

<!-- ISI SAMA PERSIS -->

<table class="mb-2">
<tr>
    <td width="50%">
        <strong>No:</strong> {{ $penjualan->no_nota }}<br>
        <strong>Tanggal:</strong> {{ $penjualan->tanggal->format('d-M-y') }}
    </td>
    <td width="50%">
        <strong>Pengiriman:</strong><br>
        Sopir : {{ $penjualan->nama_sopir }}<br>
        Mobil : {{ $penjualan->kendaraan }}<br>
        No Plat : {{ $penjualan->plat_kendaraan }}
    </td>
</tr>
<tr>
    <td>
        <strong>Kepada:</strong><br>
        {{ $penjualan->nama_customer }}<br>
        {{ $penjualan->alamat ?? '' }}
    </td>
    <td></td>
</tr>
</table>

<table class="table-barang">
<thead>
<tr>
    <th>No</th>
    <th>Nama Barang</th>
    <th>Satuan</th>
    <th>Qty</th>
    <th>Ket</th>
</tr>
</thead>
<tbody>
@foreach ($penjualan->details as $i => $detail)
<tr>
    <td class="text-center">{{ $i + 1 }}</td>
    <td>{{ $detail->barang->nama_barang }}</td>
    <td class="text-center">{{ $detail->satuan }}</td>
    <td class="text-center">{{ number_format($detail->qty,2) }}</td>
    <td>{{ $detail->keterangan ?? '' }}</td>
</tr>
@endforeach
<tr>
    <td colspan="3" class="text-right"><strong>Total</strong></td>
    <td class="text-center"><strong>{{ number_format($penjualan->details->sum('qty'),2) }}</strong></td>
    <td></td>
</tr>
</tbody>
</table>

<table class="ttd">
<tr>
    <td width="25%"><strong>Penerima</strong></td>
    <td width="25%"><strong>Sopir</strong></td>
    <td width="25%"><strong>Cek</strong></td>
    <td width="25%"><strong>Hormat Kami</strong></td>
</tr>
<tr><td colspan="4" style="height:18px;"></td></tr>
<tr>
    <td>( __________ )</td>
    <td>{{ $penjualan->nama_sopir ?? '( __________ )' }}</td>
    <td>( __________ )</td>
    <td>{{ $penjualan->user->name ?? '-' }}</td>
</tr>
</table>

</div>

</div>
</body>
</html>
