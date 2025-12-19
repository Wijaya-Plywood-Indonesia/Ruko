<!DOCTYPE html>
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
                        {{ number_format($detail->harga) }}
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
</html>
