<x-filament::page>

    {{-- ========================================= --}}
    {{-- VIEW GRID TOTAL GABUNGAN BARANG (BERSIH)  --}}
    {{-- ========================================= --}}

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

    <div class="stok-group">

        {{-- Judul Menu Utama Ringkasan Akuntansi --}}
        <div class="stok-title">Ringkasan Total Stok Seluruh Barang (Buku Besar)</div>

        {{-- Grid responsive diatur menggunakan jumlah pasang kolom dari properti $pairs --}}
        <div class="stok-grid" style="--pairs: {{ $pairs }};">

            @foreach ($barangs as $barang)
            @php
            // 🔍 OPTIMASI BARU: Ambil nilai langsung per ID barang dari Buku Besar JurnalUmum
            // Tidak memerlukan loop nested internal $tokos lagi sehingga loading halaman instan
            $qtyBarang = $stok[$barang->id]->stok ?? 0.0;
            @endphp

            <div class="stok-column">
                <div class="stok-row">
                    {{-- Nama produk yang terikat no_akun valid --}}
                    <div>{{ $barang->nama_barang }}</div>

                    {{-- Angka saldo berjalan dicetak presisi desimal:4 jika ada pecahan sisa timbangan pakan --}}
                    <div>
                        <strong>
                            {{ $qtyBarang > 0 ? number_format($qtyBarang, 2, ',', '.') : '0' }}
                        </strong>
                    </div>
                </div>
            </div>
            @endforeach

        </div>

    </div>

</x-filament::page>