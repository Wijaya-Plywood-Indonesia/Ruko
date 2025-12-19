<!DOCTYPE html>

<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <title>Nota {{ $penjualan->no_nota }}</title>

        ```
        <style>
            /* =====================
       SETTING KERTAS F4
       ===================== */
            @page {
                size: 210mm 330mm; /* F4 */
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
                padding: 5mm;
                position: relative;
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
                background: #fff; /* nutup garis di belakang teks */
                padding: 0 6px;
                font-size: 10px;
            }

            /* =====================
       UTILITIES
       ===================== */
            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                border: 1px solid #000;
                padding: 4px;
            }

            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
        </style>
        ```
    </head>

    <body onload="window.print()">
        <div class="page">
            ```
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
                            {{ number_format($detail->qty) }}
                        </td>
                        <td class="text-right">
                            {{ number_format($detail->harga_jual) }}
                        </td>
                        <td class="text-right">
                            {{ number_format($detail->subtotal) }}
                        </td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="5" class="text-right">
                            <strong>Total</strong>
                        </td>
                        <td class="text-right">
                            <strong
                                >{{ number_format($penjualan->total) }}</strong
                            >
                        </td>
                    </tr>
                </tbody>
            </table>

            <br />

            @if ($penjualan->metode_pembayaran === 'TRANSFER')
            <div style="width: 45%; background: #eee; padding: 8px">
                <strong>Pembayaran:</strong> Transfer<br />
                Bank: {{ $penjualan->bank }}<br />
                No Rek: {{ $penjualan->no_rekening }}
            </div>
            @endif

            <br /><br />

            <table style="border: none">
                <tr>
                    <td style="border: none">Cek</td>
                    <td style="border: none" class="text-right">
                        Hormat Kami<br /><br /><br />
                        <strong>{{ $penjualan->user->name }}</strong>
                    </td>
                </tr>
            </table>

            <!-- GARIS POTONG -->
            <div class="cut-line"></div>
            <div class="cut-text">✂ Potong di sini</div>
            ```
        </div>
    </body>
</html>

<!-- <!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <title>Nota {{ $penjualan->no_nota }}</title>

        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
            }

            @media print {
                .no-print {
                    display: none;
                }
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                border: 1px solid #000;
                padding: 4px;
            }

            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
        </style>
    </head>
    <body onload="window.print()">
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
                        {{ number_format($detail->qty) }}
                    </td>
                    <td class="text-right">
                        {{ number_format($detail->harga_jual) }}
                    </td>
                    <td class="text-right">
                        {{ number_format($detail->subtotal) }}
                    </td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="5" class="text-right">
                        <strong>Total</strong>
                    </td>
                    <td class="text-right">
                        <strong>{{ number_format($penjualan->total) }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>

        <br />

        @if ($penjualan->metode_pembayaran === 'TRANSFER')
        <div style="width: 40%; background: #eee; padding: 8px">
            <strong>Pembayaran:</strong> Transfer<br />
            Bank: {{ $penjualan->bank }}<br />
            No Rek: {{ $penjualan->no_rekening }}
        </div>
        @endif

        <br /><br />

        <table style="border: none">
            <tr>
                <td style="border: none">Cek</td>
                <td style="border: none" class="text-right">
                    Hormat Kami<br /><br /><br />
                    <strong>{{ $penjualan->user->name }}</strong>
                </td>
            </tr>
        </table>
    </body>
</html> -->
