<x-filament-panels::page>

    {{-- Flatpickr CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_orange.css">

    <style>
        [x-cloak] {
            display: none !important;
        }

        .flatpickr-calendar {
            border-radius: 4px !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .1), 0 4px 6px -2px rgba(0, 0, 0, .05) !important;
            border: 1px solid #e5e7eb !important;
            z-index: 9999 !important;
        }

        .dark .flatpickr-calendar {
            background: #111827 !important;
            border-color: #374151 !important;
            color: #fff !important;
        }

        .no-transition * {
            transition: none !important;
            animation: none !important;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }

        .dark .custom-scroll::-webkit-scrollbar-thumb {
            background: #374151;
        }

        @keyframes blink {

            0%,
            80%,
            100% {
                opacity: .15;
                transform: scale(.8);
            }

            40% {
                opacity: 1;
                transform: scale(1.2);
            }
        }

        .loading-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #d97706;
            animation: blink 1.4s infinite ease-in-out;
        }

        .loading-dot:nth-child(1) {
            animation-delay: 0s;
        }

        .loading-dot:nth-child(2) {
            animation-delay: .2s;
        }

        .loading-dot:nth-child(3) {
            animation-delay: .4s;
        }

        @keyframes shimmer {
            0% {
                background-position: -600px 0;
            }

            100% {
                background-position: 600px 0;
            }
        }

        .skeleton-row td {
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 600px 100%;
            animation: shimmer 1.5s infinite linear;
            border-radius: 3px;
            height: 18px;
        }

        .dark .skeleton-row td {
            background: linear-gradient(90deg, #1f2937 25%, #374151 50%, #1f2937 75%);
            background-size: 600px 100%;
        }

        @keyframes fadeInRow {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .row-fadein {
            animation: fadeInRow .25s ease-out forwards;
        }

        .filter-badge {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 1px solid #f59e0b;
            color: #92400e;
        }

        .dark .filter-badge {
            background: linear-gradient(135deg, #451a03, #78350f);
            border-color: #b45309;
            color: #fcd34d;
        }

        .table-body-scroll {
            max-height: 700px;
            overflow-y: auto;
            overflow-x: auto;
        }

        .table-body-scroll::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        .table-body-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .table-body-scroll::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }

        .dark .table-body-scroll::-webkit-scrollbar-thumb {
            background: #374151;
        }

        .table-body-scroll thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .toast {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 4px;
            border-left: 3px solid;
            min-width: 280px;
            max-width: 360px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .18);
            pointer-events: all;
            animation: toastIn .22s cubic-bezier(.16, 1, .3, 1) forwards;
        }

        .toast.hide {
            animation: toastOut .18s ease-in forwards;
        }

        .toast-success {
            background: #111827;
            border-color: #10b981;
            color: #d1fae5;
        }

        .toast-error {
            background: #111827;
            border-color: #ef4444;
            color: #fee2e2;
        }

        .toast-info {
            background: #111827;
            border-color: #d97706;
            color: #fef3c7;
        }

        .toast-icon {
            flex-shrink: 0;
            width: 18px;
            height: 18px;
            margin-top: 1px;
        }

        .toast-body {
            flex: 1;
        }

        .toast-title {
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .1em;
            line-height: 1.3;
        }

        .toast-msg {
            font-size: 12px;
            font-weight: 500;
            opacity: .75;
            margin-top: 2px;
            line-height: 1.4;
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateX(24px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes toastOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }

            to {
                opacity: 0;
                transform: translateX(24px);
            }
        }

        .bulk-bar {
            position: sticky;
            top: 0;
            z-index: 30;
            animation: fadeInRow .2s ease-out forwards;
        }

        .row-checkbox {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            border: 2px solid #d1d5db;
            cursor: pointer;
            accent-color: #d97706;
        }

        .row-selected td {
            background-color: rgba(217, 119, 6, .06) !important;
        }
    </style>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- FORM INPUT UTAMA                                               --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="fi-page-content max-w-7xl mx-auto no-transition"
        x-data="{
            tgl:            @entangle('tgl'),
            jurnal:         @entangle('jurnal'),
            no_akun:        @entangle('no_akun'),
            nama_akun:      @entangle('nama_akun'),
            nama:           @entangle('nama'),
            keterangan:     @entangle('keterangan'),
            banyak:         @entangle('banyak'),
            harga_display:  '',
            harga_raw:      @entangle('harga'),
            map:            @entangle('map'),
            searchTerm:     '',
            isDropdownOpen: false,
            accounts:       @js($accounts ?? []),
            items:          @entangle('items'),

            /* ── Hitung total: banyak × harga, atau harga saja jika banyak kosong ── */
            hitungTotal(banyak, harga) {
                const h = parseFloat(harga) || 0;
                if (banyak === null || banyak === undefined || banyak === '' || parseFloat(banyak) <= 0) return h;
                return parseFloat(banyak) * h;
            },

            /* ── Format angka jadi Rupiah: titik ribuan, koma desimal ── */
            formatRupiah(val) {
                if (val === null || val === undefined || val === '') return '';
                /* Bersihkan semua kecuali angka dan koma */
                let clean    = val.toString().replace(/[^0-9,]/g, '');
                let commaIdx = clean.indexOf(',');
                let intPart, decPart;
                if (commaIdx === -1) {
                    intPart = clean;
                    decPart = null;
                } else {
                    intPart = clean.substring(0, commaIdx);
                    /* Buang koma ganda di bagian desimal */
                    decPart = clean.substring(commaIdx + 1).replace(/,/g, '');
                }
                /* Format ribuan dengan titik */
                let formatted = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                return decPart !== null ? formatted + ',' + decPart : formatted;
            },

            /* ── Parse display → float untuk dikirim ke PHP ── */
            parseToFloat(displayVal) {
                if (!displayVal) return 0;
                /* Hapus titik ribuan, ganti koma desimal jadi titik */
                return parseFloat(displayVal.replace(/\./g, '').replace(',', '.')) || 0;
            },

            /* ── Format float PHP → display string (untuk populate edit/load draft) ── */
            floatToDisplay(num) {
                if (num === null || num === undefined || num === '' || isNaN(parseFloat(num))) return '';
                let n = parseFloat(num);
                /* Tampilkan desimal hanya jika ada */
                let str = Number.isInteger(n) ? n.toString() : n.toString().replace('.', ',');
                /* Terapkan format ribuan */
                return this.formatRupiah(str);
            },

            /* ── Format total untuk preview & tabel: tanpa trailing ,00 ── */
            formatTotal(num) {
                if (!num && num !== 0) return '0';
                let n      = parseFloat(num);
                let parts  = n.toFixed(2).split('.');
                let intFmt = new Intl.NumberFormat('id-ID').format(parseInt(parts[0]));
                let dec    = parts[1];
                return dec === '00' ? intFmt : intFmt + ',' + dec;
            },

            get filteredAccounts() {
                const valid = (this.accounts ?? []).filter(acc => acc && acc.no && acc.nama);
                if (this.searchTerm === '') return valid;
                const term = this.searchTerm.toLowerCase();
                return valid.filter(acc =>
                    acc.no.toLowerCase().includes(term) ||
                    acc.nama.toLowerCase().includes(term)
                );
            },
            selectAccount(acc) {
                this.no_akun        = acc.no;
                this.nama_akun      = acc.nama;
                this.searchTerm     = acc.no;
                this.isDropdownOpen = false;
            },
            clearAccount() {
                this.no_akun        = '';
                this.nama_akun      = '';
                this.searchTerm     = '';
                this.isDropdownOpen = false;
            },
            renderDropdown() {
                const container = this.$refs.dropdownList;
                if (!container) return;
                container.innerHTML = '';
                const list = this.filteredAccounts;
                if (list.length === 0) {
                    container.innerHTML = '<div class=\'px-3 py-4 text-center text-xs text-gray-400 italic\'>Tidak ada akun ditemukan.</div>';
                    return;
                }
                list.forEach(acc => {
                    if (!acc || !acc.no || !acc.nama) return;
                    const btn      = document.createElement('button');
                    btn.type       = 'button';
                    btn.className  = 'w-full text-left px-3 py-2 hover:bg-amber-50 dark:hover:bg-amber-900/40 rounded-[2px] flex flex-col group transition-none';
                    const spanNama = document.createElement('span');
                    spanNama.className   = 'text-sm text-gray-500 dark:text-gray-300 font-medium';
                    spanNama.textContent = acc.nama;
                    const spanNo = document.createElement('span');
                    spanNo.className   = 'font-bold text-gray-800 dark:text-gray-200 text-sm';
                    spanNo.textContent = acc.no;
                    btn.appendChild(spanNama);
                    btn.appendChild(spanNo);
                    btn.addEventListener('click', () => { this.selectAccount(acc); });
                    container.appendChild(btn);
                });
            },
            initFlatpickr() {
                const init = () => {
                    if (typeof flatpickr === 'undefined') { setTimeout(init, 50); return; }
                    flatpickr(this.$refs.dateInput, {
                        dateFormat: 'Y-m-d',
                        defaultDate: this.tgl,
                        onChange: (dates, dateStr) => { this.tgl = dateStr; }
                    });
                };
                init();
            },
            get totalDebit() {
                return this.items.reduce((a, c) =>
                    c.map.toLowerCase() === 'd' ? a + parseFloat(c.total) : a, 0);
            },
            get totalKredit() {
                return this.items.reduce((a, c) =>
                    c.map.toLowerCase() === 'k' ? a + parseFloat(c.total) : a, 0);
            },
            get isBalanced() {
                return Math.abs(this.totalDebit - this.totalKredit) < 0.01 && this.items.length > 0;
            }
        }"
        x-init="
            initFlatpickr();
            (function(self) {
                $watch('searchTerm',     () => { self.renderDropdown(); });
                $watch('isDropdownOpen', val => { if (val) setTimeout(() => { self.renderDropdown(); }, 0); });
                $watch('accounts',       () => { self.renderDropdown(); });
            })(Alpine.raw ? Alpine.raw($data) : $data);

            $watch('no_akun', v => {
                if (!v) { searchTerm = ''; }
                else if (searchTerm !== v) { searchTerm = v; }
            });

            /* Populate display saat load (misal dari draft session) */
            harga_display = floatToDisplay(harga_raw);

            /* Saat harga_raw diubah dari luar (load to draft), sync display */
            $watch('harga_raw', v => {
                let expected = parseToFloat(harga_display);
                if (Math.abs(expected - parseFloat(v || 0)) > 0.001) {
                    harga_display = floatToDisplay(v);
                }
            });

            $wire.on('toast', ({ type, title, msg }) => {
                window.showToast(type, title, msg ?? '');
            });

            /* Saat loadToDraft selesai, sync harga_display */
            $wire.on('harga-loaded', ({ harga }) => {
                harga_display = floatToDisplay(harga);
            });
        ">

        {{-- ── Form Input ─────────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[4px] shadow-sm overflow-hidden mb-6">
            <div class="bg-amber-600 dark:bg-amber-700 px-6 py-4 text-white">
                <h2 class="text-sm font-bold tracking-tight flex items-center gap-2 uppercase tracking-widest leading-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Masukkan detail transaksi.
                </h2>
            </div>

            <form wire:submit.prevent="addItem" class="p-6 space-y-6">
                @csrf

                {{-- Baris 1: Tanggal + No Jurnal --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal Transaksi</label>
                        <input type="text" x-ref="dateInput" readonly
                            class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-[4px] outline-none font-medium text-gray-800 dark:text-gray-200 cursor-pointer">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Jurnal</label>
                        <input type="text" x-model="jurnal" placeholder="Isi nomor jurnal..."
                            class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-[4px] outline-none font-medium text-gray-800 dark:text-gray-200 placeholder:text-sm">
                    </div>
                </div>

                {{-- Baris 2: Akun + Nama --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-1.5 relative" @click.away="isDropdownOpen = false">
                        <label class="text-[11px] font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cari Nomor Akun</label>
                        <div class="relative flex items-center">
                            <input type="text" x-model="searchTerm" @focus="isDropdownOpen = true" placeholder="Ketik no/nama..."
                                class="w-full px-3 py-2.5 bg-white dark:bg-gray-800 border border-amber-300 dark:border-amber-900 rounded-[4px] font-bold text-amber-700 dark:text-amber-500 outline-none pr-10 focus:ring-1 focus:ring-amber-500 placeholder:text-sm">
                            <button type="button" x-show="searchTerm.length > 0 || no_akun" @click="clearAccount()"
                                class="absolute right-3 text-gray-400 hover:text-rose-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div x-show="isDropdownOpen" x-cloak
                            class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-[4px] shadow-lg max-h-60 overflow-y-auto p-1 custom-scroll"
                            x-ref="dropdownList">
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Akun</label>
                        <div class="px-3 py-2.5 bg-gray-100 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-[4px] text-gray-600 dark:text-gray-300 font-bold text-sm min-h-[42px] flex items-center"
                            x-text="nama_akun || 'Pilih akun...'"></div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama</label>
                        <input type="text" x-model="nama" placeholder="Masukkan nama..."
                            class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-[4px] outline-none font-medium text-gray-800 dark:text-gray-200 placeholder:text-sm">
                    </div>
                </div>

                {{-- Baris 3: Keterangan --}}
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan Transaksi</label>
                    <input type="text" x-model="keterangan" placeholder="Masukkan detail keterangan..."
                        class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-[4px] outline-none font-medium text-gray-800 dark:text-gray-200 focus:border-amber-500 placeholder:text-sm">
                </div>

                {{-- Baris 4: Kuantitas + Harga + Tipe Mutasi --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kuantitas (Opsional)</label>
                        <input type="text" inputmode="decimal" x-model="banyak"
                            placeholder="Kosongkan jika tidak perlu"
                            class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-[4px] font-bold text-gray-500 dark:text-gray-300">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">Rp</span>
                            <input type="text"
                                x-model="harga_display"
                                @input="
                                    const el      = $event.target;
                                    const raw     = el.value;
                                    const cursor  = el.selectionStart;

                                    /* Berapa titik sebelum cursor di nilai lama */
                                    const dotsBeforeCursor = (raw.substring(0, cursor).match(/\./g) || []).length;

                                    const formatted = formatRupiah(raw);

                                    /* Cari posisi cursor baru berdasarkan jumlah karakter non-titik */
                                    const targetCharPos = cursor - dotsBeforeCursor;
                                    let charCount = 0;
                                    let newCursor = formatted.length;
                                    for (let i = 0; i < formatted.length; i++) {
                                        if (formatted[i] !== '.') charCount++;
                                        if (charCount === targetCharPos) { newCursor = i + 1; break; }
                                    }
                                    /* Jika user baru ketik koma, paksa cursor ke akhir */
                                    if (raw[cursor - 1] === ',') newCursor = formatted.length;

                                    harga_display = formatted;
                                    harga_raw     = parseToFloat(formatted);

                                    $nextTick(() => { el.setSelectionRange(newCursor, newCursor); });
                                "
                                @blur="
                                    /* Buang koma di akhir saat blur */
                                    if (harga_display.endsWith(',')) {
                                        harga_display = harga_display.slice(0, -1);
                                    }
                                    harga_raw = parseToFloat(harga_display);
                                "
                                placeholder="0"
                                class="w-full pl-9 pr-3 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-[4px] font-bold text-gray-500 dark:text-gray-300">
                        </div>
                    </div>

                    <div class="space-y-3 pt-1">
                        <label class="text-[11px] font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider block text-center">Tipe Mutasi</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="map = 'd'"
                                :class="map === 'd' ? 'bg-emerald-600 border-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-400'"
                                class="py-2.5 rounded-[4px] border font-black text-xs tracking-widest transition-none">DEBIT</button>
                            <button type="button" @click="map = 'k'"
                                :class="map === 'k' ? 'bg-rose-600 border-rose-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-400'"
                                class="py-2.5 rounded-[4px] border font-black text-xs tracking-widest transition-none">KREDIT</button>
                        </div>
                    </div>
                </div>

                {{-- Preview Total --}}
                <div class="flex items-center gap-2 px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 rounded-[4px]">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Preview Total</span>
                    <span class="text-[10px] text-gray-300 dark:text-gray-600">→</span>
                    <span class="font-black text-sm"
                        :class="map === 'd' ? 'text-emerald-500' : 'text-rose-500'"
                        x-text="'Rp ' + formatTotal(hitungTotal(banyak, harga_raw))">
                    </span>
                    <span class="text-[10px] font-bold text-gray-300 dark:text-gray-600 ml-1"
                        x-text="'(' + (map === 'd' ? 'Debit' : 'Kredit') + ')'"></span>
                </div>

                <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-800 pt-6">
                    <button type="button" wire:click="resetForm"
                        class="px-6 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 rounded-[4px] font-bold text-[10px] uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 transition-none">
                        Batal
                    </button>
                    <button type="button"
                        @click="harga_raw = parseToFloat(harga_display); $nextTick(() => { $wire.addItem(); });"
                        class="px-10 py-2.5 bg-amber-600 dark:bg-amber-700 text-white rounded-[4px] font-bold text-[10px] uppercase tracking-widest hover:bg-amber-700 transition-none flex items-center gap-2 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Masukkan Draft
                    </button>
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- TABLE DRAFT                                                    --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="items.length > 0" x-cloak class="space-y-4 mb-10">
            <div class="flex items-center justify-between px-1">
                <div :class="isBalanced
                    ? 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400'
                    : 'bg-red-50 border-red-200 text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400'"
                    class="px-4 py-2 rounded-[4px] border flex items-center gap-2.5 font-black text-[11px] uppercase tracking-[.2em] shadow-sm">
                    <div :class="isBalanced ? 'bg-green-500' : 'bg-red-500 animate-pulse'"
                        class="w-1.5 h-1.5 rounded-full flex-shrink-0"></div>
                    <span x-text="isBalanced ? 'Jurnal Balanced' : 'Jurnal Unbalanced'"></span>
                </div>
                <div class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                    <span x-text="items.length"></span> item dalam draft
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[4px] shadow-sm overflow-hidden">
                <div class="grid grid-cols-[1fr_80px_140px_60px_200px] gap-0 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/60 px-4 py-2">
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Akun & Keterangan</div>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Qty</div>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest text-right pr-4">Harga</div>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Tipe</div>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Debit / Kredit</div>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <template x-for="(row, i) in items" :key="i">
                        <template x-if="row && row.no_akun && row.nama_akun">
                            <div class="grid grid-cols-[1fr_80px_140px_60px_200px] gap-0 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/40 items-center group">
                                <div class="min-w-0 pr-4">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-[3px] bg-gray-100 dark:bg-gray-700 text-[10px] font-black text-gray-400 tracking-wider shrink-0"
                                            x-text="'#' + row.jurnal"></span>
                                        <span class="font-mono font-black text-amber-600 dark:text-amber-500 text-sm"
                                            x-text="row.no_akun"></span>
                                        <span class="font-bold text-gray-800 dark:text-gray-100 text-sm truncate"
                                            x-text="row.nama_akun"></span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
                                        <span x-show="row.nama" x-text="row.nama"
                                            class="font-medium text-gray-500 dark:text-gray-400 shrink-0"></span>
                                        <span x-show="row.nama && row.keterangan"
                                            class="text-gray-300 dark:text-gray-600">·</span>
                                        <span x-show="row.keterangan" x-text="row.keterangan"
                                            class="truncate text-gray-400 dark:text-gray-500"></span>
                                    </div>
                                </div>

                                <div class="text-right shrink-0 whitespace-nowrap">
                                    <span class="text-sm font-bold text-gray-500 dark:text-gray-400 tabular-nums"
                                        x-text="(row.banyak !== null && row.banyak !== undefined && row.banyak !== '') ? formatTotal(row.banyak) : '-'"></span>
                                </div>

                                {{-- Kolom Harga: tampilkan desimal jika ada --}}
                                <div class="text-right pr-4 shrink-0 whitespace-nowrap">
                                    <span class="text-sm font-bold text-gray-500 dark:text-gray-400 tabular-nums"
                                        x-text="formatTotal(row.harga)"></span>
                                </div>

                                <div class="flex justify-center shrink-0">
                                    <span :class="row.map.toLowerCase() === 'd'
                                        ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400'
                                        : 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400'"
                                        class="inline-flex items-center justify-center w-6 h-6 rounded-[3px] text-[11px] font-black uppercase"
                                        x-text="row.map.toLowerCase() === 'd' ? 'D' : 'K'">
                                    </span>
                                </div>

                                <div class="flex items-center justify-end gap-3 shrink-0 whitespace-nowrap">
                                    <div :class="row.map.toLowerCase() === 'd' ? 'text-emerald-500' : 'text-rose-500'"
                                        class="font-black text-sm tabular-nums whitespace-nowrap"
                                        x-text="'Rp ' + formatTotal(row.total)">
                                    </div>
                                    <button type="button" @click="$wire.mountAction('editDraft', { index: i })"
                                        class="p-1.5 text-amber-600/80 hover:text-amber-600 dark:text-amber-500/80 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-[3px] transition-none shrink-0"
                                        title="Edit Item">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="$wire.removeItem(i)"
                                        class="p-1.5 text-rose-600/80 hover:text-rose-600 dark:text-rose-500/80 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-[3px] transition-none shrink-0"
                                        title="Hapus Item">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </template>
                </div>

                <div class="border-t-2 border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/60">
                    <div class="grid grid-cols-2 divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="px-6 py-3 text-right">
                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Total Debit</div>
                            <div class="font-black text-emerald-500 text-base tabular-nums"
                                x-text="'Rp ' + formatTotal(totalDebit)"></div>
                        </div>
                        <div class="px-6 py-3 text-right">
                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Total Kredit</div>
                            <div class="font-black text-rose-500 text-base tabular-nums"
                                x-text="'Rp ' + formatTotal(totalKredit)"></div>
                        </div>
                    </div>

                    <div x-show="!isBalanced"
                        class="px-6 py-2 border-t border-dashed border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/10 flex items-center justify-end gap-2">
                        <svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-[11px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-wider">Selisih</span>
                        <span class="text-[11px] font-black text-amber-700 dark:text-amber-300 tabular-nums"
                            x-text="'Rp ' + formatTotal(Math.abs(totalDebit - totalKredit))">
                        </span>
                    </div>
                </div>

                <div class="p-4 bg-amber-50 dark:bg-amber-900/10 border-t border-amber-100 dark:border-gray-800 flex justify-end">
                    <button type="button" wire:click="saveJurnal" :disabled="!isBalanced"
                        :class="isBalanced
                            ? 'bg-amber-600 text-white hover:bg-amber-700 shadow-md cursor-pointer'
                            : 'bg-gray-200 dark:bg-gray-800 text-gray-400 cursor-not-allowed border-transparent shadow-none'"
                        class="px-12 py-3 rounded-[4px] font-black text-[10px] uppercase tracking-[.2em] transition-none">
                        Posting Jurnal
                    </button>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- TABLE HISTORY + BULK DELETE                                    --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="space-y-4"
            x-data="{
                isFiltering: false,
                fpDari:    null,
                fpSampai:  null,
                filterDari:   @entangle('filterTglDariInput'),
                filterSampai: @entangle('filterTglSampaiInput'),
                activeFilterDari:   @entangle('filterTglDari'),
                activeFilterSampai: @entangle('filterTglSampai'),
                hasMorePages: @entangle('hasMorePages'),

                selectedIds: @entangle('selectedIds'),
                selectAll:   @entangle('selectAll'),
                showConfirm: false,

                get visibleIds() {
                    return Array.from(document.querySelectorAll('[data-row-id]'))
                        .map(el => parseInt(el.dataset.rowId));
                },
                toggleSelectAll() {
                    if (this.selectAll) { this.$wire.toggleSelectAll([]); }
                    else { this.$wire.toggleSelectAll(this.visibleIds); }
                },
                toggleRow(id)      { this.$wire.toggleSelected(id); },
                isSelected(id)     { return this.selectedIds.includes(id); },
                confirmBulkDelete(){ if (this.selectedIds.length === 0) return; this.showConfirm = true; },
                cancelBulkDelete() { this.showConfirm = false; },
                doBulkDelete()     { this.showConfirm = false; this.$wire.bulkDelete(); },

                get hasActiveFilter() { return this.activeFilterDari !== '' || this.activeFilterSampai !== ''; },
                formatDateDisplay(val) {
                    if (!val) return '';
                    const [y, m, d] = val.split('-');
                    return d + '-' + m + '-' + y;
                },
                async applyFilter() {
                    this.isFiltering = true;
                    await this.$wire.applyFilter();
                    this.isFiltering = false;
                },
                async resetFilter() {
                    this.isFiltering = true;
                    if (this.fpDari)   this.fpDari.clear();
                    if (this.fpSampai) this.fpSampai.clear();
                    this.filterDari   = '';
                    this.filterSampai = '';
                    await this.$wire.resetFilter();
                    this.isFiltering = false;
                },
                initFilterDatepickers() {
                    const init = () => {
                        if (typeof flatpickr === 'undefined') { setTimeout(init, 50); return; }
                        this.fpDari   = flatpickr(this.$refs.filterDariInput,   { dateFormat:'Y-m-d', onChange:(_, d) => { this.filterDari = d; } });
                        this.fpSampai = flatpickr(this.$refs.filterSampaiInput, { dateFormat:'Y-m-d', onChange:(_, d) => { this.filterSampai = d; } });
                    };
                    init();
                },
                initInfiniteScroll() {
                    const sentinel   = this.$refs.scrollSentinel;
                    const scrollRoot = this.$refs.tableScrollBody;
                    if (!sentinel || !scrollRoot) return;
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting && this.hasMorePages && !this.isFiltering) {
                                this.$wire.loadMore();
                            }
                        });
                    }, { root: scrollRoot, rootMargin: '100px' });
                    observer.observe(sentinel);
                }
            }"
            x-init="
                initFilterDatepickers();
                initInfiniteScroll();
                $wire.on('bulk-delete-done', () => { showConfirm = false; });
                $wire.on('toast', ({ type, title, msg }) => { window.showToast(type, title, msg ?? ''); });
            ">

            {{-- Modal Konfirmasi Bulk Delete --}}
            <div x-show="showConfirm" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-[4px] shadow-2xl p-8 max-w-sm w-full mx-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-black text-sm text-gray-800 dark:text-gray-100 uppercase tracking-wider">Hapus Transaksi?</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                        Anda akan menghapus <span class="font-black text-red-500" x-text="selectedIds.length"></span> transaksi secara permanen.
                    </p>
                    <div class="flex gap-3 justify-end">
                        <button @click="cancelBulkDelete()"
                            class="px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 rounded-[4px] font-bold text-[10px] uppercase tracking-widest hover:bg-gray-50 transition-none">
                            Batal
                        </button>
                        <button @click="doBulkDelete()"
                            class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-[4px] font-black text-[10px] uppercase tracking-widest transition-none flex items-center gap-2"
                            wire:loading.attr="disabled" wire:target="bulkDelete">
                            <span wire:loading wire:target="bulkDelete" class="flex gap-1">
                                <span class="loading-dot" style="background:#fff"></span>
                                <span class="loading-dot" style="background:#fff"></span>
                                <span class="loading-dot" style="background:#fff"></span>
                            </span>
                            <span wire:loading.remove wire:target="bulkDelete">Ya, Hapus Semua</span>
                        </button>
                    </div>
                </div>
            </div>

            @php
            $fmtTotal = function(float $n): string {
                $f = number_format($n, 2, ',', '.');
                return str_ends_with($f, ',00') ? substr($f, 0, -3) : $f;
            };
            @endphp

            {{-- Header + Filter & Summary Total --}}
            <div class="flex flex-col gap-3 px-1">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h2 class="text-sm font-black uppercase tracking-widest text-gray-500">Riwayat Jurnal Umum</h2>
                    </div>
                    <div x-show="hasActiveFilter" x-cloak
                        class="filter-badge px-3 py-1.5 rounded-[4px] text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4h18M7 8h10M11 12h2" />
                        </svg>
                        <span x-text="'Filter: ' + (activeFilterDari ? formatDateDisplay(activeFilterDari) : '...') + ' → ' + (activeFilterSampai ? formatDateDisplay(activeFilterSampai) : '...')"></span>
                    </div>
                </div>

                {{-- SATU BARIS KOTAK: Filter Kiri & Summary Kanan --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[4px] p-3 lg:p-4 shadow-sm flex flex-col xl:flex-row items-center justify-between gap-4 w-full">
                    
                    {{-- KIRI: Form Filter Tanggal --}}
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2 mr-1">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Filter:</span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <input type="text" x-ref="filterDariInput" readonly placeholder="Dari Tanggal..."
                                class="px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-[4px] text-xs font-bold text-gray-700 dark:text-gray-200 cursor-pointer w-32 outline-none focus:border-amber-400">
                            
                            <span class="text-gray-300 dark:text-gray-600 font-black">→</span>
                            
                            <input type="text" x-ref="filterSampaiInput" readonly placeholder="Sampai Tanggal..."
                                class="px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-[4px] text-xs font-bold text-gray-700 dark:text-gray-200 cursor-pointer w-32 outline-none focus:border-amber-400">
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="applyFilter()" :disabled="isFiltering"
                                class="flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-[4px] font-black text-[10px] uppercase tracking-widest transition-none shadow-sm disabled:opacity-60 disabled:cursor-wait">
                                <span x-show="isFiltering" class="flex items-center gap-1">
                                    <span class="loading-dot"></span><span class="loading-dot"></span><span class="loading-dot"></span>
                                </span>
                                <span x-show="!isFiltering" class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Apply
                                </span>
                            </button>
                            <button type="button" @click="resetFilter()"
                                x-show="hasActiveFilter || filterDari || filterSampai"
                                :disabled="isFiltering"
                                class="flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:text-rose-500 hover:border-rose-300 rounded-[4px] font-black text-[10px] uppercase tracking-widest transition-none disabled:opacity-60">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Reset
                            </button>
                        </div>
                    </div>

                    {{-- KANAN: Summary Total & Badge --}}
                    <div class="flex flex-wrap items-center gap-4 xl:gap-5 border-t xl:border-t-0 xl:border-l border-gray-100 dark:border-gray-800 pt-3 xl:pt-0 xl:pl-5 w-full xl:w-auto justify-between xl:justify-end">
                        
                        <div class="flex items-center gap-4">
                            <div class="flex flex-col items-end">
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Total Debit</span>
                                <span class="text-emerald-500 font-black text-sm tabular-nums">Rp {{ $fmtTotal($totalDebitDB) }}</span>
                            </div>
                            
                            <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>
                            
                            <div class="flex flex-col items-end">
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Total Kredit</span>
                                <span class="text-rose-500 font-black text-sm tabular-nums">Rp {{ $fmtTotal($totalKreditDB) }}</span>
                            </div>
                        </div>

                        <div>
                            @if($isHistoryBalanced)
                                <div class="flex items-center gap-2 px-3 py-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-[4px] whitespace-nowrap">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-[10px] font-black text-green-600 dark:text-green-400 uppercase tracking-[0.2em]">Balanced</span>
                                </div>
                            @else
                                <div class="flex flex-col items-end gap-1">
                                    <div class="flex items-center gap-1.5 px-2.5 py-1 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-[4px] whitespace-nowrap">
                                        <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                                        <span class="text-[9px] font-black text-red-600 dark:text-red-400 uppercase tracking-[0.2em]">Unbalanced</span>
                                    </div>
                                    <span class="text-[9px] text-amber-600 dark:text-amber-400 font-bold whitespace-nowrap">Selisih: Rp {{ $fmtTotal($selisihDB) }}</span>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

            {{-- Bulk Action Bar --}}
            <div x-show="selectedIds.length > 0" x-cloak
                class="bulk-bar bg-rose-600 dark:bg-rose-700 rounded-[4px] px-5 py-3 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="text-white font-black text-[11px] uppercase tracking-widest">
                        <span x-text="selectedIds.length"></span> transaksi dipilih
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="selectedIds = []; selectAll = false; $wire.set('selectedIds', []); $wire.set('selectAll', false);"
                        class="px-4 py-1.5 bg-white/10 hover:bg-white/20 text-white rounded-[4px] font-bold text-[10px] uppercase tracking-widest transition-none border border-white/20">
                        Batalkan
                    </button>
                    <button @click="confirmBulkDelete()"
                        class="px-5 py-1.5 bg-white text-rose-600 hover:bg-rose-50 rounded-[4px] font-black text-[10px] uppercase tracking-widest transition-none flex items-center gap-2 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Hapus yang Dipilih
                    </button>
                </div>
            </div>

            {{-- Tabel History --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[4px] shadow-sm overflow-hidden custom-scroll">
                <div class="table-body-scroll" x-ref="tableScrollBody">
                    <table class="w-full text-left text-sm border-collapse table-fixed min-w-[1500px]">
                        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800 sticky top-0 z-10">
                            <tr class="text-[10px] font-bold text-gray-500 dark:text-gray-300 uppercase tracking-widest">
                                <th class="px-4 py-4 w-[48px]">
                                    <input type="checkbox" class="row-checkbox" :checked="selectAll" @change="toggleSelectAll()" title="Pilih semua">
                                </th>
                                <th class="px-4 py-4 w-[100px]">Tanggal</th>
                                <th class="px-4 py-4 w-[100px]">No Akun</th>
                                <th class="px-4 py-4 w-[160px]">Nama Akun</th>
                                <th class="px-4 py-4 text-center w-[90px]">No Jurnal</th>
                                <th class="px-4 py-4 w-[130px]">Nama</th>
                                <th class="px-4 py-4 w-[180px]">Keterangan</th>
                                <th class="px-4 py-4 text-right w-[80px]">Qty</th>
                                <th class="px-4 py-4 text-right w-[120px]">Harga</th>
                                <th class="px-4 py-4 text-right w-[160px] text-green-400 bg-green-50/10 font-black">Debit (Rp)</th>
                                <th class="px-4 py-4 text-right w-[160px] text-red-400 bg-red-50/10 font-black">Kredit (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">

                            <template x-if="isFiltering">
                                <template x-for="n in 8" :key="n">
                                    <tr class="skeleton-row">
                                        <td class="px-4 py-5"></td>
                                        <td class="px-4 py-5">
                                            <div class="h-3 rounded w-20 bg-gray-200 dark:bg-gray-700"></div>
                                        </td>
                                        <td class="px-4 py-5">
                                            <div class="h-3 rounded w-16 bg-gray-200 dark:bg-gray-700"></div>
                                        </td>
                                        <td class="px-4 py-5">
                                            <div class="h-3 rounded w-28 bg-gray-200 dark:bg-gray-700"></div>
                                        </td>
                                        <td class="px-4 py-5">
                                            <div class="h-3 rounded w-10 mx-auto bg-gray-200 dark:bg-gray-700"></div>
                                        </td>
                                        <td class="px-4 py-5">
                                            <div class="h-3 rounded w-20 bg-gray-200 dark:bg-gray-700"></div>
                                        </td>
                                        <td class="px-4 py-5">
                                            <div class="h-3 rounded w-32 bg-gray-200 dark:bg-gray-700"></div>
                                        </td>
                                        <td class="px-4 py-5">
                                            <div class="h-3 rounded w-10 ml-auto bg-gray-200 dark:bg-gray-700"></div>
                                        </td>
                                        <td class="px-4 py-5">
                                            <div class="h-3 rounded w-16 ml-auto bg-gray-200 dark:bg-gray-700"></div>
                                        </td>
                                        <td class="px-4 py-5">
                                            <div class="h-3 rounded w-20 ml-auto bg-gray-200 dark:bg-gray-700"></div>
                                        </td>
                                        <td class="px-4 py-5">
                                            <div class="h-3 rounded w-20 ml-auto bg-gray-200 dark:bg-gray-700"></div>
                                        </td>
                                        <td class="px-4 py-5">
                                            <div class="h-3 rounded w-8 mx-auto bg-gray-200 dark:bg-gray-700"></div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            
                            @forelse($historyJurnals as $index => $hj)
                            @php
                             $namaPelanggan = '-';
                             if (!empty($hj->keterangan)) {

                                $parts = explode('|', $hj->keterangan);

                                // ambil bagian terakhir
                                $lastPart = trim(end($parts));

                                // hindari teks HPP
                                if (
                                    !str_contains(strtolower($hj->keterangan), 'hpp')
                                    && !empty($lastPart)
                                ) {
                                    $namaPelanggan = $lastPart;
                                }
                            }
                            /* Helper: format angka desimal PHP — buang trailing ,00 */
                            $fmt = function(float $n): string {
                            $f = number_format($n, 2, ',', '.');
                            return str_ends_with($f, ',00') ? substr($f, 0, -3) : $f;
                            };
                            $totalNominal = ($hj->banyak !== null && $hj->banyak > 0)
                            ? $hj->banyak * $hj->harga
                            : $hj->harga;
                            @endphp

                            
                            <tr data-row-id="{{ $hj->id }}"
                                :class="isSelected({{ $hj->id }}) ? 'row-selected' : ''"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50 align-top transition-none row-fadein"
                                style="animation-delay: {{ min($index * 0.02, 0.4) }}s">

                                <td class="px-4 py-4 text-center">
                                    <input type="checkbox" class="row-checkbox"
                                        :checked="isSelected({{ $hj->id }})"
                                        @change="toggleRow({{ $hj->id }})">
                                </td>
                                <td class="px-4 py-4 text-gray-500 font-medium whitespace-nowrap">{{ $hj->tgl->format('d-m-Y') }}</td>
                                <td class="px-4 py-4 font-mono font-bold text-amber-600 dark:text-amber-500">{{ $hj->no_akun }}</td>
                                <td class="px-4 py-4 font-bold text-gray-800 dark:text-gray-100">{{ $hj->nama_akun }}</td>
                                <td class="px-4 py-4 text-center text-gray-400 font-medium">{{ $hj->jurnal }}</td>
                                <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $namaPelanggan ?? '-' }}</td>
                                <td class="px-4 py-4 text-[12px] leading-relaxed text-gray-500 dark:text-gray-400 break-words whitespace-normal">{{ $hj->keterangan }}</td>
                                <td class="px-4 py-4 text-right font-medium text-gray-400 dark:text-gray-500">
                                    {{ ($hj->banyak !== null && $hj->banyak > 0) ? $fmt($hj->banyak) : '-' }}
                                </td>
                                <td class="px-4 py-4 text-right text-gray-400 dark:text-gray-500 font-mono">
                                    {{ $fmt($hj->harga) }}
                                </td>
                                <td class="px-4 py-4 text-right font-bold text-green-400 bg-green-50/5">
                                    @if(in_array(strtolower($hj->map), ['d', 'debit']))
                                    {{ $fmt($totalNominal) }}
                                    @else
                                    <span class="text-gray-300 dark:text-gray-600 font-normal">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right font-bold text-red-400 bg-red-50/5">
                                    @if(in_array(strtolower($hj->map), ['k', 'kredit']))
                                    {{ $fmt($totalNominal) }}
                                    @else
                                    <span class="text-gray-300 dark:text-gray-600 font-normal">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="12" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3 text-gray-400">
                                        <svg class="w-10 h-10 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-xs italic font-medium">
                                            {{ ($filterTglDari || $filterTglSampai) ? 'Tidak ada data untuk rentang tanggal yang dipilih.' : 'Belum ada riwayat transaksi yang diposting.' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div x-ref="scrollSentinel" class="h-1"></div>

                    <div wire:loading wire:target="loadMore"
                        style="position:sticky;bottom:0;left:0;right:0;z-index:20;pointer-events:none;"
                        class="flex items-center justify-center py-4 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm border-t border-amber-100 dark:border-amber-900/40">
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-white dark:bg-gray-800 border border-amber-200 dark:border-amber-800 rounded-full shadow-md">
                            <svg class="w-4 h-4 text-amber-500 animate-spin flex-shrink-0" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10" opacity="0.25" />
                                <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                            </svg>
                            <span class="text-[10px] font-black text-gray-500 dark:text-gray-300 uppercase tracking-[.2em]">Memuat data jurnal</span>
                            <span class="text-[10px] font-black text-amber-600 dark:text-amber-400">+50 baris</span>
                        </div>
                    </div>

                    @if(!$hasMorePages && $historyJurnals->count() > 0)
                    <div style="position:sticky;bottom:0;left:0;right:0;"
                        class="flex items-center justify-center gap-3 py-3 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm border-t border-gray-100 dark:border-gray-800">
                        <div class="flex-1 max-w-[60px] h-px bg-gradient-to-r from-transparent to-gray-200 dark:to-gray-700"></div>
                        <div class="flex items-center gap-2 text-gray-300 dark:text-gray-600">
                            <div class="w-4 h-4 rounded-full bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 flex items-center justify-center">
                                <svg class="w-2.5 h-2.5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <polyline stroke-linecap="round" stroke-linejoin="round" stroke-width="3" points="20 6 9 17 4 12" />
                                </svg>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-[.3em]">Semua data sudah dimuat</span>
                        </div>
                        <div class="flex-1 max-w-[60px] h-px bg-gradient-to-l from-transparent to-gray-200 dark:to-gray-700"></div>
                    </div>
                    @endif
                </div>

                {{-- Footer Totals (Dikembalikan Sesuai Request) --}}
                <div class="overflow-x-auto border-t-2 border-gray-200 dark:border-gray-700">
                    <table class="w-full text-left text-sm border-collapse table-fixed min-w-[1500px]">
                        <tfoot class="bg-gray-50 dark:bg-gray-800 border-t-2 border-gray-200 dark:border-gray-700 font-black text-[10px] uppercase">
                            <tr>
                                <td colspan="9" class="px-4 py-5 text-right text-gray-400 tracking-widest uppercase">Total Akumulasi</td>
                                <td class="px-4 py-5 text-right text-green-400 bg-green-50/10 text-base font-black pr-6">
                                    {{ $fmtTotal($totalDebitDB) }}
                                </td>
                                <td class="px-4 py-5 text-right text-red-400 bg-red-50/10 text-base font-black pr-6">
                                    {{ $fmtTotal($totalKreditDB) }}
                                </td>
                            </tr>
                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                <td colspan="11" class="px-4 py-3">
                                    @if($isHistoryBalanced)
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="flex items-center gap-2 px-4 py-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-[4px]">
                                            <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="text-[10px] font-black text-green-600 dark:text-green-400 uppercase tracking-[.2em]">Jurnal Balanced</span>
                                            <span class="text-[10px] text-green-500 font-medium normal-case tracking-normal">— Debit = Kredit</span>
                                        </div>
                                    </div>
                                    @else
                                    <div class="flex items-center justify-end gap-3">
                                        <div class="flex items-center gap-2 px-4 py-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-[4px]">
                                            <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                                            <span class="text-[10px] font-black text-red-600 dark:text-red-400 uppercase tracking-[.2em]">Jurnal Unbalanced</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-[4px]">
                                            <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-[10px] text-amber-600 dark:text-amber-400 font-bold normal-case tracking-normal">Selisih: Rp {{ $fmtTotal($selisihDB) }}</span>
                                        </div>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <x-filament-actions::modals />

    <div id="toast-container"></div>

    <script>
        window.showToast = function(type, title, msg, duration) {
            if (duration === undefined) duration = 3500;
            var container = document.getElementById('toast-container');
            if (!container) return;
            var svgNS = 'http://www.w3.org/2000/svg';
            var svg = document.createElementNS(svgNS, 'svg');
            svg.setAttribute('class', 'toast-icon');
            svg.setAttribute('fill', 'none');
            svg.setAttribute('stroke', 'currentColor');
            svg.setAttribute('viewBox', '0 0 24 24');
            var path = document.createElementNS(svgNS, 'path');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-linejoin', 'round');
            if (type === 'success') {
                path.setAttribute('stroke-width', '2.5');
                path.setAttribute('d', 'M5 13l4 4L19 7');
            } else if (type === 'error') {
                path.setAttribute('stroke-width', '2.5');
                path.setAttribute('d', 'M6 18L18 6M6 6l12 12');
            } else {
                path.setAttribute('stroke-width', '2');
                path.setAttribute('d', 'M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z');
            }
            svg.appendChild(path);
            var body = document.createElement('div');
            body.className = 'toast-body';
            var titleEl = document.createElement('div');
            titleEl.className = 'toast-title';
            titleEl.textContent = title;
            body.appendChild(titleEl);
            if (msg) {
                var msgEl = document.createElement('div');
                msgEl.className = 'toast-msg';
                msgEl.textContent = msg;
                body.appendChild(msgEl);
            }
            var el = document.createElement('div');
            el.className = 'toast toast-' + type;
            el.appendChild(svg);
            el.appendChild(body);
            container.appendChild(el);
            setTimeout(function() {
                el.classList.add('hide');
                el.addEventListener('animationend', function() {
                    el.remove();
                });
            }, duration);
        };
    </script>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>
    @endpush

</x-filament-panels::page>