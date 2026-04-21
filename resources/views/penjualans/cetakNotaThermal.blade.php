<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <title>Nota {{ $penjualan->no_nota }}</title>

        <style>
            /* =================================
           THERMAL 55mm – POS STYLE FINAL
        ================================= */

            @page {
                size: 57mm auto;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0;
                font-family: "Courier New", Consolas, monospace;
                font-size: 9px;
                font-weight: 800;
                line-height: 1.3;
                letter-spacing: -0.2px;
                color: #000;
            }

            /* ===== PREVIEW LAYAR ===== */
            @media screen {
                body {
                    background: #eee;
                }

                .thermal {
                    width: 57mm;
                    background: #fff;
                    margin: 12px auto;
                    padding: 4px;
                    box-shadow: 0 0 6px rgba(0, 0, 0, 0.3);
                }
            }

            /* ===== CETAK ===== */
            @media print {
                .thermal {
                    width: 57mm;
                    margin: 0;
                    padding: 0;
                    box-shadow: none;
                }
            }

            /* ===== KUNCI: BUAT FONT LEBIH PANJANG ===== */
            .thermal {
                transform: scaleY(2); /* atur 1.1 – 1.2 jika perlu */
                transform-origin: top;
            }

            /* ===== PAKSA TINTA LEBIH GELAP ===== */
            * {
                text-shadow: 0.35px 0 0 #000, -0.35px 0 0 #000;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .text-center {
                text-align: center;
            }
            .text-right {
                text-align: right;
            }

            .bold {
                font-weight: 900;
                font-size: 10px;
            }

            .total {
                font-size: 14px;
                font-weight: 900;
                letter-spacing: -0.3px;
            }

            .small {
                font-size: 11px;
                font-weight: 700;
            }

            hr {
                border: none;
                border-top: 1px dashed #000;
                margin: 4px 0;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            td {
                padding: 2px 0;
                vertical-align: top;
            }
        </style>
    </head>

    <body onload="window.print()">
        <div class="thermal">
            <!-- ===== HEADER ===== -->
            <div style="letter-spacing: 2.5px" class="text-center bold">NOTA PENJUALAN</div>
            <div style="letter-spacing: 2.5px" class="text-center bold">RUKO INA</div>
            <div  class="text-center small">------------------------------</div>

            <br />

            No : {{ $penjualan->no_nota }}<br />
            Tgl : {{ $penjualan->tanggal->format('d-m-Y') }}<br />
            Kepada : {{ $penjualan->nama_customer }}

            <hr />

            <!-- ===== ITEM ===== -->
            <table>
                <tbody>
                    @foreach ($penjualan->details as $detail)
                    <tr>
                        <td colspan="2" class="bold">
                            {{ $detail->barang->nama_barang }}
                        </td>
                    </tr>

                    <tr>
                        <td class="small">
                            {{ number_format($detail->qty, 2) }}
                            {{ $detail->satuan }}
                            x {{ number_format($detail->harga_jual) }}
                        </td>
                        <td class="text-right">
                            {{ number_format($detail->subtotal) }}
                        </td>
                    </tr>

                    @if(($detail->potongan ?? 0) > 0)
                    <tr>
                        <td class="small">
                            Diskon {{ number_format($detail->potongan) }} x
                            {{ $detail->qty }}
                        </td>
                        <td class="text-right small">
                            -{{ number_format($detail->potongan * $detail->qty) }}
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td colspan="2"><hr /></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- ===== TOTAL ===== -->
            <table>
                <tr>
                    <td class="total bold">TOTAL</td>
                    <td class="text-right bold total">
                        Rp. {{ number_format($penjualan->total) }}
                    </td>
                </tr>
                <tr>
                    <td class="" style="padding: 0; font-weight: 600; font-size: 10px;">Bayar ({{ strtolower($penjualan->metode_pembayaran) }})</td>
                    <td class="text-right " style="padding: 0; font-weight: 600; font-size: 10px;">
                        Rp. {{ number_format($penjualan->bayar) }}
                    </td>
                </tr>
                <br>    
                <tr >
                    <td class="" style="padding: 0; font-weight: 600; font-size: 10px;">Kembali</td>
                    <td class="text-right " style="padding: 0; font-weight: 600; font-size: 10px;">
                        Rp. {{ number_format($penjualan->kembali) }}
                    </td>
                </tr>
            </table>

            @if ($totalPotonganNota > 0)
            <hr />
            <div class="text-center small">
                Hemat Rp {{ number_format($totalPotonganNota) }}
            </div>
            @endif

            <hr />

            <!-- ===== PEMBAYARAN ===== -->
            @if ($penjualan->metode_pembayaran === 'TRANSFER')
            <div class="small">
                Pembayaran : <span class="bold">TRANSFER</span><br />
                Bank : {{ $penjualan->bank }}<br />
                Rek : {{ $penjualan->no_rekening }} <br />
                Atas Nama:
                {{ $penjualan->rekeningPerusahaan?->atas_nama ?? '-' }}
            </div>
            <hr />
            @endif

            <!-- ===== FOOTER ===== -->
            <div  class="text-center small">
                TERIMA KASIH<br />
                Kasir : {{ $penjualan->user->name }}
            </div>
        </div>
    </body>
</html>
