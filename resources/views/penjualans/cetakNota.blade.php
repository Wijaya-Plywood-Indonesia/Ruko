<!DOCTYPE html>

<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <title>Nota {{ $penjualan->no_nota }}</title>
<style>
    /* =====================
       SETTING KERTAS F4
       ===================== */
    @page {
        size: 210mm 330mm; /* F4 */
        margin: 5mm;
    }

    body {
        font-family: Arial, sans-serif;
        font-size: 16px;
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
        padding: 5mm;
        position: relative;
        font-size: 16px;
    }

    /* =====================
       JUDUL
       ===================== */
    h2 {
        font-size: 24px;
        margin: 0 0 8px 0;
        text-align: center;
    }

    /* =====================
       GARIS POTONG
       ===================== */
    .cut-line {
        position: absolute;
        top: 50%;
        left: 5mm;
        right: 5mm;
        transform: translateY(-50%);
        border-top: 1px dashed #000;
    }

    .cut-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #fff;
        padding: 0 6px;
        font-size: 1px;
    }

    /* =====================
       TABEL
       ===================== */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 16px;
    }

    th,
    td {
        border: 1px solid #000;
        padding: 6px 8px;
    }

    th {
        font-weight: bold;
        background: #f5f5f5;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    /* ============================
       UPDATE: LEBARKAN BARIS DATA
       ============================ */
    tbody tr td {
        padding-top: 14px;
        padding-bottom: 14px; 
    }

    /* ============================
       UPDATE: LEBARKAN KOLOM NAMA BARANG
       ============================ */
    th:nth-child(2),
    td:nth-child(2) {
        width: 35%;
    }
</style>

    </head>

    <body onload="window.print()">
        <div class="page">
 
            <h2 class="text-center">Nota</h2>

            <table style="border: none">
                <tr>
                    <td style="border: none">
                        <strong>No:</strong> {{ $penjualan->no_nota }}<br />
                        <strong>Tanggal:</strong>
                        {{ $penjualan->tanggal->format('d-m-Y') }}
                    </td>
                    <td style="border: none" class="text-right">
                        <strong>Kepada:</strong><br />
                        {{ $penjualan->nama_customer }}
                    </td>
                </tr>
            </table>

            <br />

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Potongan</th>
                        <th>Total Potongan</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($penjualan->details as $i => $detail)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $detail->barang->nama_barang }}</td>
                        <td class="text-center">{{ $detail->satuan }}</td>
                        <td class="text-right">
                            {{ number_format($detail->qty, 2) }}
                        </td>
                        <td class="text-right">
                            {{ number_format($detail->harga_jual) }}
                        </td>
                        {{-- POTONGAN PER PCS --}}
                        <td class="text-right">
                            {{ number_format($detail->potongan ?? 0) }}
                        </td>

                        @php $totalPotonganItem = ($detail->potongan ?? 0) *
                        $detail->qty; @endphp

                        <td class="text-right">
                            {{ number_format($totalPotonganItem) }}
                        </td>

                        <td class="text-right">
                            {{ number_format($detail->subtotal) }}
                        </td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="7" class="text-right">
                            <strong>Total</strong>
                        </td>
                        <td class="text-right">
                            <strong
                                >{{ number_format($penjualan->total) }}</strong
                            >
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="text-center">
                            @if ($totalPotonganNota > 0)
                            <p style="margin-top: 6px; font-size: 11px">
                                <em>
                                    🎉 Anda menghemat
                                    <strong
                                        >Rp
                                        {{
                                            number_format($totalPotonganNota)
                                        }}</strong
                                    >
                                    Pada Pembelian Kali Ini !
                                </em>
                            </p>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>

            <br />

            @if ($penjualan->metode_pembayaran === 'TRANSFER')
            <div style="width: 45%; background: #eee; padding: 8px">
                <strong>Pembayaran:</strong> Transfer<br />
                Bank: {{ $penjualan->bank }}<br />
                No Rek: {{ $penjualan->no_rekening }}<br />
                Atas Nama:
                {{ $penjualan->rekeningPerusahaan?->atas_nama ?? '-' }}
            </div>
            @endif

            <br /><br />

            <table style="border: none">
                <tr>
                    <td style="border: none">Cek</td>
                    <td style="border: none" class="text-right">
                        Hormat Kami<br /><br /><br /><br /><br />
                        <strong>{{ $penjualan->user->name }}</strong>
                    </td>
                </tr>
            </table>

            <!-- GARIS POTONG -->
            <div class="cut-line"></div>
            <div class="cut-text">✂ Potong di sini</div>

        </div>
    </body>
</html>
