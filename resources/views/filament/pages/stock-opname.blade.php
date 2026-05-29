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
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Dari Tanggal</label>
                <input
                    type="date"
                    class="h-9 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    wire:model="filterTanggalDari">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Sampai Tanggal</label>
                <input
                    type="date"
                    class="h-9 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    wire:model="filterTanggalSampai">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
                <select class="h-9 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" wire:model="filterStatus">
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="menunggu">Menunggu Approval</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="disetujui">Disetujui</option>
                </select>
            </div>
            <div class="flex gap-2 items-end">
                <button wire:click="terapkanFilter" class="h-9 px-5 rounded-lg text-sm font-semibold bg-primary-600 hover:bg-primary-700 text-white transition-colors duration-150">
                    Terapkan
                </button>
                <button wire:click="resetFilter" class="h-9 px-5 rounded-lg text-sm font-semibold bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
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
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Daftar opname yang sedang dalam proses (draft, menunggu approval, atau ditolak).
        </p>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">No Opname</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Toko</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100">Tanggal</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Dibuat Oleh</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($daftarOpname as $row)
                    @php
                    $badgeClasses = match($row['status']) {
                    'draft' => 'bg-gray-500',
                    'menunggu' => 'bg-amber-500',
                    'ditolak' => 'bg-red-500',
                    default => 'bg-gray-500',
                    };
                    $badgeLabel = match($row['status']) {
                    'draft' => 'Draft',
                    'menunggu' => 'Menunggu Approval',
                    'ditolak' => 'Ditolak',
                    default => $row['status'],
                    };
                    $rowBgClass = $row['status'] === 'menunggu' ? 'bg-amber-50 dark:bg-amber-950/20' : '';
                    @endphp
                    <tr class="{{ $rowBgClass }} hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-150">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-gray-900 dark:text-gray-100">
                            {{ $row['no_opname'] }}
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                            {{ $row['toko'] }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">
                            {{ $row['tanggal'] }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white {{ $badgeClasses }}">
                                {{ $badgeLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $row['created_by'] }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button
                                wire:click="bukaOpname({{ $row['id'] }})"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-primary-600 hover:bg-primary-700 text-white transition-colors duration-150">
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
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Menampilkan maks. 50 opname yang sudah disetujui sesuai filter.
        </p>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">No Opname</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Toko</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100">Tgl Opname</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Dibuat Oleh</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Disetujui Oleh</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100">Tgl Disetujui</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100">Status</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($riwayatOpname as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-150">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-gray-900 dark:text-gray-100">
                            {{ $row['no_opname'] }}
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $row['toko'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $row['tanggal'] }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $row['created_by'] }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $row['approved_by'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $row['approved_at'] }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white bg-green-500">
                                Disetujui
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button
                                wire:click="bukaOpname({{ $row['id'] }})"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-gray-500 hover:bg-gray-600 text-white transition-colors duration-150">
                                Lihat Detail
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-sm text-gray-400 dark:text-gray-500 italic text-center py-4">
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
        <div class="flex justify-between items-start gap-4">
            <div class="flex flex-col gap-1.5">
                <div class="flex gap-2 items-center">
                    <span class="text-xs text-gray-500 dark:text-gray-400 min-w-[90px]">No Opname</span>
                    <strong class="text-sm text-gray-900 dark:text-gray-100">{{ $opname->no_opname }}</strong>
                </div>
                <div class="flex gap-2 items-center">
                    <span class="text-xs text-gray-500 dark:text-gray-400 min-w-[90px]">Toko</span>
                    <strong class="text-sm text-gray-900 dark:text-gray-100">{{ $opname->toko->nama_toko }}</strong>
                </div>
                <div class="flex gap-2 items-center">
                    <span class="text-xs text-gray-500 dark:text-gray-400 min-w-[90px]">Tanggal</span>
                    <strong class="text-sm text-gray-900 dark:text-gray-100">{{ $opname->tanggal_opname->format('d-m-Y') }}</strong>
                </div>
            </div>
            <div>
                @php
                $badgeClasses = match($opname->status) {
                'draft' => 'bg-gray-500',
                'menunggu' => 'bg-amber-500',
                'disetujui' => 'bg-green-500',
                'ditolak' => 'bg-red-500',
                default => 'bg-gray-500',
                };
                $badgeLabel = match($opname->status) {
                'draft' => 'Draft',
                'menunggu' => 'Menunggu Approval',
                'disetujui' => 'Disetujui',
                'ditolak' => 'Ditolak',
                default => $opname->status,
                };
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white {{ $badgeClasses }}">
                    {{ $badgeLabel }}
                </span>
            </div>
        </div>
    </x-filament::section>

    {{-- TABEL DETAIL BARANG --}}
    <x-filament::section heading="Detail Barang">
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Kode</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Nama Barang</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100 w-28">Stok Sistem</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100 w-32">Stok Aktual</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100 w-24">Selisih</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($details as $index => $detail)
                    @php
                    $aktual = $detail['stok_aktual'] !== '' ? (float) $detail['stok_aktual'] : null;
                    $selisih = $aktual !== null ? $aktual - (float) $detail['stok_sistem'] : null;
                    $rowClasses = '';
                    if ($selisih !== null) {
                    if ($selisih > 0) $rowClasses = 'bg-green-50 dark:bg-green-950/20';
                    elseif ($selisih < 0) $rowClasses='bg-red-50 dark:bg-red-950/20' ;
                        else $rowClasses='bg-gray-50 dark:bg-gray-800/50' ;
                        }
                        @endphp
                        <tr class="{{ $rowClasses }} hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-150">
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                            {{ $detail['kode'] }}
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                            {{ $detail['barang'] }}
                        </td>
                        <td class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100">
                            {{ number_format($detail['stok_sistem'], 2, '.', '') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($opname->isDraft())
                            <input
                                type="text"
                                inputmode="decimal"
                                pattern="[0-9]+([.,][0-9]{1,2})?"
                                class="w-28 px-2 py-1.5 text-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                wire:model.lazy="details.{{ $index }}.stok_aktual"
                                onclick="this.select()"
                                placeholder="-">
                            @else
                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ $aktual !== null ? number_format($aktual, 2, '.', '') : '-' }}
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center font-bold">
                            @if ($selisih !== null)
                            <span class="{{ $selisih > 0 ? 'text-green-600 dark:text-green-400' : ($selisih < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400') }}">
                                {{ $selisih > 0 ? '+' : '' }}{{ number_format($selisih, 2, '.', '') }}
                            </span>
                            @else
                            <span class="text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($opname->isDraft())
                            <input
                                type="text"
                                class="w-full px-2 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                wire:model.defer="details.{{ $index }}.catatan"
                                placeholder="Opsional">
                            @else
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $detail['catatan'] ?: '-' }}</span>
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
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
            <strong class="text-gray-900 dark:text-gray-100">Catatan petugas:</strong> {{ $opname->catatan }}
        </p>
        @endif
        <textarea
            wire:model.defer="catatan_approval"
            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            rows="3"
            placeholder="Catatan approval (opsional)..."></textarea>
    </x-filament::section>
    @endif

    {{-- DITOLAK INFO --}}
    @if ($opname->isDitolak())
    <x-filament::section>
        <div class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-red-700 dark:text-red-400 font-semibold">Opname ini ditolak</p>
            @if ($opname->catatan_approval)
            <p class="text-red-600 dark:text-red-300 text-sm mt-1">Alasan: {{ $opname->catatan_approval }}</p>
            @endif
            @if ($opname->approvedBy)
            <p class="text-red-500 dark:text-red-400 text-xs mt-1">Oleh: {{ $opname->approvedBy->name }}</p>
            @endif
        </div>
    </x-filament::section>
    @endif

    {{-- DISETUJUI INFO --}}
    @if ($opname->isDisetujui())
    <x-filament::section>
        <div class="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <p class="text-green-700 dark:text-green-400 font-semibold">✓ Opname disetujui dan stok sudah disesuaikan</p>
            @if ($opname->approvedBy)
            <p class="text-green-600 dark:text-green-300 text-sm mt-1">
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
            icon="heroicon-o-arrow-down-tray">
            Simpan Progress
        </x-filament::button>

        <x-filament::button
            color="warning"
            wire:click="submitApproval"
            icon="heroicon-o-paper-airplane"
            wire:confirm="Yakin submit untuk approval? Data tidak bisa diubah setelah ini.">
            Submit untuk Approval
        </x-filament::button>
        @endif

        {{-- Tombol saat MENUNGGU (hanya manager/super admin) --}}
        @if ($opname->isMenunggu() && auth()->user()->hasAnyRole(['super_admin', 'manager']))
        <x-filament::button
            color="danger"
            wire:click="tolak"
            icon="heroicon-o-x-circle"
            wire:confirm="Yakin menolak opname ini?">
            Tolak
        </x-filament::button>

        <x-filament::button
            color="success"
            wire:click="approve"
            icon="heroicon-o-check-circle"
            wire:confirm="Yakin menyetujui? Stok semua barang akan disesuaikan sesuai hasil hitung fisik.">
            Setujui & Adjust Stok
        </x-filament::button>
        @endif

        {{-- Tombol kembali --}}
        <x-filament::button
            color="gray"
            wire:click="batal"
            icon="heroicon-o-arrow-left">
            {{ $opname->isDisetujui() || $opname->isDitolak() ? 'Kembali' : 'Tutup' }}
        </x-filament::button>

    </div>

    @endif

</x-filament::page>