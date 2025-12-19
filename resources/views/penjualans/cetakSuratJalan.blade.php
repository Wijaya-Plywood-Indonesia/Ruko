<!DOCTYPE html>

<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <title>Surat Jalan</title>
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

            th,
            td {
                padding: 4px;
            }

            .border {
                border: 1px solid #000;
            }

            .text-center {
                text-align: center;
            }
            .text-right {
                text-align: right;
            }
            .text-left {
                text-align: left;
            }
            .mb-2 {
                margin-bottom: 10px;
            }
        </style>
    </head>

    <body onload="window.print()">
        <div class="page">
            {{-- COPY 1 : CUSTOMER --}}
            <div class="sj">
                <h2 class="text-center">Surat Jalan</h2>
                <p class="text-center">(Customer)</p>

                <table class="mb-2">
                    <tr>
                        <td width="50%">
                            <strong>No:</strong> {{ $penjualan->no_nota }}<br />
                            <strong>Tanggal:</strong>
                            {{ $penjualan->tanggal->format('d-M-y') }}
                        </td>
                        <td width="50%">
                            <strong>Pengiriman:</strong><br />
                            Sopir : {{ $penjualan->nama_sopir }}<br />
                            Mobil : {{ $penjualan->kendaraan }}<br />
                            No Plat :
                        </td>
                    </tr>
                </table>

                <table class="mb-2">
                    <tr>
                        <td>
                            <strong>Kepada:</strong><br />
                            {{ $penjualan->nama_customer }}<br />
                            {{ $penjualan->alamat ?? '' }}
                        </td>
                    </tr>
                </table>

                <table class="border">
                    <thead>
                        <tr>
                            <th class="border text-center" width="5%">No</th>
                            <th class="border">Nama Barang</th>
                            <th class="border text-center" width="10%">
                                Satuan
                            </th>
                            <th class="border text-center" width="10%">Qty</th>
                            <th class="border text-center" width="20%">Ket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($penjualan->details as $i => $detail)
                        <tr>
                            <td class="border text-center">{{ $i + 1 }}</td>
                            <td class="border">
                                {{ $detail->barang->nama_barang }}
                            </td>
                            <td class="border text-center">
                                {{ $detail->satuan }}
                            </td>
                            <td class="border text-center">
                                {{ number_format($detail->qty) }}
                            </td>
                            <td class="border text-center">
                                {{ $detail->keterangan ?? '' }}
                            </td>
                        </tr>
                        @endforeach
                        <tr>
                            <td colspan="3" class="border text-right">
                                <strong>Total</strong>
                            </td>
                            <td class="border text-center">
                                <strong
                                    >{{ number_format($penjualan->details->sum('qty')) }}</strong
                                >
                            </td>
                            <td class="border"></td>
                        </tr>
                    </tbody>
                </table>

                <table
                    width="100%"
                    style="margin-top: 25px; text-align: center"
                >
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
                        <td>
                            {{ $penjualan->nama_sopir ?? '( __________ )' }}
                        </td>
                        <td>( __________ )</td>
                        <td>{{ $penjualan->user->name ?? '-' }}</td>
                    </tr>
                </table>
            </div>

            <div class="cut-line"></div>

            {{-- COPY 2 : ARSIP --}}
            <div class="sj">
                <h2 class="text-center">Surat Jalan</h2>
                <p class="text-center">(Arsip)</p>

                {{-- ISI SAMA PERSIS DENGAN ATAS --}}
                {{-- sengaja diulang agar 1 file, mudah dirawat --}}

                <table class="mb-2">
                    <tr>
                        <td width="50%">
                            <strong>No:</strong> {{ $penjualan->no_nota }}<br />
                            <strong>Tanggal:</strong>
                            {{ $penjualan->tanggal->format('d-M-y') }}
                        </td>
                        <td width="50%">
                            <strong>Pengiriman:</strong><br />
                            Sopir : {{ $penjualan->nama_sopir }}<br />
                            Mobil : {{ $penjualan->kendaraan }}<br />
                            No Plat :
                        </td>
                    </tr>
                </table>

                <table class="mb-2">
                    <tr>
                        <td>
                            <strong>Kepada:</strong><br />
                            {{ $penjualan->nama_customer }}<br />
                            {{ $penjualan->alamat ?? '' }}
                        </td>
                    </tr>
                </table>

                <table class="border">
                    <thead>
                        <tr>
                            <th class="border text-center" width="5%">No</th>
                            <th class="border">Nama Barang</th>
                            <th class="border text-center" width="10%">
                                Satuan
                            </th>
                            <th class="border text-center" width="10%">Qty</th>
                            <th class="border text-center" width="20%">Ket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($penjualan->details as $i => $detail)
                        <tr>
                            <td class="border text-center">{{ $i + 1 }}</td>
                            <td class="border">
                                {{ $detail->barang->nama_barang }}
                            </td>
                            <td class="border text-center">
                                {{ $detail->satuan }}
                            </td>
                            <td class="border text-center">
                                {{ number_format($detail->qty) }}
                            </td>
                            <td class="border text-center">
                                {{ $detail->keterangan ?? '' }}
                            </td>
                        </tr>
                        @endforeach
                        <tr>
                            <td colspan="3" class="border text-right">
                                <strong>Total</strong>
                            </td>
                            <td class="border text-center">
                                <strong
                                    >{{ number_format($penjualan->details->sum('qty')) }}</strong
                                >
                            </td>
                            <td class="border"></td>
                        </tr>
                    </tbody>
                </table>

                <table
                    width="100%"
                    style="margin-top: 25px; text-align: center"
                >
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
                        <td>
                            {{ $penjualan->nama_sopir ?? '( __________ )' }}
                        </td>
                        <td>( __________ )</td>
                        <td>{{ $penjualan->user->name ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </body>
</html>

<!-- <!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <title>Surat Jalan</title>

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
                padding: 4px;
            }

            .border {
                border: 1px solid #000;
            }

            .text-center {
                text-align: center;
            }
            .text-right {
                text-align: right;
            }
            .text-left {
                text-align: left;
            }

            .mb-2 {
                margin-bottom: 10px;
            }
        </style>
    </head>

    <body onload="window.print()">
        <h2 class="text-center">Surat Jalan</h2>

        <table class="mb-2">
            <tr>
                <td width="50%">
                    <strong>No:</strong> {{ $penjualan->no_nota }}<br />
                    <strong>Tanggal:</strong>
                    {{ $penjualan->tanggal->format('d-M-y') }}
                </td>
                <td width="50%">
                    <strong>Pengiriman:</strong><br />
                    Sopir : {{ $penjualan->nama_sopir }}<br />
                    Mobil : {{ $penjualan->kendaraan }}<br />
                    No Plat :
                </td>
            </tr>
        </table>

        <table class="mb-2">
            <tr>
                <td>
                    <strong>Kepada:</strong><br />
                    {{ $penjualan->nama_customer }}<br />
                    {{ $penjualan->alamat ?? '' }}
                </td>
            </tr>
        </table>

        <table class="border">
            <thead>
                <tr>
                    <th class="border text-center" width="5%">No.</th>
                    <th class="border">Nama Barang</th>
                    <th class="border text-center" width="10%">Satuan</th>
                    <th class="border text-center" width="10%">Qty</th>
                    <th class="border text-center" width="20%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($penjualan->details as $i => $detail)
                <tr>
                    <td class="border text-center">{{ $i + 1 }}</td>
                    <td class="border">{{ $detail->barang->nama_barang }}</td>
                    <td class="border text-center">{{ $detail->satuan }}</td>
                    <td class="border text-center">
                        {{ number_format($detail->qty) }}
                    </td>
                    <td class="border text-center">
                        {{ $detail->keterangan ?? '' }}
                    </td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="border text-right">
                        <strong>Total</strong>
                    </td>
                    <td class="border text-center">
                        <strong
                            >{{ number_format($penjualan->details->sum('qty')) }}</strong
                        >
                    </td>
                    <td class="border"></td>
                </tr>
            </tbody>
        </table>

        <br /><br />

        <table width="100%" style="margin-top: 40px; text-align: center">
            <tr>
                <td width="25%">
                    <strong>Penerima</strong>
                </td>
                <td width="25%">
                    <strong>Sopir</strong>
                </td>
                <td width="25%">
                    <strong>Cek</strong>
                </td>
                <td width="25%">
                    <strong>Hormat Kami</strong>
                </td>
            </tr>
            <tr>
                {{-- ruang paraf --}}
                <td style="height: 50px"></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                {{-- nama --}}
                <td>( __________ )</td>
                <td>{{ $penjualan->nama_sopir ?? '( __________ )' }}</td>
                <td>( __________ )</td>
                <td>
                    {{ $penjualan->user->name ?? '-' }}
                </td>
            </tr>
        </table>
    </body>
</html> -->
