<x-filament-panels::page>

    <style>
        /* Hilangkan spinner pada input number */
        .pp-table input[type="number"] {
            -moz-appearance: textfield;
            appearance: textfield;
        }

        .pp-table input[type="number"]::-webkit-outer-spin-button,
        .pp-table input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Input dalam sel tabel */
        .pp-table .cell-input {
            width: 100%;
            height: 2.25rem;
            padding: 0 0.5rem;
            text-align: center;
            font-size: 0.875rem;
            font-weight: 500;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 0.375rem;
            transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
            color: inherit;
        }

        .pp-table .cell-input:focus {
            outline: none;
            border-color: rgb(var(--primary-500));
            background-color: rgb(var(--primary-50));
            box-shadow: 0 0 0 3px rgb(var(--primary-500) / 0.1);
        }

        .dark .pp-table .cell-input:focus {
            background-color: rgb(var(--primary-950));
        }

        .pp-table .cell-input:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Scroll horizontal tabel di mobile */
        .pp-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .pp-table table {
            min-width: 600px;
            width: 100%;
            border-collapse: collapse;
        }

        /* Badge status */
        .pp-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            border-radius: 9999px;
            padding: 0.25rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .pp-badge-success {
            background-color: rgb(240 253 244);
            color: rgb(21 128 61);
            box-shadow: inset 0 0 0 1px rgb(22 163 74 / 0.2);
        }

        .pp-badge-warning {
            background-color: rgb(255 251 235);
            color: rgb(180 83 9);
            box-shadow: inset 0 0 0 1px rgb(217 119 6 / 0.2);
        }

        .dark .pp-badge-success {
            background-color: rgb(21 128 61 / 0.1);
            color: rgb(74 222 128);
            box-shadow: inset 0 0 0 1px rgb(74 222 128 / 0.2);
        }

        .dark .pp-badge-warning {
            background-color: rgb(180 83 9 / 0.1);
            color: rgb(251 191 36);
            box-shadow: inset 0 0 0 1px rgb(251 191 36 / 0.2);
        }

        /* Chip nilai akhir */
        .pp-chip-warning {
            display: inline-block;
            border-radius: 0.375rem;
            background-color: rgb(255 251 235);
            padding: 0.25rem 0.625rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: rgb(180 83 9);
        }

        .pp-chip-success {
            display: inline-block;
            border-radius: 0.375rem;
            background-color: rgb(240 253 244);
            padding: 0.25rem 0.625rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: rgb(21 128 61);
        }

        .dark .pp-chip-warning {
            background-color: rgb(180 83 9 / 0.1);
            color: rgb(251 191 36);
        }

        .dark .pp-chip-success {
            background-color: rgb(21 128 61 / 0.1);
            color: rgb(74 222 128);
        }

        /* Audit card */
        .pp-audit-card {
            border-radius: 0.5rem;
            background-color: rgb(249 250 251);
            padding: 1rem;
            box-shadow: inset 0 0 0 1px rgb(229 231 235);
        }

        .dark .pp-audit-card {
            background-color: rgb(255 255 255 / 0.05);
            box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.1);
        }

        /* Tombol terkunci */
        .pp-locked-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 0.5rem;
            background-color: rgb(243 244 246);
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgb(107 114 128);
        }

        .dark .pp-locked-badge {
            background-color: rgb(55 65 81);
            color: rgb(156 163 175);
        }

        /* Date input styling */
        .pp-date-input {
            display: block;
            border-radius: 0.5rem;
            border: none;
            background-color: white;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            color: rgb(17 24 39);
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05), inset 0 0 0 1px rgb(209 213 219);
        }

        .pp-date-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgb(var(--primary-600)), inset 0 0 0 1px rgb(var(--primary-600));
        }

        .dark .pp-date-input {
            background-color: rgb(255 255 255 / 0.05);
            color: white;
            box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.1);
        }

        /* Textarea styling */
        .pp-textarea {
            display: block;
            width: 100%;
            border-radius: 0.5rem;
            border: none;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            color: rgb(17 24 39);
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05), inset 0 0 0 1px rgb(209 213 219);
            line-height: 1.5rem;
        }

        .pp-textarea::placeholder {
            color: rgb(156 163 175);
        }

        .pp-textarea:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgb(var(--primary-600) / 0.3), inset 0 0 0 1px rgb(var(--primary-600));
        }

        .pp-textarea:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .dark .pp-textarea {
            background-color: rgb(255 255 255 / 0.05);
            color: white;
            box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.1);
        }

        .dark .pp-textarea::placeholder {
            color: rgb(107 114 128);
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- ── 1. FILTER TANGGAL ──────────────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content" style="padding: 1rem;">
                <div style="display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem;">

                    <div>
                        <label style="display: block; margin-bottom: 0.375rem; font-size: 0.875rem; font-weight: 500; color: rgb(55 65 81);" class="dark:text-gray-300">
                            Pilih Tanggal Laporan
                        </label>
                        <input
                            type="date"
                            wire:model.live="selectedDate"
                            max="{{ now()->format('Y-m-d') }}"
                            class="pp-date-input" />
                    </div>

                    @if($currentRecord)
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem;">
                        <span style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.875rem; color: rgb(107 114 128);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            Dibuat oleh:
                            <strong style="color: rgb(55 65 81);" class="dark:text-gray-200">
                                {{ $currentRecord->created_by ?? '-' }}
                            </strong>
                        </span>

                        @if($isLocked)
                        <span class="pp-badge pp-badge-success">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 0.875rem; height: 0.875rem; flex-shrink: 0;">
                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                            </svg>
                            Divalidasi oleh {{ $currentRecord->validated_by }}
                        </span>
                        @else
                        <span class="pp-badge pp-badge-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 0.875rem; height: 0.875rem; flex-shrink: 0;">
                                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" />
                            </svg>
                            Menunggu Validasi
                        </span>
                        @endif
                    </div>
                    @endif

                    {{-- ════════════════════════════════════════════════════════
                         PERUBAHAN #1: Tombol aksi dikontrol flag dari PHP,
                         bukan logika di blade. Tiga flag:
                           $showSaveButton     → tampilkan Simpan Draft
                           $showValidateButton → tampilkan Validasi & Kunci
                           $isLocked           → tampilkan badge TERKUNCI
                         ════════════════════════════════════════════════════════ --}}
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">

                        @if($showSaveButton)
                        <x-filament::button wire:click="save" icon="heroicon-m-arrow-down-on-square-stack" color="gray" size="sm">
                            Simpan Draft
                        </x-filament::button>
                        @endif

                        @if($showValidateButton)
                        <x-filament::button wire:click="validateData" color="success" icon="heroicon-m-check-badge" size="sm" wire:confirm="Validasi akan mengunci data secara permanen. Lanjutkan?">
                            Validasi & Kunci
                        </x-filament::button>
                        @endif

                        @if($isLocked)
                        <div class="pp-locked-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0;">
                                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
                            </svg>
                            TERKUNCI
                        </div>
                        @endif

                    </div>

                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════════════════
             PERUBAHAN #2: Kondisi empty state.
             Sebelum : @if(! $currentRecord)
             Sesudah : @if(empty($mentahState) && empty($campuranState))

             Alasan: kita sekarang mengisi mentahState & campuranState dari
             data Barang meskipun record DB belum ada, sehingga form tetap
             tampil untuk tanggal baru. Empty state hanya muncul jika
             benar-benar tidak ada barang pakan di sistem.
             ════════════════════════════════════════════════════════════════════ --}}
        @if(empty($mentahState) && empty($campuranState))

        {{-- ── 2. EMPTY STATE ────────────────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem; padding: 4rem 1rem; text-align: center;">
                <div style="border-radius: 9999px; background-color: rgb(243 244 246); padding: 1rem;" class="dark:bg-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 2.5rem; height: 2.5rem; color: rgb(156 163 175);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5M12 12.75h.008v.008H12v-.008Zm0 3h.008v.008H12v-.008Zm-3 0h.008v.008H9v-.008Zm6 0h.008v.008H15v-.008Z" />
                    </svg>
                </div>
                <div>
                    <p style="font-size: 0.875rem; font-weight: 600; color: rgb(75 85 99);" class="dark:text-gray-300">
                        Tidak ada data produksi untuk
                        <span style="color: rgb(var(--primary-600));">
                            {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}
                        </span>
                    </p>
                    <p style="margin-top: 0.25rem; font-size: 0.75rem; color: rgb(156 163 175);">
                        Silahkan pilih tanggal yang berbeda.
                    </p>
                </div>
            </div>
        </div>

        @else

        {{-- ── 3a. TABEL BAHAN BAKU MENTAH ────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            <header class="fi-section-header" style="display: flex; align-items: center; gap: 0.75rem; overflow: hidden; padding: 1rem 1.5rem;">
                <div style="display: grid; flex: 1; gap: 0.25rem;">
                    <h3 style="font-size: 1rem; font-weight: 600; line-height: 1.5rem; color: rgb(3 7 18);" class="dark:text-white">
                        Produksi Pakan Mentah
                    </h3>
                </div>
            </header>

            <div class="fi-section-content" style="border-top: 1px solid rgb(229 231 235);" class="dark:border-white/10">
                <div class="pp-table-wrapper pp-table">
                    <table>
                        <thead style="background-color: rgb(249 250 251);" class="dark:bg-white/5">
                            <tr>
                                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(107 114 128);">Nama Bahan</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(107 114 128); width: 7rem;">Stok Awal</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(var(--primary-600)); width: 7rem;">Pullet</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(var(--primary-600)); width: 7rem;">Layer 1</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(var(--primary-600)); width: 7rem;">Layer 2</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(217 119 6); width: 7rem;">Sisa Akhir</th>
                            </tr>
                        </thead>
                        <tbody style="border-top: 1px solid rgb(243 244 246);" class="dark:divide-white/5">
                            @forelse($mentahState as $idx => $item)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                                <td style="padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 500; color: rgb(17 24 39);" class="dark:text-gray-100">
                                    {{ $item['nama'] }}
                                    {{-- nama sudah berformat "Jagung (Sak)" dari PHP --}}
                                </td>
                                <td style="padding: 0.625rem 1rem; text-align: center; font-size: 0.875rem; color: rgb(107 114 128);">
                                    {{ number_format($item['awal']) }}
                                </td>
                                {{-- ════════════════════════════════════════════
                                     PERUBAHAN #3, #4, #5: disabled dikontrol
                                     $canEdit (dari PHP), bukan $isLocked.
                                     Ini yang memungkinkan:
                                       - validator tetap bisa edit walau draft sudah ada
                                       - creator terkunci setelah simpan (meski belum divalidasi)
                                       - super_admin selalu bisa edit
                                     ════════════════════════════════════════════ --}}
                                <td style="padding: 0.375rem 0.5rem;">
                                    <input type="number" wire:model.lazy="mentahState.{{ $idx }}.p" {{ !$canEdit ? 'disabled' : '' }} class="cell-input" min="0" step="any" placeholder="0" />
                                </td>
                                <td style="padding: 0.375rem 0.5rem;">
                                    <input type="number" wire:model.lazy="mentahState.{{ $idx }}.l1" {{ !$canEdit ? 'disabled' : '' }} class="cell-input" min="0" step="any" placeholder="0" />
                                </td>
                                <td style="padding: 0.375rem 0.5rem;">
                                    <input type="number" wire:model.lazy="mentahState.{{ $idx }}.l2" {{ !$canEdit ? 'disabled' : '' }} class="cell-input" min="0" step="any" placeholder="0" />
                                </td>
                                <td style="padding: 0.625rem 1rem; text-align: center;">
                                    <span class="pp-chip-warning">{{ number_format($item['akhir']) }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="padding: 2rem 1rem; text-align: center; font-size: 0.875rem; color: rgb(156 163 175);">
                                    Tidak ada data bahan baku.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── 3b. TABEL HASIL CAMPURAN ────────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            <header class="fi-section-header" style="display: flex; align-items: center; gap: 0.75rem; overflow: hidden; padding: 1rem 1.5rem;">
                <div style="display: grid; flex: 1; gap: 0.25rem;">
                    <h3 style="font-size: 1rem; font-weight: 600; line-height: 1.5rem; color: rgb(3 7 18);" class="dark:text-white">
                        Produksi Pakan Campuran
                    </h3>
                </div>
                <span style="display: inline-flex; align-items: center; gap: 0.25rem; border-radius: 9999px; background-color: rgb(240 253 244); padding: 0.125rem 0.5rem; font-size: 0.75rem; font-weight: 600; color: rgb(22 163 74); box-shadow: inset 0 0 0 1px rgb(22 163 74 / 0.2);" class="dark:bg-success-400/10 dark:text-success-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 0.75rem; height: 0.75rem;">
                        <path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 0 1 .359.852L12.982 9.75h7.268a.75.75 0 0 1 .548 1.262l-10.5 11.25a.75.75 0 0 1-1.272-.71l1.992-7.302H3.818a.75.75 0 0 1-.548-1.262l10.5-11.25a.75.75 0 0 1 .845-.143Z" clip-rule="evenodd" />
                    </svg>
                    Auto-Sync
                </span>
            </header>

            <div class="fi-section-content" style="border-top: 1px solid rgb(229 231 235);">
                <div class="pp-table-wrapper pp-table">
                    <table>
                        <thead style="background-color: rgb(249 250 251);" class="dark:bg-white/5">
                            <tr>
                                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(107 114 128);">Nama Bahan</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(107 114 128); width: 7rem;">Stok Awal</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(22 163 74); width: 7rem;">Masuk</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(var(--primary-600)); width: 7rem;">Pulet</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(var(--primary-600)); width: 7rem;">Layer 1</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(var(--primary-600)); width: 7rem;">Layer 2</th>
                                <th style="padding: 0.75rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(217 119 6); width: 7rem;">Sisa Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="dark:divide-white/5">
                            @forelse($campuranState as $idx => $item)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                                <td style="padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 500; color: rgb(17 24 39);" class="dark:text-gray-100">
                                    {{ $item['nama'] }}
                                </td>
                                <td style="padding: 0.625rem 1rem; text-align: center; font-size: 0.875rem; color: rgb(107 114 128);">
                                    {{ number_format($item['awal']) }}
                                </td>
                                <td style="padding: 0.625rem 1rem; text-align: center;">
                                    <span class="pp-chip-success">{{ number_format($item['masuk']) }}</span>
                                </td>
                                <td style="padding: 0.375rem 0.5rem;">
                                    <input type="number" wire:model.lazy="campuranState.{{ $idx }}.p" {{ !$canEdit ? 'disabled' : '' }} class="cell-input" min="0" step="any" placeholder="0" />
                                </td>
                                <td style="padding: 0.375rem 0.5rem;">
                                    <input type="number" wire:model.lazy="campuranState.{{ $idx }}.l1" {{ !$canEdit ? 'disabled' : '' }} class="cell-input" min="0" step="any" placeholder="0" />
                                </td>
                                <td style="padding: 0.375rem 0.5rem;">
                                    <input type="number" wire:model.lazy="campuranState.{{ $idx }}.l2" {{ !$canEdit ? 'disabled' : '' }} class="cell-input" min="0" step="any" placeholder="0" />
                                </td>
                                <td style="padding: 0.625rem 1rem; text-align: center;">
                                    <span class="pp-chip-warning">{{ number_format($item['akhir']) }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="padding: 2rem 1rem; text-align: center; font-size: 0.875rem; color: rgb(156 163 175);">
                                    Tidak ada data pakan campuran.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── 3c. KETERANGAN & AUDIT ──────────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            <header class="fi-section-header" style="display: flex; align-items: center; gap: 0.75rem; overflow: hidden; padding: 1rem 1.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.25rem; height: 1.25rem; color: rgb(var(--primary-500)); flex-shrink: 0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <div style="display: grid; flex: 1; gap: 0.25rem;">
                    <h3 style="font-size: 1rem; font-weight: 600; line-height: 1.5rem; color: rgb(3 7 18);" class="dark:text-white">
                        Keterangan & Audit
                    </h3>
                </div>
            </header>

            <div class="fi-section-content" style="border-top: 1px solid rgb(229 231 235); padding: 1.5rem;">
                <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
                    <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;" class="lg:grid-cols-3">

                        <div style="grid-column: span 2;">
                            <label style="display: block; margin-bottom: 0.375rem; font-size: 0.875rem; font-weight: 500; color: rgb(55 65 81);" class="dark:text-gray-300">
                                Catatan Produksi
                            </label>
                            {{-- PERUBAHAN #6 (textarea): disabled dikontrol $canEdit --}}
                            <textarea
                                wire:model.live.debounce.500ms="keterangan"
                                {{ !$canEdit ? 'disabled' : '' }}
                                rows="5"
                                placeholder="Tuliskan catatan produksi, kendala, atau informasi tambahan harian di sini..."
                                class="pp-textarea"></textarea>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">

                            <div class="pp-audit-card">
                                <p style="margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(156 163 175);">
                                    Status Verifikasi
                                </p>
                                @if($isLocked)
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; color: rgb(34 197 94);">
                                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p style="font-size: 0.875rem; font-weight: 600; color: rgb(22 163 74);">{{ $currentRecord->validated_by }}</p>
                                        <p style="font-size: 0.75rem; color: rgb(156 163 175);">{{ optional($currentRecord->updated_at)->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                                @else
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; color: rgb(245 158 11);">
                                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" />
                                    </svg>
                                    <p style="font-size: 0.875rem; font-weight: 600; color: rgb(217 119 6);">Menunggu Validasi</p>
                                </div>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        @endif
    </div>

    <x-filament-actions::modals />

</x-filament-panels::page>