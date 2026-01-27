{{-- resources/views/filament/resources/penjualans/pages/settings.blade.php --}}

<style>
    /* CSS MURNI UNTUK PREVIEW TABEL */
    .table-container {
        width: 100%;
        overflow-x: auto;
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1500px; /* Ditambah karena kolom sangat banyak */
        font-family: sans-serif;
        font-size: 13px;
    }

    .custom-table thead tr {
        background-color: #1F4ED8;
        color: #ffffff;
        text-align: left;
        font-weight: bold;
    }

    .custom-table th, 
    .custom-table td {
        padding: 12px 15px;
        border: 1px solid #e5e7eb;
        /* PAKSA TEXT TIDAK WRAP */
        white-space: nowrap; 
    }

    /* KECUALI KOLOM ALAMAT */
    .col-alamat {
        white-space: normal !important;
        min-width: 250px; /* Beri ruang agar alamat bisa membungkus dengan baik */
        max-width: 350px;
        line-height: 1.4;
    }

    .custom-table tbody tr:nth-of-type(even) {
        background-color: #f3f4f6;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .badge-member { background: #dcfce7; color: #166534; }
    .badge-regular { background: #f3f4f6; color: #374151; }

    .header-section {
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
.filter-section {
        background: #ffffff;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        gap: 4px;
        align-items: flex-end;
    }
    .container-inputs {
        display: flex;
        gap: 15px;
        align-items: flex-end;
    }

    .input-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .input-group label {
        font-size: 12px;
        font-weight: bold;
        color: #374151;
    }

    .input-field {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
    }

.btn-filter {
        background: #1F4ED8;
        color: white;
        padding: 9px 20px;
        border-radius: 6px;
        border: 1px solid #1F4ED8;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.2s;
    }

    .btn-filter:hover { background: #1e40af; }

    /* STYLE TOMBOL RESET GHOST */
    .btn-reset {
        background: transparent;
        color: #6b7280;
        padding: 9px 20px;
        border-radius: 6px;
        border: 1px solid #d1d5db; /* Border tipis */
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
        display: inline-block;
    }

    .btn-reset:hover {
        background: #f9fafb;
        color: #111827;
        border-color: #9ca3af;
    }
    .export-container {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    }

    .btn-export {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: #10b981; /* Warna Success (Hijau) */
        color: white;
        padding: 10px 16px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: background 0.2s;
        border: none;
        cursor: pointer;
    }

    .btn-export:hover {
        background-color: #059669;
    }

    /* Icon simple menggunakan SVG */
    .icon-download {
        width: 16px;
        height: 16px;
    }
</style>

<div class="p-10 bg-gray-50 min-h-screen">
    
<div class="header-section">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #111827;">PREVIEW LAPORAN PENJUALAN</h1>
            <p style="color: #6b7280;">Data terfilter: {{ $startDate }} s/d {{ $endDate }}</p>
        </div>
        <a href="{{ static::$resource::getUrl('index') }}" class="btn-reset">Kembali ke List</a>
    </div>
    
    <form id="filterForm" action="" method="GET" class="filter-section">
        <div class="container-inputs">
            <div class="input-group">
                <label>Dari Tanggal</label>
                <input type="date" name="dari_tanggal" value="{{ $startDate }}" class="input-field" onchange="this.form.submit()">
            </div>
            <div class="input-group">
                <label>Sampai Tanggal</label>
                <input type="date" name="sampai_tanggal" value="{{ $endDate }}" class="input-field" onchange="this.form.submit()">
            </div>
            <button type="submit" class="btn-filter">Tampilkan Data</button>
            <a href="{{ request()->url() }}" class="btn-reset">Reset Filter</a>
        </div>

        <div style="" class="">
            {{-- Kita tambahkan parameter export=... ke URL yang sekarang sedang aktif --}}
            <a href="{{ request()->fullUrlWithQuery(['export' => 'main']) }}" class="btn-export">
                <svg class="icon-download" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download Penjualan Excel
            </a>

            <a href="{{ request()->fullUrlWithQuery(['export' => 'detail']) }}" class="btn-export">
                <svg class="icon-download" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download Detail Penjualan
            </a>

            <a href="{{ request()->fullUrlWithQuery(['export' => 'full']) }}" class="btn-export" style="background-color: #059669;">
                <svg class="icon-download" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download Data Full Excel
            </a>
        </div>
    </form>


    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="text-align: center">No Nota</th>
                    <th style="text-align: center">Tanggal</th>
                    <th style="text-align: center">Customer</th>
                    <th style="text-align: center">Tipe</th>
                    <th style="text-align: center">Alamat</th>
                    <th style="text-align: center">Metode Bayar</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: right;">Bayar</th>
                    <th style="text-align: right;">Kembalian</th>
                    <th style="text-align: center">Status</th>
                    <th style="text-align: center">Kasir</th>
                    <th style="text-align: center">Validator</th>
                    <th style="text-align: center">Bank</th>
                    <th style="text-align: center">No Rekening</th>
                    <th style="text-align: center">Kendaraan</th>
                    <th style="text-align: center">Plat Kendaraan</th>
                    <th style="text-align: center">Nama Sopir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allPenjualan as $item)
                    <tr>
                        <td style="font-family: monospace; font-weight: bold;">{{ $item->no_nota }}</td>
                        <td>{{ $item->created_at->format('d M Y H:i') }}</td>
                        <td>{{ $item->nama_customer }}</td>
                        <td style="text-align: center;">
                            <span class="badge {{ $item->member ? 'badge-member' : 'badge-regular' }}">
                                {{ $item->member ? 'MEMBER' : 'REGULAR' }}
                            </span>
                        </td>
                        <td class="col-alamat">{{ $item->alamat ?? '-' }}</td>
                        <td style="text-align: center;">{{ $item->metode_pembayaran ?? '-' }}</td>
                        <td style="text-align: right; font-weight: bold;">
                            Rp {{ number_format($item->total ?? 0, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right;">
                            Rp {{ number_format($item->bayar ?? 0, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right;">
                            Rp {{ number_format($item->kembalian ?? 0, 0, ',', '.') }}
                        </td>
                        <td style="text-align: center;">{{ $item->status_transaksi ?? '-' }}</td>
                        <td>{{ $item->user?->name ?? '-' }}</td>
                        <td>{{ $item->validator?->name ?? '-' }}</td>
                        <td>{{ $item->bank ?? '-' }}</td>
                        <td>{{ $item->no_rekening ?? '-' }}</td>
                        <td>{{ $item->kendaraan ?? '-' }}</td>
                        <td>{{ $item->plat_kendaraan ?? 'ANTAR SENDIRI' }}</td>
                        <td>{{ $item->nama_sopir ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="17" style="text-align: center; padding: 40px; color: #9ca3af;">
                            Tidak ada data penjualan pada rentang tanggal ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    // Opsional: Memberikan efek loading saat form auto-submit agar user tahu proses sedang berjalan
    document.querySelectorAll('.input-field').forEach(input => {
        input.addEventListener('change', function() {
            document.body.style.opacity = '0.5';
            document.body.style.cursor = 'wait';
        });
    });
</script>