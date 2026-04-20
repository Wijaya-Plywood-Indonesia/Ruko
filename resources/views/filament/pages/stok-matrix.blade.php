<x-filament::page>

    {{-- ===================== --}}
    {{--  TABEL HORIZONTAL BARU --}}
    {{-- ===================== --}}

    <style>
        table.stok-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-bottom: 40px;
        }

        table.stok-table th,
        table.stok-table td {
            border: 1px solid #d1d5db;
            padding: 6px 10px;
            text-align: center;
        }

        table.stok-table th:first-child,
        table.stok-table td:first-child {
            text-align: left;
            font-weight: 600;
        }

        html.dark table.stok-table th,
        html.dark table.stok-table td {
            border-color: #4b5563;
        }
    </style>

    <table class="stok-table">
        <thead>
            <tr>
                <th>Barang</th>
                @foreach ($tokos as $toko)
                    <th>{{ $toko->nama_toko }}</th>
                @endforeach
                <th>Total Stok</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($barangs as $barang)
                @php $total = 0; @endphp
                <tr>
                    <td>{{ $barang->nama_barang }}</td>

                    @foreach ($tokos as $toko)
                        @php
                            $qty = $stok[$toko->id][$barang->id]->stok ?? 0;
                            $total += $qty;
                        @endphp
                        <td>{{ $qty }}</td>
                    @endforeach

                    <td>{{ $total }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>


    {{-- ========================== --}}
    {{--   VIEW STOK PER TOKO (ASLI) --}}
    {{-- ========================== --}}

    <style>
        .stok-group {
            margin-bottom: 32px;
            padding-bottom: 16px;
            border-bottom: 2px solid #d1d5db;
        }
        html.dark .stok-group {
            border-bottom-color: #4b5563;
        }

        .stok-title {
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 12px;
            text-align: center;
        }

        .stok-grid {
            display: grid;
            grid-template-columns: repeat(var(--pairs), 1fr);
            gap: 0;
        }

        .stok-column {
            padding-right: 24px;
            margin-right: 24px;
            border-right: 1px solid #d1d5db;
        }
        html.dark .stok-column {
            border-right-color: #4b5563;
        }

        .stok-column:nth-child(var(--pairs)n) {
            border-right: none !important;
            margin-right: 0 !important;
            padding-right: 0 !important;
        }

        .stok-row {
            display: grid;
            grid-template-columns: 1fr 60px;
            gap: 8px;
            padding: 6px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        html.dark .stok-row {
            border-bottom-color: #4b5563;
        }

        .stok-row div:first-child {
            padding-right: 10px;
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
            line-height: 1.2;
        }

        .stok-row div:last-child {
            text-align: right;
            padding-left: 10px;
            white-space: nowrap;
        }
    </style>


    @foreach ($tokos as $toko)
        <div class="stok-group">

            <div class="stok-title">{{ $toko->nama_toko }}</div>

            <div class="stok-grid" style="--pairs: {{ $pairs }};">

                @foreach ($barangs as $barang)
                    @php
                        $qty = $stok[$toko->id][$barang->id]->stok ?? 0;
                    @endphp

                    <div class="stok-column">
                        <div class="stok-row">
                            <div>{{ $barang->nama_barang }}</div>
                            <div>{{ $qty }}</div>
                        </div>
                    </div>

                @endforeach

            </div>

        </div>
    @endforeach

</x-filament::page>
