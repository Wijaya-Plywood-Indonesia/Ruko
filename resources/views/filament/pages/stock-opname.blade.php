<x-filament::page>

    {{-- ===========================
    FORM PILIH TOKO
    =========================== --}}
    @if (!$opname)
        <x-filament::section heading="Mulai Stock Opname">
            {{ $this->form }}
            <div class="mt-4">
                <x-filament::button wire:click="mulaiOpname" color="primary" icon="heroicon-o-play">
                    Mulai Opname
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- ===========================
        FILTER
        =========================== --}}
        <x-filament::section heading="Filter" class="mt-6">
            <div class="so-filter-bar">
                <div class="so-filter-group">
                    <label class="so-filter-label">Dari Tanggal</label>
                    <input
                        type="date"
                        class="so-filter-input"
                        wire:model="filterTanggalDari"
                    >
                </div>
                <div class="so-filter-group">
                    <label class="so-filter-label">Sampai Tanggal</label>
                    <input
                        type="date"
                        class="so-filter-input"
                        wire:model="filterTanggalSampai"
                    >
                </div>
                <div class="so-filter-group">
                    <label class="so-filter-label">Status</label>
                    <select class="so-filter-input" wire:model="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="menunggu">Menunggu Approval</option>
                        <option value="ditolak">Ditolak</option>
                        <option value="disetujui">Disetujui</option>
                    </select>
                </div>
                <div class="so-filter-actions">
                    <button wire:click="terapkanFilter" class="so-btn-filter-primary">
                        Terapkan
                    </button>
                    <button wire:click="resetFilter" class="so-btn-filter-reset">
                        Reset
                    </button>
                </div>
            </div>
        </x-filament::section>

        {{-- ===========================
        DAFTAR OPNAME BERJALAN
        =========================== --}}
        @if (count($daftarOpname) > 0)
            <x-filament::section heading="Opname Berjalan" class="mt-6">
                <p class="text-sm text-gray-500 mb-4">
                    Daftar opname yang sedang dalam proses (draft, menunggu approval, atau ditolak).
                </p>
                <div class="overflow-x-auto">
                    <table class="so-table">
                        <thead>
                            <tr>
                                <th>No Opname</th>
                                <th>Toko</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-center">Status</th>
                                <th>Dibuat Oleh</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarOpname as $row)
                                @php
                                    $badgeColor = match($row['status']) {
                                        'draft'    => '#6b7280',
                                        'menunggu' => '#d97706',
                                        'ditolak'  => '#dc2626',
                                        default    => '#6b7280',
                                    };
                                    $badgeLabel = match($row['status']) {
                                        'draft'    => 'Draft',
                                        'menunggu' => 'Menunggu Approval',
                                        'ditolak'  => 'Ditolak',
                                        default    => $row['status'],
                                    };
                                @endphp
                                <tr class="{{ $row['status'] === 'menunggu' ? 'so-row-menunggu' : '' }}">
                                    <td class="font-mono text-xs font-semibold">
                                        {{ $row['no_opname'] }}
                                    </td>
                                    <td class="font-medium">
                                        {{ $row['toko'] }}
                                    </td>
                                    <td class="text-center text-sm">
                                        {{ $row['tanggal'] }}
                                    </td>
                                    <td class="text-center">
                                        <span class="so-badge" style="background: {{ $badgeColor }}">
                                            {{ $badgeLabel }}
                                        </span>
                                    </td>
                                    <td class="text-sm text-gray-600">
                                        {{ $row['created_by'] }}
                                    </td>
                                    <td class="text-center">
                                        <button
                                            wire:click="bukaOpname({{ $row['id'] }})"
                                            class="so-btn-buka"
                                        >
                                            @if ($row['status'] === 'menunggu')
                                                Review
                                            @elseif ($row['status'] === 'ditolak')
                                                Lihat
                                            @else
                                                Lanjutkan

                                            @endif
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- ===========================
        RIWAYAT OPNAME SELESAI
        =========================== --}}
        <x-filament::section heading="Riwayat Opname" class="mt-6">
            @if (count($riwayatOpname) > 0)
                <p class="text-sm text-gray-500 mb-4">
                    Menampilkan maks. 50 opname yang sudah disetujui sesuai filter.
                </p>
                <div class="overflow-x-auto">
                    <table class="so-table">
                        <thead>
                            <tr>
                                <th>No Opname</th>
                                <th>Toko</th>
                                <th class="text-center">Tgl Opname</th>
                                <th>Dibuat Oleh</th>
                                <th>Disetujui Oleh</th>
                                <th class="text-center">Tgl Disetujui</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($riwayatOpname as $row)
                                <tr class="so-row-sesuai">
                                    <td class="font-mono text-xs font-semibold">
                                        {{ $row['no_opname'] }}
                                    </td>
                                    <td class="font-medium">{{ $row['toko'] }}</td>
                                    <td class="text-center text-sm">{{ $row['tanggal'] }}</td>
                                    <td class="text-sm text-gray-600">{{ $row['created_by'] }}</td>
                                    <td class="text-sm text-gray-600">{{ $row['approved_by'] }}</td>
                                    <td class="text-center text-sm">{{ $row['approved_at'] }}</td>
                                    <td class="text-center">
                                        <span class="so-badge" style="background: #16a34a">Disetujui</span>
                                    </td>
                                    <td class="text-center">
                                        <button
                                            wire:click="bukaOpname({{ $row['id'] }})"
                                            class="so-btn-lihat"
                                        >
                                            Lihat Detail
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-400 italic text-center py-4">
                    Tidak ada riwayat opname untuk filter yang dipilih.
                </p>
            @endif
        </x-filament::section>

    @endif

    {{-- ===========================
    OPNAME AKTIF
    =========================== --}}
    @if ($opname)

        {{-- HEADER INFO --}}
        <x-filament::section>
            <div class="so-header">
                <div class="so-header-left">
                    <div class="so-info-row">
                        <span class="so-label">No Opname</span>
                        <strong class="so-value">{{ $opname->no_opname }}</strong>
                    </div>
                    <div class="so-info-row">
                        <span class="so-label">Toko</span>
                        <strong class="so-value">{{ $opname->toko->nama_toko }}</strong>
                    </div>
                    <div class="so-info-row">
                        <span class="so-label">Tanggal</span>
                        <strong class="so-value">{{ $opname->tanggal_opname->format('d-m-Y') }}</strong>
                    </div>
                </div>
                <div class="so-header-right">
                    @php
                        $badgeColor = match($opname->status) {
                            'draft'     => '#6b7280',
                            'menunggu'  => '#d97706',
                            'disetujui' => '#16a34a',
                            'ditolak'   => '#dc2626',
                            default     => '#6b7280',
                        };
                        $badgeLabel = match($opname->status) {
                            'draft'     => 'Draft',
                            'menunggu'  => 'Menunggu Approval',
                            'disetujui' => 'Disetujui',
                            'ditolak'   => 'Ditolak',
                            default     => $opname->status,
                        };
                    @endphp
                    <span class="so-badge" style="background: {{ $badgeColor }}">
                        {{ $badgeLabel }}
                    </span>
                </div>
            </div>
        </x-filament::section>

        {{-- TABEL DETAIL BARANG --}}
        <x-filament::section heading="Detail Barang">
            <div class="overflow-x-auto">
                <table class="so-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th class="text-center w-28">Stok Sistem</th>
                            <th class="text-center w-32">Stok Aktual</th>
                            <th class="text-center w-24">Selisih</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details as $index => $detail)
                            @php
                                $aktual  = $detail['stok_aktual'] !== '' ? (float) $detail['stok_aktual'] : null;
                                $selisih = $aktual !== null ? $aktual - (float) $detail['stok_sistem'] : null;
                                $rowClass = '';
                                if ($selisih !== null) {
                                    if ($selisih > 0)      $rowClass = 'so-row-lebih';
                                    elseif ($selisih < 0)  $rowClass = 'so-row-kurang';
                                    else                   $rowClass = 'so-row-sesuai';
                                }
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="text-center align-middle text-xs text-gray-500">
                                    {{ $detail['kode'] }}
                                </td>
                                <td class="align-middle font-medium">
                                    {{ $detail['barang'] }}
                                </td>
                                <td class="text-center align-middle font-semibold">
                                    {{ number_format($detail['stok_sistem'], 2, '.', '') }}
                                </td>
                                <td class="text-center align-middle">
                                    @if ($opname->isDraft())
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            pattern="[0-9]+([.,][0-9]{1,2})?"
                                            class="so-input text-center"
                                            wire:model.lazy="details.{{ $index }}.stok_aktual"
                                            onclick="this.select()"
                                            placeholder="-"
                                        >
                                    @else
                                        <span class="font-semibold">
                                            {{ $aktual !== null ? number_format($aktual, 2, '.', '') : '-' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center align-middle font-bold">
                                    @if ($selisih !== null)
                                        <span class="{{ $selisih > 0 ? 'text-green-600' : ($selisih < 0 ? 'text-red-600' : 'text-gray-500') }}">
                                            {{ $selisih > 0 ? '+' : '' }}{{ number_format($selisih, 2, '.', '') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if ($opname->isDraft())
                                        <input
                                            type="text"
                                            class="so-input"
                                            wire:model.defer="details.{{ $index }}.catatan"
                                            placeholder="Opsional"
                                        >
                                    @else
                                        <span class="text-sm text-gray-500">{{ $detail['catatan'] ?: '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- CATATAN APPROVAL (untuk approver saat menunggu) --}}
        @if ($opname->isMenunggu() && auth()->user()->hasAnyRole(['super_admin', 'manager']))
            <x-filament::section heading="Keputusan Approval">
                @if ($opname->catatan)
                    <p class="text-sm text-gray-600 mb-3">
                        <strong>Catatan petugas:</strong> {{ $opname->catatan }}
                    </p>
                @endif
                <textarea
                    wire:model.defer="catatan_approval"
                    class="so-input w-full"
                    rows="3"
                    placeholder="Catatan approval (opsional)..."
                ></textarea>
            </x-filament::section>
        @endif

        {{-- DITOLAK INFO --}}
        @if ($opname->isDitolak())
            <x-filament::section>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <p class="text-red-700 font-semibold">Opname ini ditolak</p>
                    @if ($opname->catatan_approval)
                        <p class="text-red-600 text-sm mt-1">Alasan: {{ $opname->catatan_approval }}</p>
                    @endif
                    @if ($opname->approvedBy)
                        <p class="text-red-500 text-xs mt-1">Oleh: {{ $opname->approvedBy->name }}</p>
                    @endif
                </div>
            </x-filament::section>
        @endif

        {{-- DISETUJUI INFO --}}
        @if ($opname->isDisetujui())
            <x-filament::section>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <p class="text-green-700 font-semibold">✓ Opname disetujui dan stok sudah disesuaikan</p>
                    @if ($opname->approvedBy)
                        <p class="text-green-600 text-sm mt-1">
                            Disetujui oleh {{ $opname->approvedBy->name }}
                            pada {{ $opname->approved_at->format('d-m-Y H:i') }}
                        </p>
                    @endif
                </div>
            </x-filament::section>
        @endif

        {{-- ACTION BUTTONS --}}
        <div class="mt-4 flex flex-wrap gap-3 justify-end">

            {{-- Tombol saat DRAFT --}}
            @if ($opname->isDraft())
                <x-filament::button
                    color="gray"
                    wire:click="simpanProgress"
                    icon="heroicon-o-arrow-down-tray"
                >
                    Simpan Progress
                </x-filament::button>

                <x-filament::button
                    color="warning"
                    wire:click="submitApproval"
                    icon="heroicon-o-paper-airplane"
                    wire:confirm="Yakin submit untuk approval? Data tidak bisa diubah setelah ini."
                >
                    Submit untuk Approval
                </x-filament::button>
            @endif

            {{-- Tombol saat MENUNGGU (hanya manager/super admin) --}}
            @if ($opname->isMenunggu() && auth()->user()->hasAnyRole(['super_admin', 'manager']))
                <x-filament::button
                    color="danger"
                    wire:click="tolak"
                    icon="heroicon-o-x-circle"
                    wire:confirm="Yakin menolak opname ini?"
                >
                    Tolak
                </x-filament::button>

                <x-filament::button
                    color="success"
                    wire:click="approve"
                    icon="heroicon-o-check-circle"
                    wire:confirm="Yakin menyetujui? Stok semua barang akan disesuaikan sesuai hasil hitung fisik."
                >
                    Setujui & Adjust Stok
                </x-filament::button>
            @endif

            {{-- Tombol kembali --}}
            <x-filament::button
                color="gray"
                wire:click="batal"
                icon="heroicon-o-arrow-left"
            >
                {{ $opname->isDisetujui() || $opname->isDitolak() ? 'Kembali' : 'Tutup' }}
            </x-filament::button>

        </div>

    @endif

    {{-- ===========================
    CSS
    =========================== --}}
    <style>
        .so-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }
        .so-header-left { display: flex; flex-direction: column; gap: 6px; }
        .so-info-row { display: flex; gap: 8px; align-items: center; }
        .so-label { font-size: .75rem; color: #6b7280; min-width: 90px; }
        .so-value { font-size: .9rem; }
        .so-badge {
            color: white;
            padding: 4px 14px;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .so-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #9ca3af;
        }
        .so-table th, .so-table td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            font-size: .875rem;
        }
        .so-table thead { background: #e5e7eb; }

        .so-row-lebih    { background: #f0fdf4; }
        .so-row-kurang   { background: #fff1f2; }
        .so-row-sesuai   { background: #f9fafb; }
        .so-row-menunggu { background: #fffbeb; }

        .so-input {
            width: 100%;
            height: 34px;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: white;
            font-size: .875rem;
        }
        .so-input:focus {
            outline: none;
            border-color: rgb(59 130 246);
            box-shadow: 0 0 0 1px rgb(59 130 246 / 40%);
        }

        /* Tombol aksi di daftar opname */
        .so-btn-buka {
            display: inline-flex;
            align-items: center;
            padding: 5px 14px;
            border-radius: 6px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            background: #2563eb;
            color: white;
            border: none;
            transition: background .15s;
        }
        .so-btn-buka:hover { background: #1d4ed8; }

        .so-btn-lihat {
            display: inline-flex;
            align-items: center;
            padding: 5px 14px;
            border-radius: 6px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            background: #6b7280;
            color: white;
            border: none;
            transition: background .15s;
        }
        .so-btn-lihat:hover { background: #4b5563; }

        /* Filter bar */
        .so-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }
        .so-filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .so-filter-label {
            font-size: .75rem;
            color: #6b7280;
            font-weight: 500;
        }
        .so-filter-input {
            height: 36px;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: white;
            font-size: .875rem;
            min-width: 160px;
        }
        .so-filter-input:focus {
            outline: none;
            border-color: rgb(59 130 246);
            box-shadow: 0 0 0 1px rgb(59 130 246 / 40%);
        }
        .so-filter-actions {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }
        .so-btn-filter-primary {
            height: 36px;
            padding: 0 18px;
            border-radius: 6px;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            background: #2563eb;
            color: white;
            border: none;
            transition: background .15s;
        }
        .so-btn-filter-primary:hover { background: #1d4ed8; }

        .so-btn-filter-reset {
            height: 36px;
            padding: 0 18px;
            border-radius: 6px;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
            transition: background .15s;
        }
        .so-btn-filter-reset:hover { background: #f3f4f6; }
    </style>

</x-filament::page>