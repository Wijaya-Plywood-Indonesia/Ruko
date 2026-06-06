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

        /* Native date input styling */
        .pp-date-input {
            display: block;
            border-radius: 0.5rem;
            border: none;
            background-color: white;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: rgb(17 24 39);
            box-shadow: inset 0 0 0 1px rgb(209 213 219);
            cursor: pointer;
            min-width: 10rem;
        }

        .pp-date-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgb(var(--primary-600)), inset 0 0 0 1px rgb(var(--primary-600));
        }

        .dark .pp-date-input {
            background-color: rgb(255 255 255 / 0.05);
            color: white;
            box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.1);
            color-scheme: dark;
        }

        .dark .pp-date-input:focus {
            box-shadow: 0 0 0 2px rgb(var(--primary-500)), inset 0 0 0 1px rgb(var(--primary-500));
        }

        .cell-input {
            width: 100%;
            height: 2.25rem;
            padding: 0 0.5rem;
            text-align: center;
            font-size: 0.875rem;
            font-weight: 600;
            background-color: white;
            border: 1.5px solid rgb(var(--primary-200));
            border-radius: 0.375rem;
            transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
            color: inherit;
            /* Bayangan tipis agar terasa seperti "form field" sungguhan */
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }

        .dark .cell-input {
            background-color: rgb(var(--primary-950) / 0.5);
            border-color: rgb(var(--primary-700));
            color: white;
        }

        .cell-input:focus {
            outline: none;
            border-color: rgb(var(--primary-500));
            background-color: rgb(var(--primary-50));
            box-shadow: 0 0 0 3px rgb(var(--primary-500) / 0.15);
        }

        .dark .cell-input:focus {
            background-color: rgb(var(--primary-950));
        }

        .cell-input:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: rgb(243 244 246);
        }

        .dark .cell-input:disabled {
            background-color: rgb(255 255 255 / 0.03);
        }
    </style>

    <div class="flex flex-col gap-6">

        {{-- ── 1. FILTER TANGGAL ──────────────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-4">
                <div class="flex flex-wrap items-end justify-between gap-4">

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Pilih Tanggal Laporan
                        </label>
                        <input
                            type="date"
                            wire:model.live="selectedDate"
                            max="{{ now()->toDateString() }}"
                            class="pp-date-input" />
                    </div>

                    @if($currentRecord)
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            Dibuat oleh:
                            <strong class="text-gray-700 dark:text-gray-200">
                                {{ $currentRecord->created_by ?? '-' }}
                            </strong>
                        </span>

                        @if($isLocked)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5 shrink-0">
                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                            </svg>
                            Divalidasi oleh {{ $currentRecord->validated_by }}
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-400 dark:ring-amber-400/20">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5 shrink-0">
                                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" />
                            </svg>
                            Menunggu Validasi
                        </span>
                        @endif
                    </div>
                    @endif

                    <div class="flex shrink-0 items-center gap-2">

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
                        <div class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 shrink-0">
                                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
                            </svg>
                            TERKUNCI
                        </div>
                        @endif

                    </div>

                </div>
            </div>
        </div>

        @if(empty($mentahState) && empty($campuranState))

        {{-- ── 2. EMPTY STATE ────────────────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content flex flex-col items-center justify-center gap-3 px-4 py-16 text-center">
                <div class="rounded-full bg-gray-100 p-4 dark:bg-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-10 w-10 text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5M12 12.75h.008v.008H12v-.008Zm0 3h.008v.008H12v-.008Zm-3 0h.008v.008H9v-.008Zm6 0h.008v.008H15v-.008Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">
                        Tidak ada data produksi untuk
                        <span class="text-primary-600 dark:text-primary-400">
                            {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}
                        </span>
                    </p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        Silahkan pilih tanggal yang berbeda.
                    </p>
                </div>
            </div>
        </div>

        @else

        {{-- ── 3a. TABEL BAHAN BAKU MENTAH ────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            <header class="fi-section-header flex items-center gap-3 overflow-hidden px-6 py-4">
                <div class="grid flex-1 gap-1">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Produksi Pakan Mentah
                    </h3>
                </div>
            </header>

            <div class="fi-section-content border-t border-gray-200 dark:border-white/10">
                <div class="pp-table-wrapper pp-table">
                    <table>
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama Bahan</th>
                                <th class="w-20 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Satuan</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Stok Awal</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-green-600 dark:text-green-400">Masuk</th>

                                {{-- Pullet --}}
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide">
                                    @if($canEdit)
                                    <button
                                        wire:click="autoFillKolom('p')"
                                        wire:loading.attr="disabled"
                                        wire:target="autoFillKolom('p')"
                                        title="Klik untuk isi otomatis kolom Pullet dari komposisi"
                                        class="group inline-flex items-center gap-1 text-primary-600 dark:text-primary-400 hover:text-primary-500 transition-colors">
                                        {{-- Ikon AI (sparkles) --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                            class="h-3.5 w-3.5 opacity-60 group-hover:opacity-100 transition-opacity"
                                            wire:loading.class="animate-spin" wire:target="autoFillKolom('p')">
                                            <path fill-rule="evenodd" d="M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5ZM18 1.5a.75.75 0 0 1 .728.568l.258 1.036a2.63 2.63 0 0 0 1.91 1.91l1.036.258a.75.75 0 0 1 0 1.456l-1.036.258a2.63 2.63 0 0 0-1.91 1.91l-.258 1.036a.75.75 0 0 1-1.456 0l-.258-1.036a2.63 2.63 0 0 0-1.91-1.91l-1.036-.258a.75.75 0 0 1 0-1.456l1.036-.258a2.63 2.63 0 0 0 1.91-1.91l.258-1.036A.75.75 0 0 1 18 1.5Z" clip-rule="evenodd" />
                                        </svg>
                                        <span wire:loading.remove wire:target="autoFillKolom('p')">Pullet</span>
                                        <span wire:loading wire:target="autoFillKolom('p')">...</span>
                                    </button>
                                    @else
                                    <span class="text-primary-600 dark:text-primary-400">Pullet</span>
                                    @endif
                                </th>

                                {{-- Layer 1 --}}
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide">
                                    @if($canEdit)
                                    <button
                                        wire:click="autoFillKolom('l1')"
                                        wire:loading.attr="disabled"
                                        wire:target="autoFillKolom('l1')"
                                        title="Klik untuk isi otomatis kolom Layer 1 dari komposisi"
                                        class="group inline-flex items-center gap-1 text-primary-600 dark:text-primary-400 hover:text-primary-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                            class="h-3.5 w-3.5 opacity-60 group-hover:opacity-100 transition-opacity"
                                            wire:loading.class="animate-spin" wire:target="autoFillKolom('l1')">
                                            <path fill-rule="evenodd" d="M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5ZM18 1.5a.75.75 0 0 1 .728.568l.258 1.036a2.63 2.63 0 0 0 1.91 1.91l1.036.258a.75.75 0 0 1 0 1.456l-1.036.258a2.63 2.63 0 0 0-1.91 1.91l-.258 1.036a.75.75 0 0 1-1.456 0l-.258-1.036a2.63 2.63 0 0 0-1.91-1.91l-1.036-.258a.75.75 0 0 1 0-1.456l1.036-.258a2.63 2.63 0 0 0 1.91-1.91l.258-1.036A.75.75 0 0 1 18 1.5Z" clip-rule="evenodd" />
                                        </svg>
                                        <span wire:loading.remove wire:target="autoFillKolom('l1')">Layer 1</span>
                                        <span wire:loading wire:target="autoFillKolom('l1')">...</span>
                                    </button>
                                    @else
                                    <span class="text-primary-600 dark:text-primary-400">Layer 1</span>
                                    @endif
                                </th>

                                {{-- Layer 2 --}}
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide">
                                    @if($canEdit)
                                    <button
                                        wire:click="autoFillKolom('l2')"
                                        wire:loading.attr="disabled"
                                        wire:target="autoFillKolom('l2')"
                                        title="Klik untuk isi otomatis kolom Layer 2 dari komposisi"
                                        class="group inline-flex items-center gap-1 text-primary-600 dark:text-primary-400 hover:text-primary-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                            class="h-3.5 w-3.5 opacity-60 group-hover:opacity-100 transition-opacity"
                                            wire:loading.class="animate-spin" wire:target="autoFillKolom('l2')">
                                            <path fill-rule="evenodd" d="M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5ZM18 1.5a.75.75 0 0 1 .728.568l.258 1.036a2.63 2.63 0 0 0 1.91 1.91l1.036.258a.75.75 0 0 1 0 1.456l-1.036.258a2.63 2.63 0 0 0-1.91 1.91l-.258 1.036a.75.75 0 0 1-1.456 0l-.258-1.036a2.63 2.63 0 0 0-1.91-1.91l-1.036-.258a.75.75 0 0 1 0-1.456l1.036-.258a2.63 2.63 0 0 0 1.91-1.91l.258-1.036A.75.75 0 0 1 18 1.5Z" clip-rule="evenodd" />
                                        </svg>
                                        <span wire:loading.remove wire:target="autoFillKolom('l2')">Layer 2</span>
                                        <span wire:loading wire:target="autoFillKolom('l2')">...</span>
                                    </button>
                                    @else
                                    <span class="text-primary-600 dark:text-primary-400">Layer 2</span>
                                    @endif
                                </th>

                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Sisa Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse($mentahState as $idx => $item)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $item['nama'] }}
                                </td>
                                <td class="px-4 py-2.5 text-center text-xs text-gray-400 dark:text-gray-500">
                                    {{ $item['satuan'] ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ number_format($item['awal']) }}
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <input
                                        type="number"
                                        wire:model.lazy="mentahState.{{ $idx }}.masuk"
                                        {{ !$canEdit ? 'disabled' : '' }}
                                        min="0"
                                        step="any"
                                        placeholder="0"
                                        class="inline-block w-24 rounded-md bg-green-50 px-2.5 py-1 text-sm font-semibold text-green-700 text-center border-0 focus:ring-2 focus:ring-green-500 focus:bg-green-100 dark:bg-green-400/10 dark:text-green-400 dark:focus:ring-green-400 dark:focus:bg-green-400/20 disabled:opacity-50 disabled:cursor-not-allowed" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" wire:model.lazy="mentahState.{{ $idx }}.p_sak" {{ !$canEdit ? 'disabled' : '' }} class="cell-input" min="0" step="any" placeholder="0" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" wire:model.lazy="mentahState.{{ $idx }}.l1_sak" {{ !$canEdit ? 'disabled' : '' }} class="cell-input" min="0" step="any" placeholder="0" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" wire:model.lazy="mentahState.{{ $idx }}.l2_sak" {{ !$canEdit ? 'disabled' : '' }} class="cell-input" min="0" step="any" placeholder="0" />
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-block rounded-md bg-amber-50 px-2.5 py-1 text-sm font-bold text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
                                        {{ number_format($item['akhir']) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
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
            <header class="fi-section-header flex items-center gap-3 overflow-hidden px-6 py-4">
                <div class="grid flex-1 gap-1">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Produksi Pakan Campuran
                    </h3>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-600 ring-1 ring-inset ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3 w-3">
                        <path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 0 1 .359.852L12.982 9.75h7.268a.75.75 0 0 1 .548 1.262l-10.5 11.25a.75.75 0 0 1-1.272-.71l1.992-7.302H3.818a.75.75 0 0 1-.548-1.262l10.5-11.25a.75.75 0 0 1 .845-.143Z" clip-rule="evenodd" />
                    </svg>
                    Auto-Sync
                </span>
            </header>

            <div class="fi-section-content border-t border-gray-200 dark:border-white/10">
                <div class="pp-table-wrapper pp-table">
                    <table>
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama Bahan</th>
                                <th class="w-20 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Satuan</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Stok Awal</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-green-600 dark:text-green-400">Masuk</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Keluar PLT</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Keluar L1</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Keluar L2</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Sisa Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse($campuranState as $idx => $item)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $item['nama'] }}
                                </td>
                                <td class="px-4 py-2.5 text-center text-xs text-gray-400 dark:text-gray-500">
                                    {{ $item['satuan'] ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ number_format($item['awal']) }}
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-block rounded-md bg-green-50 px-2.5 py-1 text-sm font-semibold text-green-700 dark:bg-green-400/10 dark:text-green-400">
                                        {{ number_format($item['masuk']) }}
                                    </span>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" wire:model.lazy="campuranState.{{ $idx }}.p" {{ !$canEdit ? 'disabled' : '' }} class="cell-input" min="0" step="any" placeholder="0" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" wire:model.lazy="campuranState.{{ $idx }}.l1" {{ !$canEdit ? 'disabled' : '' }} class="cell-input" min="0" step="any" placeholder="0" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" wire:model.lazy="campuranState.{{ $idx }}.l2" {{ !$canEdit ? 'disabled' : '' }} class="cell-input" min="0" step="any" placeholder="0" />
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-block rounded-md bg-amber-50 px-2.5 py-1 text-sm font-bold text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
                                        {{ number_format($item['akhir']) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                    Tidak ada data pakan campuran.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        {{-- ── 3d. KETERANGAN & AUDIT ──────────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            <header class="fi-section-header flex items-center gap-3 overflow-hidden px-6 py-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0 text-primary-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <div class="grid flex-1 gap-1">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Keterangan & Audit
                    </h3>
                </div>
            </header>

            <div class="fi-section-content border-t border-gray-200 p-6 dark:border-white/10">
                <div class="grid grid-cols-1 gap-6">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                        <div class="col-span-2">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Catatan Produksi
                            </label>
                            <textarea
                                wire:model.live.debounce.500ms="keterangan"
                                {{ !$canEdit ? 'disabled' : '' }}
                                rows="5"
                                placeholder="Tuliskan catatan produksi, kendala, atau informasi tambahan harian di sini..."
                                class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm leading-6 text-gray-900 shadow-sm placeholder:text-gray-400 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500 dark:ring-white/10 dark:focus:ring-primary-500"></textarea>
                        </div>

                        <div class="flex flex-col gap-3">
                            <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-inset ring-gray-200 dark:bg-white/5 dark:ring-white/10">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                    Status Verifikasi
                                </p>
                                @if($isLocked)
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 shrink-0 text-green-500 dark:text-green-400">
                                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-green-600 dark:text-green-400">{{ $currentRecord->validated_by }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ optional($currentRecord->updated_at)->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                                @else
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 shrink-0 text-amber-500 dark:text-amber-400">
                                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="text-sm font-semibold text-amber-600 dark:text-amber-400">Menunggu Validasi</p>
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