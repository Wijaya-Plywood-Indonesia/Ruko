{{-- resources/views/filament/resources/penjualans/pages/preview-export.blade.php --}}

@php
    $viewType = request()->query('view_type', 'main');
@endphp

<style>
    /* CSS DASAR */
    .table-container { width: 100%; overflow-x: auto; background: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .custom-table { width: 100%; border-collapse: collapse; font-family: sans-serif; font-size: 13px; }
    .custom-table thead tr { background-color: #1F4ED8; color: #ffffff; text-align: left; }
    .custom-table th, .custom-table td { padding: 12px 15px; border: 1px solid #e5e7eb; white-space: nowrap; }
    .custom-table tbody tr:nth-of-type(even) { background-color: #f3f4f6; }

    /* UTILITIES */
    .col-alamat { white-space: normal !important; min-width: 250px; max-width: 350px; line-height: 1.4; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
    .badge-member { background: #dcfce7; color: #166534; }
    .badge-regular { background: #f3f4f6; color: #374151; }

    /* FILTER & ACTION SECTION */

    .filter-section {
        background: #ffffff; padding: 20px; border-radius: 8px; margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: flex-end;
        flex-wrap: wrap; gap: 15px;
    }
    .container-inputs { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
    .input-group { display: flex; flex-direction: column; gap: 5px; }
    .input-group label { font-size: 12px; font-weight: bold; color: #374151; }
    .input-field { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: white; }

    /* BUTTONS */
    .btn-export {
        display: inline-flex; align-items: center; gap: 8px; background-color: #10b981;
        color: white; padding: 9px 16px; border-radius:  6px ; /* Rounded di kanan saja */
        text-decoration: none; font-size: 14px; font-weight: 600; border: none; cursor: pointer;
    }
    .btn-export:hover { background-color: #059669; }
    
    .select-export-type {
        padding: 8px 12px; border: 1px solid #d1d5db; border-right: none;
        border-radius: 6px 0 0 6px; font-size: 14px; background: #f9fafb;
    }

    /* NESTED TABLE FOR DETAIL */
    .detail-row { background-color: #fefce8 !important; }
    .detail-table { width: 100%; border: 1px solid #fde047; margin: 5px 0; background: white; }
    .detail-table th { background: #facc15; color: #854d0e; font-size: 11px; }
</style>

<div class="p-10 bg-gray-50 min-h-screen">
    
    <div class="header-section" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #111827;">PREVIEW LAPORAN PENJUALAN</h1>
            <p style="color: #6b7280;">Mode Tampilan: <span style="color: #1F4ED8; font-weight: bold; text-transform: uppercase;">{{ $viewType }}</span></p>
        </div>
        <a href="{{ static::$resource::getUrl('index') }}" style="text-decoration: none; color: #6b7280; font-size: 14px; border: 1px solid #d1d5db; padding: 8px 16px; border-radius: 6px;"> Kembali ke List</a>
    </div>
    
    <div class="filter-section">
        {{-- POJOK KIRI: FILTER TANGGAL + SELECT TIPE PREVIEW --}}
        <form id="filterForm" action="" method="GET" class="container-inputs">
            {{-- FILTER TANGGAL --}}
            <div class="input-group">
                <label>Dari Tanggal</label>
                <input type="date" name="dari_tanggal" value="{{ $startDate }}" class="input-field" onchange="this.form.submit()">
            </div>
            <div class="input-group">
                <label>Sampai Tanggal</label>
                <input type="date" name="sampai_tanggal" value="{{ $endDate }}" class="input-field" onchange="this.form.submit()">
            </div>

            {{-- SELECT TIPE PREVIEW (TABLE) --}}
            <div class="input-group">
                <label>Tampilan Tabel Preview</label>
                <select id="view_type" name="view_type" class="input-field" onchange="this.form.submit()" style="min-width: 150px; background: #eff6ff; border-color: #bfdbfe;">
                    <option value="main" {{ $viewType == 'main' ? 'selected' : '' }}>Penjualan Utama</option>
                    <option value="detail" {{ $viewType == 'detail' ? 'selected' : '' }}>Detail Penjualan</option>
                    <option value="full" {{ $viewType == 'full' ? 'selected' : '' }}>Penjualan Lengkap</option>
                </select>
            </div>
            
            <a href="{{ request()->url() }}" style="color: #ef4444; font-size: 12px; text-decoration: none; border: 1px solid #ef4444; padding: 8px 16px; border-radius: 6px;">Reset Filter</a>
        </form>

        {{-- POJOK KANAN: SELECT EXPORT + BUTTON --}}
        
        <div style="display: flex; align-items: center;">
            <button 
                type="button"
                class="btn-export"
                onclick="eksekusiDownload()"
            >
                <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export Excel
            </button>
        </div>
    </div>

    <div class="table-container">
        <table class="custom-table" style="min-width: {{ $viewType == 'full' ? '2000px' : '1200px' }}">
            <thead>
                <tr>
                    <th class="text-center">No Nota</th>
                    <th class="text-center">Tanggal</th>
                    <th>Nama Pelanggan</th>

                    @if($viewType == 'full' || $viewType == 'main')
                        <th class="text-center">Tipe</th>
                        <th>Alamat</th>
                        <th>Metode Bayar</th>
                    @endif

                    @if ($viewType == "detail")
                        <th>Nama Barang</th>
                        <th>Harga Awal</th>
                        <th>Harga Jual</th>
                        <th>Diskon</th>
                        <th>Jumlah</th>
                        <th>Total Diskon</th>
                        <th>Subtotal</th>
                    @endif

                    @if($viewType == 'full' || $viewType == 'main')
                    <th class="text-right">Total</th>
                    <th class="text-right">Bayar</th>
                    <th class="text-right">Kembalian</th>
                    @endif
                    
                    <th class="text-center">Status</th>
                    <th>Kasir</th>

                    @if($viewType == 'full' || $viewType == 'main')
                        <th class="text-center">Validator</th>
                        <th class="text-center">Bank</th>
                        <th class="text-center">No Rekening</th>
                        <th>Kendaraan</th>
                        <th>Plat Kendaraan</th>
                        <th>Nama Sopir</th>
                    @endif
                    
                    <th>Keterangan</th>

                </tr>
            </thead>
            <tbody>
                @forelse($laporanGabungan as $item)
                    {{-- JIKA MODE DETAIL: TAMPILKAN BARIS INDUK --}}
                    @if ($viewType == 'detail')
                        @forelse ($item['data_penjualan_detail'] as $detail)
                            <tr>
                                <td class="text-center" style="font-family: monospace; font-weight: bold;">{{ $item['no_nota'] }}</td>
                                <td class="text-center">{{ $item['tanggal'] }}</td>
                                <td>{{ $item['nama_customer'] }}</td>

                                @if($viewType == 'full' || $viewType == 'main')
                                <td class="text-center">
                                    <span class="badge {{ $item['member'] == 'MEMBER' ? 'badge-member' : 'badge-regular' }}">
                                        {{ $item['member'] }}
                                    </span>
                                </td>
                                <td class="col-alamat">{{ $item['alamat'] ?? '-' }}</td>
                                <td class="text-center">{{ $item['metode_pembayaran'] }}</td>
                                @endif

                                @if ($viewType == 'detail')
                                <td>{{ $detail['nama_barang'] }}</td>
                                <td class="text-right">Rp {{ number_format($detail['harga_awal'], 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($detail['harga_jual'], 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($detail['diskon'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ $detail['jumlah'] }}</td>
                                <td class="text-right">Rp {{ number_format($detail['total_diskon'], 0, ',', '.') }}</td>
                                <td class="text-right" style="font-weight: bold">Rp {{ number_format((float)$detail['subtotal'], 0, ',', '.') }}</td>
                                @endif

                                <td>{{ $item['status_transaksi'] }}</td>
                                <td>{{ $item['kasir'] }}</td>
                                <td>{{ $item['keterangan'] }}</td>
                            </tr>
                        @empty
                        @endforelse
                    @else
                        <tr>
                            <td class="text-center" style="font-family: monospace; font-weight: bold;">{{ $item['no_nota'] }}</td>
                            <td class="text-center">{{ $item['tanggal'] }}</td>
                            <td>{{ $item['nama_customer'] }}</td>
                            <td class="text-center">
                                <span class="badge {{ $item['member'] == 'MEMBER' ? 'badge-member' : 'badge-regular' }}">
                                    {{ $item['member'] }}
                                </span>
                            </td>
                            <td class="col-alamat">{{ $item['alamat'] ?? '-' }}</td>
                            <td class="text-center">{{ $item['metode_pembayaran'] }}</td>
                            <td class="text-right" style="font-weight: bold;">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($item['bayar'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($item['kembalian'], 0, ',', '.') }}</td>
                            <td>{{ $item['status_transaksi'] }}</td>
                            <td>{{ $item['kasir'] }}</td>
                            <td class="text-center">{{ $item['validator'] }}</td>
                            <td class="text-center">{{ $item['bank'] }}</td>
                            <td class="text-center">{{ $item['no_rekening'] }}</td>
                            <td>{{ $item['kendaraan'] }}</td>
                            <td>{{ $item['plat_kendaraan'] }}</td>
                            <td>{{ $item['nama_sopir'] }}</td>
                            <td>{{ $item['keterangan'] }}</td>
                        </tr>
                    @endif

                    {{-- JIKA MODE FULL: TAMPILKAN BARIS ANAK --}}
                    @if($viewType == 'full' && !empty($item['data_penjualan_detail']))
                        <tr class="detail-row">
                            <td colspan="100%" style="padding: 10px 40px;">
                                <table class="detail-table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Nama Barang</th>
                                            <th class="text-right">Harga Awal</th>
                                            <th class="text-right">Harga Jual</th>
                                            <th class="text-right">Diskon</th>
                                            <th class="text-center">Jumlah</th>
                                            <th class="text-right">Total Diskon</th>
                                            <th class="text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($item['data_penjualan_detail'] as $det)
                                            <tr>
                                                <td>{{ $det['nama_barang'] }}</td>
                                                <td class="text-right">Rp {{ number_format($det['harga_awal'], 0, ',', '.') }}</td>
                                                <td class="text-right">Rp {{ number_format($det['harga_jual'], 0, ',', '.') }}</td>
                                                <td class="text-right">Rp {{ number_format($det['diskon'], 0, ',', '.') }}</td>
                                                <td class="text-center">{{ $det['jumlah'] }}</td>
                                                <td class="text-right">Rp {{ number_format($det['total_diskon'], 0, ',', '.') }}</td>
                                                <td class="text-right">Rp {{ number_format($det['subtotal'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="100%" class="text-center" style="padding: 40px; color: #9ca3af;">Data tidak ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<script>
    // Fungsi untuk handle export dengan mengambil value dari select export
    function handleExport() {
        const type = document.getElementById('view_type').value;
        const dari = document.querySelector('[name="dari_tanggal"]').value;
        const sampai = document.querySelector('[name="sampai_tanggal"]').value;
        // Mengambil URL Filament Page secara dinamis
        const baseUrl = "{{ \App\Filament\Resources\Penjualans\PenjualanResource::getUrl('download') }}";
        const url = `${baseUrl}?type=${type}&dari_tanggal=${dari}&sampai_tanggal=${sampai}`;
        window.location.href = url;
    }

    // Visual feedback on change
    document.querySelectorAll('.input-field').forEach(input => {
        input.addEventListener('change', function() {
            document.body.style.opacity = '0.5';
        });
    });


    function eksekusiDownload() {
    // Ambil data dari input filter tanggal yang ada di blade kamu
    const tipe = '{{ $viewType }}';
    const dari = document.querySelector('[name="dari_tanggal"]').value;
    const sampai = document.querySelector('[name="sampai_tanggal"]').value;

    if(!dari || !sampai) {
        alert('Silahkan pilih tanggal terlebih dahulu');
        return;
    }

    // Bangun URL secara manual ke Route Controller
    const url = "{{ route('force.download') }}?type=" + tipe + "&dari=" + dari + "&sampai=" + sampai;

    // contoh : "http://localhost:8080/force-download-excel?type=main&dari=2024-01-01&sampai=2026-01-31"
    // Paksa browser buka URL ini (Akan memicu download otomatis)
    window.location.href = url;
    }

</script>