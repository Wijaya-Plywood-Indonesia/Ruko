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

        /* ── Flatpickr custom styling ── */
        .pp-flatpickr {
            display: block;
            width: 100%;
            min-width: 10rem;
            border-radius: 0.5rem;
            border: none;
            background-color: white;
            padding: 0.5rem 0.75rem 0.5rem 2.25rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: rgb(17 24 39);
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            ring-width: 1px;
            box-shadow: inset 0 0 0 1px rgb(209 213 219);
            cursor: pointer;
        }

        .pp-flatpickr-wrap {
            position: relative;
            display: inline-block;
        }

        .pp-flatpickr-wrap .pp-cal-icon {
            position: absolute;
            left: 0.625rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: rgb(156 163 175);
            width: 1rem;
            height: 1rem;
        }

        .pp-flatpickr:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgb(var(--primary-600)), inset 0 0 0 1px rgb(var(--primary-600));
        }

        /* Dark mode override untuk Flatpickr calendar popup */
        .dark .flatpickr-calendar {
            background: rgb(31 41 55);
            border-color: rgb(55 65 81);
            box-shadow: 0 4px 24px 0 rgb(0 0 0 / 0.5);
            color: rgb(243 244 246);
        }

        .dark .flatpickr-calendar.arrowTop::before,
        .dark .flatpickr-calendar.arrowTop::after {
            border-bottom-color: rgb(31 41 55);
        }

        .dark .flatpickr-calendar.arrowBottom::before,
        .dark .flatpickr-calendar.arrowBottom::after {
            border-top-color: rgb(31 41 55);
        }

        .dark .flatpickr-months {
            background: rgb(17 24 39);
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .dark .flatpickr-months .flatpickr-month,
        .dark .flatpickr-current-month,
        .dark .flatpickr-current-month .cur-month,
        .dark .flatpickr-current-month input.cur-year {
            color: rgb(243 244 246);
            fill: rgb(243 244 246);
        }

        .dark .flatpickr-months .flatpickr-prev-month,
        .dark .flatpickr-months .flatpickr-next-month {
            color: rgb(156 163 175);
            fill: rgb(156 163 175);
        }

        .dark .flatpickr-months .flatpickr-prev-month:hover,
        .dark .flatpickr-months .flatpickr-next-month:hover {
            color: rgb(243 244 246);
            fill: rgb(243 244 246);
        }

        .dark .flatpickr-weekdays,
        .dark span.flatpickr-weekday {
            background: rgb(17 24 39);
            color: rgb(107 114 128);
        }

        .dark .flatpickr-day {
            color: rgb(209 213 219);
        }

        .dark .flatpickr-day:hover,
        .dark .flatpickr-day:focus {
            background: rgb(55 65 81);
            border-color: rgb(55 65 81);
            color: rgb(243 244 246);
        }

        .dark .flatpickr-day.selected,
        .dark .flatpickr-day.selected:hover {
            background: rgb(var(--primary-600));
            border-color: rgb(var(--primary-600));
            color: white;
        }

        .dark .flatpickr-day.today {
            border-color: rgb(var(--primary-500));
            color: rgb(var(--primary-400));
        }

        .dark .flatpickr-day.today:hover {
            background: rgb(var(--primary-600));
            border-color: rgb(var(--primary-600));
            color: white;
        }

        .dark .flatpickr-day.flatpickr-disabled,
        .dark .flatpickr-day.prevMonthDay,
        .dark .flatpickr-day.nextMonthDay {
            color: rgb(75 85 99);
        }

        .dark .numInputWrapper:hover {
            background: rgb(55 65 81);
        }

        .dark .numInputWrapper span {
            border-color: rgb(55 65 81);
        }

        .dark .numInputWrapper span svg path {
            fill: rgb(156 163 175);
        }

        .dark .pp-flatpickr {
            background-color: rgb(255 255 255 / 0.05);
            color: white;
            box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.1);
        }

        .dark .pp-flatpickr-wrap .pp-cal-icon {
            color: rgb(107 114 128);
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
                        {{-- Input hidden untuk Livewire wire:model; Flatpickr sync nilai ke sini --}}
                        <input type="hidden" wire:model.live="selectedDate" id="pp-selected-date" value="{{ $selectedDate }}" />
                        <div class="pp-flatpickr-wrap">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pp-cal-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            <input
                                type="text"
                                id="pp-datepicker"
                                readonly
                                placeholder="Pilih tanggal..."
                                class="pp-flatpickr" />
                        </div>
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
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Stok Awal</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Pullet</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Layer 1</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Layer 2</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Sisa Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse($mentahState as $idx => $item)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $item['nama'] }}
                                </td>
                                <td class="px-4 py-2.5 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ number_format($item['awal']) }}
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
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
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
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Stok Awal</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-green-600 dark:text-green-400">Masuk</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Pulet</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Layer 1</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Layer 2</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Sisa Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse($campuranState as $idx => $item)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $item['nama'] }}
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
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                    Tidak ada data pakan campuran.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── 3c. TABEL PENGGUNAAN KARUNG (AYAM) ────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden mt-6">
            <header class="fi-section-header flex items-center gap-3 overflow-hidden px-6 py-4">
                <div class="grid flex-1 gap-1">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Penggunaan Karung
                    </h3>
                </div>
            </header>

            <div class="fi-section-content border-t border-gray-200 dark:border-white/10">
                <div class="pp-table-wrapper pp-table">
                    <table>
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama Barang</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Stok Awal</th>
                                <th class="w-32 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Pullet</th>
                                <th class="w-32 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Layer 1</th>
                                <th class="w-32 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Layer 2</th>
                                <th class="w-28 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Sisa Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse($karungState as $idx => $item)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-gray-100 uppercase">
                                    {{ $item['nama'] }}
                                    @if(($item['konversi_sak'] ?? 1) > 1)
                                    <span class="block text-[10px] text-gray-400 font-normal italic">Isi: {{ $item['konversi_sak'] }} Pcs/Sak</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-center text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    {{ number_format((float) $item['awal']) }}
                                </td>

                                {{-- Input Pullet --}}
                                <td class="px-2 py-1.5">
                                    <div class="flex flex-col items-center">
                                        @if(($item['konversi_sak'] ?? 1) > 1)
                                        <input type="number" wire:model.live.debounce.500ms="karungState.{{ $idx }}.p_sak" @disabled(!$canEdit) class="cell-input" placeholder="Sak">
                                        <span class="text-[9px] font-black text-blue-500">{{ number_format((float) $item['p'], 0) }} Pcs</span>
                                        @else
                                        <input type="number" wire:model.live.debounce.500ms="karungState.{{ $idx }}.p" @disabled(!$canEdit) class="cell-input" placeholder="0">
                                        @endif
                                    </div>
                                </td>

                                {{-- Input Layer 1 --}}
                                <td class="px-2 py-1.5">
                                    <div class="flex flex-col items-center">
                                        @if(($item['konversi_sak'] ?? 1) > 1)
                                        <input type="number" wire:model.live.debounce.500ms="karungState.{{ $idx }}.l1_sak" @disabled(!$canEdit) class="cell-input" placeholder="Sak">
                                        <span class="text-[9px] font-black text-blue-500">{{ number_format((float) $item['l1'], 0) }} Pcs</span>
                                        @else
                                        <input type="number" wire:model.live.debounce.500ms="karungState.{{ $idx }}.l1" @disabled(!$canEdit) class="cell-input" placeholder="0">
                                        @endif
                                    </div>
                                </td>

                                {{-- Input Layer 2 --}}
                                <td class="px-2 py-1.5">
                                    <div class="flex flex-col items-center">
                                        @if(($item['konversi_sak'] ?? 1) > 1)
                                        <input type="number" wire:model.live.debounce.500ms="karungState.{{ $idx }}.l2_sak" @disabled(!$canEdit) class="cell-input" placeholder="Sak">
                                        <span class="text-[9px] font-black text-blue-500">{{ number_format((float) $item['l2'], 0) }} Pcs</span>
                                        @else
                                        <input type="number" wire:model.live.debounce.500ms="karungState.{{ $idx }}.l2" @disabled(!$canEdit) class="cell-input" placeholder="0">
                                        @endif
                                    </div>
                                </td>

                                {{-- Sisa Akhir --}}
                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-block rounded-md bg-amber-50 px-2.5 py-1 text-sm font-bold text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
                                        {{ number_format((float) $item['akhir'], 2) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                    Tidak ada data karung (ayam).
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


    {{-- ── Flatpickr CDN ── --}}
    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initPpDatepicker();
        });

        document.addEventListener('livewire:navigated', function() {
            initPpDatepicker();
        });

        function initPpDatepicker() {
            const hiddenInput = document.getElementById('pp-selected-date');
            const visibleInput = document.getElementById('pp-datepicker');

            if (!visibleInput || !hiddenInput) return;

            // Destroy instance lama jika ada (saat Livewire re-render)
            if (visibleInput._flatpickr) {
                visibleInput._flatpickr.destroy();
            }

            const today = new Date();
            const initialDate = hiddenInput.value || today.toISOString().split('T')[0];

            flatpickr(visibleInput, {
                locale: 'id',
                dateFormat: 'd F Y', // tampilan: 04 Mei 2026
                altInput: false,
                defaultDate: initialDate,
                maxDate: today,
                disableMobile: true, // tetap pakai Flatpickr di mobile
                onChange: function(selectedDates, dateStr, instance) {
                    if (!selectedDates.length) return;

                    // Format ke Y-m-d untuk Livewire
                    const d = selectedDates[0];
                    const ymd = d.getFullYear() + '-' +
                        String(d.getMonth() + 1).padStart(2, '0') + '-' +
                        String(d.getDate()).padStart(2, '0');

                    // Update hidden input dan dispatch ke Livewire
                    hiddenInput.value = ymd;
                    hiddenInput.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                },
            });
        }

        // Re-init setelah setiap Livewire update (jaga-jaga jika DOM di-morph)
        document.addEventListener('livewire:updated', function() {
            const visibleInput = document.getElementById('pp-datepicker');
            if (visibleInput && !visibleInput._flatpickr) {
                initPpDatepicker();
            }
        });
    </script>
    @endpush

</x-filament-panels::page>