<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan</title>

    <style>
        /* =====================
           SETTING KERTAS F4
        ===================== */
        @page {
            size: 210mm 330mm;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
        }

        /* =====================
           SIMULASI KERTAS DI LAYAR
        ===================== */
        @media screen {
            body {
                background: #eee;
            }
            .page {
                background: #fff;
                box-shadow: 0 0 6px rgba(0, 0, 0, 0.3);
                margin: 10px auto;
            }
        }

        /* =====================
           LAYOUT HALAMAN
        ===================== */
        .page {
            width: 210mm;
            height: 330mm;
            box-sizing: border-box;
        }

        .sj {
            height: 50%;
            padding: 5mm;
            box-sizing: border-box;
        }

        .cut-line {
            border-top: 1px dashed #000;
            margin: 3mm 0;
        }

        /* =====================
           UTILITIES
        ===================== */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 4px;
        }

        .border {
            border: 1px solid #000;
        }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-left   { text-align: left; }

        .mb-2 {
            margin-bottom: 10px;
        }
    </style>
</head>

<body onload="window.print()">
<div class="page">

    {{-- ================= COPY 1 : CUSTOMER ================= --}}
    <div class="sj">
        <h2 class="text-center">Surat Jalan</h2>
        <p class="text-center">(Customer)</p>

        <table class="mb-2">
            <tr>
                <td width="50%">
                    <strong>No:</strong> {{ $suratJalan->no_surat_jalan }}<br>
                    <strong>Tanggal:</strong> {{ $suratJalan->tanggal_kirim->format('d-M-y') }}
                </td>
                <td width="50%">
                    <strong>Pengiriman:</strong><br>
                    Sopir : {{ $suratJalan->nama_supir }}<br>
                    Mobil : {{ $suratJalan->jeniskendaraan }}<br>
                    No Plat : {{ $suratJalan->plat }}
                </td>
            </tr>
        </table>

        <table class="mb-2">
            <tr>
                <td>
                    <strong>Kepada:</strong><br>
                    {{ $suratJalan->tokoTujuan->nama }}<br>
                    {{ $suratJalan->tokoTujuan->alamat ?? '' }}
                </td>
            </tr>
        </table>

        <table class="border">
            <thead>
                <tr>
                    <th class="border text-center" width="5%">No</th>
                    <th class="border">Nama Barang</th>
                    <th class="border text-center" width="10%">Satuan</th>
                    <th class="border text-center" width="10%">Qty</th>
                    <th class="border text-center" width="20%">Ket</th>
                </tr>
            </thead>
            <tbody>
                @php $totalQty = 0; @endphp
                @foreach ($suratJalan->details as $i => $detail)
                    @php $totalQty += $detail->qty_kirim; @endphp
                    <tr>
                        <td class="border text-center">{{ $i + 1 }}</td>
                        <td class="border">{{ $detail->barang->nama_barang }}</td>
                        <td class="border text-center">
                            {{ $detail->barang->satuan->nama_satuan ?? 'Lbr' }}
                        </td>
                        <td class="border text-center">
                            {{ number_format($detail->qty_kirim) }}
                        </td>
                        <td class="border text-center">
                            {{ $detail->catatan ?? '' }}
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="border text-right"><strong>Total</strong></td>
                    <td class="border text-center"><strong>{{ number_format($totalQty) }}</strong></td>
                    <td class="border"></td>
                </tr>
            </tbody>
        </table>

        <table width="100%" style="margin-top: 25px; text-align: center">
            <tr>
                <td width="25%"><strong>Penerima</strong></td>
                <td width="25%"><strong>Sopir</strong></td>
                <td width="25%"><strong>Cek</strong></td>
                <td width="25%"><strong>Hormat Kami</strong></td>
            </tr>
            <tr>
                <td style="height: 40px"></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>( __________ )</td>
                <td>{{ $suratJalan->nama_supir }}</td>
                <td>( __________ )</td>
                <td>{{ $suratJalan->createdBy->name ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="cut-line"></div>

    {{-- ================= COPY 2 : ARSIP ================= --}}
    <div class="sj">
        <h2 class="text-center">Surat Jalan</h2>
        <p class="text-center">(Arsip)</p>
 <table class="mb-2">
            <tr>
                <td width="50%">
                    <strong>No:</strong> {{ $suratJalan->no_surat_jalan }}<br>
                    <strong>Tanggal:</strong> {{ $suratJalan->tanggal_kirim->format('d-M-y') }}
                </td>
                <td width="50%">
                    <strong>Pengiriman:</strong><br>
                    Sopir : {{ $suratJalan->nama_supir }}<br>
                    Mobil : {{ $suratJalan->jeniskendaraan }}<br>
                    No Plat : {{ $suratJalan->plat }}
                </td>
            </tr>
        </table>

        <table class="mb-2">
            <tr>
                <td>
                    <strong>Kepada:</strong><br>
                    {{ $suratJalan->tokoTujuan->nama }}<br>
                    {{ $suratJalan->tokoTujuan->alamat ?? '' }}
                </td>
            </tr>
        </table>

        <table class="border">
            <thead>
                <tr>
                    <th class="border text-center" width="5%">No</th>
                    <th class="border">Nama Barang</th>
                    <th class="border text-center" width="10%">Satuan</th>
                    <th class="border text-center" width="10%">Qty</th>
                    <th class="border text-center" width="20%">Ket</th>
                </tr>
            </thead>
            <tbody>
                @php $totalQty = 0; @endphp
                @foreach ($suratJalan->details as $i => $detail)
                    @php $totalQty += $detail->qty_kirim; @endphp
                    <tr>
                        <td class="border text-center">{{ $i + 1 }}</td>
                        <td class="border">{{ $detail->barang->nama_barang }}</td>
                        <td class="border text-center">
                            {{ $detail->barang->satuan->nama_satuan ?? 'Lbr' }}
                        </td>
                        <td class="border text-center">
                            {{ number_format($detail->qty_kirim) }}
                        </td>
                        <td class="border text-center">
                            {{ $detail->catatan ?? '' }}
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="border text-right"><strong>Total</strong></td>
                    <td class="border text-center"><strong>{{ number_format($totalQty) }}</strong></td>
                    <td class="border"></td>
                </tr>
            </tbody>
        </table>

        <table width="100%" style="margin-top: 25px; text-align: center">
            <tr>
                <td width="25%"><strong>Penerima</strong></td>
                <td width="25%"><strong>Sopir</strong></td>
                <td width="25%"><strong>Cek</strong></td>
                <td width="25%"><strong>Hormat Kami</strong></td>
            </tr>
            <tr>
                <td styl >
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>( __________ )</td>
                <td>{{ $suratJalan->nama_supir }}</td>
                <td>( __________ )</td>
                <td>{{ $suratJalan->createdBy->name ?? '-' }}</td>
            </tr>
        </table>
       
    </div>

</div>
</body>
</html>
