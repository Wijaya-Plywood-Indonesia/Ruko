<x-filament-panels::page>
    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @endpush

    <div class="min-h-full pb-10 transition-colors duration-300">

        {{-- ============================================================ --}}
        {{-- WRAPPER ALPINE: satu x-data di level form agar semua bagian  --}}
        {{-- bisa share state grand total & bayar tanpa round-trip server  --}}
        {{-- ============================================================ --}}
        <div
            x-data="{
                subTotal:  {{ intval($this->sub_total) }},
                diskon:    0,
                ppn:       0,
                ongkir:    0,
                biayaLain: 0,
                bayar:     0,

                get grandTotal() {
                    // Bulatkan tiap komponen sebelum dijumlah — hindari float error
                    return Math.max(0,
                        Math.round(this.subTotal)
                        - Math.round(this.diskon)
                        + Math.round(this.ppn)
                        + Math.round(this.ongkir)
                        + Math.round(this.biayaLain)
                    );
                },

                get sisaBayar() {
                    if (this.grandTotal <= 0) return 0;
                    return Math.round(this.grandTotal) - Math.round(this.bayar);
                },

                get statusBayar() {
                    if (this.bayar <= 0 || this.grandTotal <= 0) return 'none';
                    if (this.sisaBayar > 0)  return 'kurang';
                    if (this.sisaBayar < 0)  return 'kembalian';
                    return 'pas';
                },

                fmt(n) {
                    return Math.round(n).toLocaleString('id-ID');
                },

                onInput(field, el) {
                    const raw = el.value.replace(/\D/g, '');
                    this[field] = raw ? Number(raw) : 0;
                    el.value    = raw ? Number(raw).toLocaleString('id-ID') : '';
                    // Tidak sync ke Livewire saat input agar tidak trigger $watch sub_total
                    // Sync dilakukan saat blur saja (onBlur)
                },

                onBlur(field, el) {
                    const raw = el.value.replace(/\D/g, '');
                    const wireField = {
                        diskon:    'total_diskon',
                        ppn:       'total_ppn',
                        ongkir:    'ongkir',
                        biayaLain: 'biaya_lain',
                        bayar:     'payment_amount',
                    }[field];
                    if (wireField) {
                        $wire.set(wireField, raw ? Number(raw) : null, false);
                    }
                },

                setBayarPas() {
                    const grand = this.grandTotal;
                    this.bayar  = grand;
                    const el = document.getElementById('input-bayar');
                    if (el) el.value = grand > 0 ? grand.toLocaleString('id-ID') : '';
                    $wire.set('payment_amount', grand > 0 ? grand : null, false);
                }
            }"
            x-init="
                $watch('$wire.sub_total', v => { subTotal = Math.round(parseFloat(v) || 0); });
            ">

            <form wire:submit.prevent="simpan" class="flex flex-col xl:flex-row gap-4 sm:gap-6 items-start">

                {{-- ========================================================== --}}
                {{-- BAGIAN KIRI: HEADER PEMBELIAN & DAFTAR BARANG --}}
                {{-- ========================================================== --}}
                <div class="w-full xl:w-[65%] flex flex-col gap-6">

                    {{-- CARD 1: INFORMASI PEMBELIAN (HEADER) --}}
                    <div class="bg-white dark:bg-[#1a1d24] rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-[#20242c] p-4 sm:p-5 border-b border-gray-200 dark:border-gray-800">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                <x-heroicon-o-information-circle class="w-6 h-6 text-primary-500" />
                                Informasi Nota Pembelian
                            </h2>
                        </div>

                        <div class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-x-4 sm:gap-x-6 gap-y-5 sm:gap-y-6">

                            <div class="col-span-1 flex flex-col gap-1.5 sm:gap-2">
                                <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    <x-heroicon-o-hashtag class="w-5 h-5 text-primary-500" /> Nomor Nota <span class="text-danger-500 font-bold">*</span>
                                </label>
                                <input type="text" wire:model="nomor_nota" required placeholder="INV-909921"
                                    class="w-full p-3 sm:p-4 text-base sm:text-lg font-bold bg-white dark:bg-gray-950 border-2 border-gray-300 dark:border-gray-700 focus:ring-4 focus:ring-primary-500/30 focus:border-primary-500 dark:text-white transition-all outline-none rounded-xl" />
                            </div>

                            <div class="col-span-1 flex flex-col gap-1.5 sm:gap-2">
                                <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    <x-heroicon-o-calendar class="w-5 h-5 text-primary-500" /> Tanggal <span class="text-danger-500 font-bold">*</span>
                                </label>
                                <div wire:ignore
                                    x-data="{
                                    init() {
                                        flatpickr(this.$refs.picker, {
                                            locale: 'id', altInput: true, altFormat: 'd F Y', dateFormat: 'Y-m-d', defaultDate: $wire.tanggal,
                                            onChange: (selectedDates, dateStr) => { $wire.tanggal = dateStr; }
                                        });
                                    }
                                 }">
                                    <input x-ref="picker" type="text" placeholder="Pilih Tanggal..."
                                        class="w-full p-3 sm:p-4 text-base sm:text-lg font-bold bg-white dark:bg-gray-950 border-2 border-gray-300 dark:border-gray-700 focus:ring-4 focus:ring-primary-500/30 focus:border-primary-500 dark:text-white transition-all outline-none cursor-pointer rounded-xl" />
                                </div>
                            </div>

                            {{-- Supplier --}}
                            <div class="col-span-1 md:col-span-2 flex flex-col gap-1.5 sm:gap-2 relative"
                                x-data="{
                                isOpen: false,
                                search: '',
                                suppliers: @js(\App\Models\Supplier::select('id', 'nama', 'telepon', 'alamat')->get()),
                                get filteredSuppliers() {
                                    if (this.search === '') return this.suppliers;
                                    return this.suppliers.filter(s => s.nama.toLowerCase().includes(this.search.toLowerCase()));
                                },
                                selectSupplier(sup) {
                                    $wire.supplier_id = sup.id;
                                    $wire.supplier_name = sup.nama;
                                    $wire.supplier_phone = sup.telepon;
                                    $wire.supplier_address = sup.alamat;
                                    $wire.is_new_supplier = false;
                                    this.search = sup.nama;
                                    this.isOpen = false;
                                },
                                createNewSupplier() {
                                    $wire.supplier_id = null;
                                    $wire.supplier_name = this.search;
                                    $wire.supplier_phone = '';
                                    $wire.supplier_address = '';
                                    $wire.is_new_supplier = true;
                                    this.isOpen = false;
                                },
                                clearSearch() {
                                    this.search = '';
                                    $wire.supplier_id = null;
                                    $wire.supplier_name = '';
                                    $wire.supplier_phone = '';
                                    $wire.supplier_address = '';
                                    $wire.is_new_supplier = false;
                                }
                            }"
                                @click.away="isOpen = false">

                                <label class="flex items-center justify-between gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    <span class="flex items-center gap-1.5 sm:gap-2">
                                        <x-heroicon-o-building-storefront class="w-5 h-5 text-primary-500" /> Pilih / Cari Supplier <span class="text-danger-500 font-bold">*</span>
                                    </span>
                                    <span x-show="$wire.is_new_supplier" x-cloak class="text-[10px] sm:text-xs bg-amber-100 text-amber-700 px-2 py-1 font-bold border border-amber-300 uppercase tracking-wider animate-pulse rounded-md">Input Manual</span>
                                </label>

                                <div class="relative flex items-center">
                                    <input type="text" x-model="search" @focus="isOpen = true" placeholder="Ketik nama supplier..."
                                        class="w-full p-3 sm:p-4 text-base sm:text-lg font-bold bg-white dark:bg-gray-950 border-2 border-gray-300 dark:border-gray-700 focus:ring-4 focus:ring-primary-500/30 focus:border-primary-500 dark:text-white transition-all outline-none pr-10 rounded-xl">
                                    <button type="button" x-show="search.length > 0" @click="clearSearch()" x-cloak class="absolute right-3 text-gray-400 hover:text-rose-500 p-1 transition-colors">
                                        <x-heroicon-o-x-mark class="w-6 h-6" stroke-width="3" />
                                    </button>
                                </div>
                                @error('supplier_id') <span class="text-danger-500 text-sm font-bold">{{ $message }}</span> @enderror

                                <div x-show="isOpen" x-transition x-cloak class="absolute top-[85px] sm:top-[95px] z-50 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl max-h-60 overflow-y-auto p-1.5 custom-scroll rounded-xl">
                                    <template x-for="sup in filteredSuppliers" :key="sup.id">
                                        <button type="button" @click="selectSupplier(sup)" class="w-full text-left px-3 py-3 hover:bg-primary-50 dark:hover:bg-primary-900/40 rounded-lg flex flex-col group border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors">
                                            <span class="font-bold text-gray-800 dark:text-gray-200 text-base sm:text-lg group-hover:text-primary-700" x-text="sup.nama"></span>
                                            <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400" x-text="sup.telepon ? sup.telepon : '-'"></span>
                                        </button>
                                    </template>
                                    <button type="button" @click="createNewSupplier()" class="w-full text-left px-3 py-3 mt-1 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/30 dark:hover:bg-amber-900/60 border border-amber-300 dark:border-amber-700 rounded-lg flex items-center gap-3 transition-colors group">
                                        <div class="p-1.5 bg-amber-200 dark:bg-amber-800 rounded-full group-hover:bg-amber-300 dark:group-hover:bg-amber-700 transition-colors">
                                            <x-heroicon-o-plus class="w-5 h-5 text-amber-800 dark:text-amber-200 font-bold" />
                                        </div>
                                        <div>
                                            <span class="font-bold text-amber-800 dark:text-amber-200 text-sm sm:text-base">Tambah "<span x-text="search || 'Baru'"></span>"</span>
                                            <p class="text-[11px] sm:text-xs text-amber-700 dark:text-amber-400 mt-0.5">Klik untuk mengisi data manual</p>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div class="col-span-1 flex flex-col gap-1.5 sm:gap-2">
                                <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    <x-heroicon-o-user class="w-5 h-5 text-primary-500" /> Nama Supplier
                                </label>
                                <div class="relative">
                                    <input type="text" wire:model="supplier_name" placeholder="Otomatis terisi..."
                                        x-bind:disabled="!$wire.is_new_supplier"
                                        x-bind:class="$wire.is_new_supplier ? 'bg-white dark:bg-gray-950 border-amber-300 dark:border-amber-700 focus:border-amber-500 focus:ring-amber-500/30 dark:text-white' : 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 cursor-not-allowed opacity-90'"
                                        class="w-full p-3 sm:p-4 pl-10 sm:pl-12 text-base sm:text-lg font-semibold border-2 text-gray-700 dark:text-gray-300 rounded-xl transition-all outline-none" />
                                    <div x-show="!$wire.is_new_supplier"><x-heroicon-o-lock-closed class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" /></div>
                                    <div x-show="$wire.is_new_supplier" x-cloak><x-heroicon-o-pencil-square class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-amber-500 w-5 h-5" /></div>
                                </div>
                                @error('supplier_name') <span class="text-danger-500 text-sm font-bold">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-span-1 flex flex-col gap-1.5 sm:gap-2">
                                <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    <x-heroicon-o-phone class="w-5 h-5 text-primary-500" /> Telepon Supplier
                                </label>
                                <div class="relative">
                                    <input type="text" wire:model="supplier_phone" placeholder="Otomatis terisi..."
                                        x-bind:disabled="!$wire.is_new_supplier"
                                        x-bind:class="$wire.is_new_supplier ? 'bg-white dark:bg-gray-950 border-amber-300 dark:border-amber-700 focus:border-amber-500 focus:ring-amber-500/30 dark:text-white' : 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 cursor-not-allowed opacity-90'"
                                        class="w-full p-3 sm:p-4 pl-10 sm:pl-12 text-base sm:text-lg font-semibold border-2 text-gray-700 dark:text-gray-300 rounded-xl transition-all outline-none" />
                                    <div x-show="!$wire.is_new_supplier"><x-heroicon-o-lock-closed class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" /></div>
                                    <div x-show="$wire.is_new_supplier" x-cloak><x-heroicon-o-pencil-square class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-amber-500 w-5 h-5" /></div>
                                </div>
                            </div>

                            <div class="col-span-1 md:col-span-2 flex flex-col gap-1.5 sm:gap-2">
                                <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    <x-heroicon-o-map-pin class="w-5 h-5 text-primary-500" /> Alamat Supplier
                                </label>
                                <div class="relative">
                                    <textarea wire:model="supplier_address" rows="1" placeholder="Otomatis terisi..."
                                        x-bind:disabled="!$wire.is_new_supplier"
                                        x-bind:class="$wire.is_new_supplier ? 'bg-white dark:bg-gray-950 border-amber-300 dark:border-amber-700 focus:border-amber-500 focus:ring-amber-500/30 dark:text-white' : 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 cursor-not-allowed opacity-90'"
                                        class="w-full p-3 sm:p-4 pl-10 sm:pl-12 text-base sm:text-lg font-semibold border-2 text-gray-700 dark:text-gray-300 rounded-xl transition-all outline-none resize-none"></textarea>
                                    <div x-show="!$wire.is_new_supplier"><x-heroicon-o-lock-closed class="absolute left-3 sm:left-4 top-4 sm:top-5 text-gray-400 w-5 h-5" /></div>
                                    <div x-show="$wire.is_new_supplier" x-cloak><x-heroicon-o-pencil-square class="absolute left-3 sm:left-4 top-4 sm:top-5 text-amber-500 w-5 h-5" /></div>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- CARD 2: DAFTAR BARANG --}}
                    <div class="bg-white dark:bg-[#1a1d24] rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                        <div class="bg-primary-50 dark:bg-[#20242c] p-4 sm:p-5 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                            <h2 class="text-base sm:text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                <x-heroicon-o-shopping-cart class="w-5 h-5 text-primary-500" /> Daftar Barang (Keranjang)
                            </h2>
                        </div>

                        <div class="p-4 sm:p-6 flex flex-col gap-4">
                            @error('items.*.barang_id') <div class="bg-rose-100 text-rose-700 p-3 rounded-lg font-bold border border-rose-300">{{ $message }}</div> @enderror

                            @foreach($items as $index => $item)
                            <div class="relative p-4 sm:p-5 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-950 flex flex-col gap-4 group">

                                @if(count($items) > 1)
                                <button type="button" wire:click="removeItem({{ $index }})" class="absolute -top-3 -right-3 bg-rose-100 dark:bg-rose-900/80 text-rose-600 dark:text-rose-400 p-2 rounded-full border-2 border-rose-200 dark:border-rose-700 hover:bg-rose-500 hover:text-white transition-colors z-10">
                                    <x-heroicon-o-trash class="w-5 h-5" stroke-width="2" />
                                </button>
                                @endif

                                <div class="grid grid-cols-1 gap-5">
                                    <div class="relative"
                                        x-data="{
                                        isOpen: false,
                                        search: '{{ $item['nama_barang'] ?? '' }}',
                                        allItems: @js(\App\Models\Barang::with('satuan')->get()->map(fn($b) => [
                                            'id'     => $b->id,
                                            'kode'   => $b->kode_barang,
                                            'nama'   => $b->nama_barang,
                                            'satuan' => is_object($b->satuan) ? ($b->satuan->nama ?? $b->satuan->keterangan ?? 'Unit') : ($b->satuan ?? 'Unit'),
                                        ]))
                                     }" @click.away="isOpen = false">

                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Cari Barang</label>
                                        <div class="relative flex items-center">
                                            <input type="text" x-model="search" @focus="isOpen = true" placeholder="Ketik nama atau kode barang..."
                                                class="w-full p-3 font-bold bg-white dark:bg-gray-900 border-2 border-gray-300 dark:border-gray-600 rounded-xl dark:text-white outline-none focus:border-primary-500 transition-all shadow-sm" />
                                            <x-heroicon-o-magnifying-glass class="absolute right-4 text-gray-400 w-5 h-5" />
                                        </div>

                                        <div x-show="isOpen" x-transition class="absolute top-[75px] z-[60] w-full bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 shadow-2xl max-h-56 overflow-y-auto rounded-xl custom-scroll mt-1">
                                            <template x-for="b in allItems.filter(i => i.nama.toLowerCase().includes(search.toLowerCase()) || i.kode.toLowerCase().includes(search.toLowerCase()))" :key="b.id">
                                                <button type="button"
                                                    @click="$wire.set('items.{{ $index }}.barang_id', b.id); $wire.set('items.{{ $index }}.qty', 1); search = b.nama; isOpen = false"
                                                    class="w-full text-left px-4 py-3 hover:bg-primary-50 dark:hover:bg-primary-900/40 border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors">
                                                    <div class="font-bold dark:text-white text-sm" x-text="b.nama"></div>
                                                    <div class="flex justify-between text-[10px] text-gray-500 font-medium">
                                                        <span x-text="'KODE: ' + b.kode"></span>
                                                        <span class="text-primary-600 font-bold" x-text="'Rp ' + b.harga.toLocaleString('id-ID')"></span>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>

                                        @if(!empty($item['kode_barang']))
                                        <span class="text-[10px] font-bold text-primary-500 mt-1 uppercase pl-1 block">Kode: {{ $item['kode_barang'] }}</span>
                                        @endif
                                    </div>

                                    <div class="xl:col-span-9 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <label class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Jumlah (Qty)</label>
                                            {{-- debounce 300ms sudah cukup, tidak perlu 500ms --}}
                                            <input type="number" step="any" min="0"
                                                wire:model.live.debounce.300ms="items.{{ $index }}.qty"
                                                class="w-full p-3 font-bold text-center bg-white dark:bg-gray-900 border-2 border-gray-300 dark:border-gray-600 rounded-lg dark:text-white outline-none focus:border-primary-500" />
                                        </div>

                                        <div>
                                            <label class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Satuan</label>
                                            <input type="text" wire:model="items.{{ $index }}.satuan" placeholder="Pcs / Kg..."
                                                class="w-full p-3 font-bold text-center bg-white dark:bg-gray-900 border-2 border-gray-300 dark:border-gray-600 rounded-lg dark:text-white outline-none focus:border-primary-500 uppercase" />
                                        </div>

                                        {{-- Diskon Item --}}
                                        <div x-data="{
                                            formatted: '',
                                            formatNum(v) { return v ? parseInt(v.toString().replace(/\D/g, '') || 0).toLocaleString('id-ID') : ''; }
                                         }"
                                            x-init="
                                            formatted = formatNum($wire.items[{{ $index }}].diskon);
                                            $watch('$wire.items[{{ $index }}].diskon', v => formatted = formatNum(v))
                                         ">
                                            <label class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Diskon Item (-)</label>
                                            <div class="relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 font-bold text-rose-400 text-xs">Rp</span>
                                                <input type="text" inputmode="numeric" placeholder="0"
                                                    x-bind:value="formatted"
                                                    x-on:input="
                                                    let u = $event.target.value.replace(/\D/g, '');
                                                    $wire.items[{{ $index }}].diskon = u ? u : null;
                                                    formatted = formatNum($wire.items[{{ $index }}].diskon);
                                                    $event.target.value = formatted;
                                                "
                                                    class="w-full p-3 pl-8 font-bold text-right bg-white dark:bg-gray-900 border-2 border-rose-300 dark:border-rose-700 text-rose-600 dark:text-rose-400 rounded-lg outline-none focus:border-rose-500" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center border-t border-gray-200 dark:border-gray-700 pt-3 mt-1">
                                    <input type="text" wire:model="items.{{ $index }}.catatan" placeholder="Catatan opsional untuk barang ini..."
                                        class="w-full sm:w-1/2 p-2 text-sm font-medium bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md dark:text-white outline-none focus:border-primary-500" />

                                    @if(!empty($item['barang_id']))
                                    <div class="w-full sm:w-auto flex justify-between sm:justify-end items-center gap-3 bg-success-50 dark:bg-success-900/20 px-4 py-2 rounded-lg border border-success-200 dark:border-success-800/50">
                                        <span class="text-sm font-bold text-success-700 dark:text-success-400">Subtotal Item:</span>
                                        <span class="text-lg font-black text-success-700 dark:text-success-400">Rp {{ number_format($item['subtotal'] ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach

                            <button type="button" wire:click="addItem"
                                class="w-full py-4 border-2 border-dashed border-primary-400 dark:border-primary-700 rounded-xl font-bold text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 flex items-center justify-center gap-2 transition-colors">
                                <x-heroicon-o-plus class="w-6 h-6" stroke-width="3" /> TAMBAH BARANG LAIN
                            </button>
                        </div>
                    </div>

                    {{-- CARD 3: FILE PENDUKUNG --}}
                    <div class="bg-white dark:bg-[#1a1d24] rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                        <div class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">

                            <div class="col-span-1 md:col-span-2 flex flex-col gap-1.5 sm:gap-2">
                                <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    <x-heroicon-o-photo class="w-5 h-5 text-primary-500" /> Foto Nota (Opsional)
                                </label>
                                <label for="foto-upload" class="w-full p-6 sm:p-8 border-4 border-dashed border-gray-300 dark:border-gray-700 rounded-2xl bg-gray-50 hover:bg-gray-100 dark:bg-gray-950 dark:hover:bg-gray-800 transition-colors cursor-pointer flex flex-col items-center justify-center gap-2 sm:gap-3 text-center relative overflow-hidden">
                                    @if (!$foto_nota)
                                    <div class="p-3 sm:p-4 bg-primary-100 dark:bg-primary-900/40 rounded-full">
                                        <x-heroicon-o-arrow-up-tray class="w-8 h-8 sm:w-10 sm:h-10 text-primary-600 dark:text-primary-400" />
                                    </div>
                                    <div><span class="text-lg sm:text-xl font-bold text-primary-600 dark:text-primary-400 block sm:inline">Sentuh untuk Foto / Pilih File</span></div>
                                    @endif
                                    <input type="file" wire:model="foto_nota" multiple accept="image/*" class="hidden" id="foto-upload" />

                                    @if ($foto_nota)
                                    <div class="w-full flex flex-col items-center">
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 w-full mb-4">
                                            @foreach ($foto_nota as $foto)
                                            <div class="relative rounded-lg overflow-hidden border-2 border-primary-500 shadow-md aspect-square">
                                                <img src="{{ $foto->temporaryUrl() }}" alt="Preview" class="w-full h-full object-cover">
                                            </div>
                                            @endforeach
                                        </div>
                                        <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400 font-bold bg-primary-50 dark:bg-primary-900/40 px-4 py-2 rounded-full">
                                            <x-heroicon-o-arrow-path class="w-5 h-5" /> Sentuh lagi untuk ganti foto
                                        </div>
                                    </div>
                                    @endif

                                    <div wire:loading wire:target="foto_nota" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm flex flex-col items-center justify-center z-10">
                                        <x-heroicon-o-arrow-path class="w-10 h-10 text-primary-500 animate-spin mb-2" />
                                        <span class="font-bold text-primary-600 dark:text-primary-400">Memproses...</span>
                                    </div>
                                </label>
                            </div>

                            <div class="col-span-1 md:col-span-2 flex flex-col gap-1.5 sm:gap-2">
                                <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    <x-heroicon-o-document-text class="w-5 h-5 text-primary-500" /> Catatan Transaksi
                                </label>
                                <textarea wire:model="catatan" rows="3" placeholder="Tulis pesan atau catatan di sini jika ada..."
                                    class="w-full p-3 sm:p-4 text-base sm:text-lg bg-white dark:bg-gray-950 border-2 border-gray-300 dark:border-gray-700 rounded-xl focus:ring-4 focus:ring-primary-500/30 focus:border-primary-500 dark:text-white transition-all resize-none outline-none"></textarea>
                            </div>

                        </div>
                    </div>

                </div>

                {{-- ========================================================== --}}
                {{-- BAGIAN KANAN: RINGKASAN & KASIR                             --}}
                {{-- Semua kalkulasi di sini MURNI Alpine.js — zero server trip  --}}
                {{-- ========================================================== --}}
                <div class="w-full xl:w-[35%] flex flex-col gap-4 sm:gap-6 xl:sticky xl:top-[5.5rem] pb-2">

                    <div class="bg-white dark:bg-[#1a1d24] rounded-2xl shadow-xl border border-gray-200 dark:border-gray-800 overflow-hidden shrink-0">
                        <div class="bg-gray-50 dark:bg-[#20242c] p-4 sm:p-5 border-b border-gray-200 dark:border-gray-800">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                <x-heroicon-o-calculator class="w-6 h-6 text-success-500" />
                                Ringkasan & Kasir
                            </h2>
                        </div>

                        <div class="p-4 sm:p-6 flex flex-col gap-4 sm:gap-5">

                            {{-- Rincian Biaya Global --}}
                            <div class="space-y-3 border-b-2 border-dashed border-gray-200 dark:border-gray-700 pb-4">

                                {{-- Subtotal Barang: reactive dari Alpine $watch sub_total --}}
                                <div class="flex justify-between items-center text-sm sm:text-base">
                                    <span class="font-semibold text-gray-600 dark:text-gray-400">Subtotal Barang</span>
                                    <span class="font-bold dark:text-white" x-text="'Rp ' + fmt(subTotal)"></span>
                                </div>

                                {{-- Diskon Global --}}
                                <div class="flex justify-between items-center text-sm sm:text-base">
                                    <span class="font-semibold text-gray-600 dark:text-gray-400">Diskon Global (−)</span>
                                    <input type="text" inputmode="numeric" placeholder="0"
                                        x-on:input="onInput('diskon', $el)"
                                        class="w-32 p-1.5 text-right font-bold border-2 rounded dark:bg-gray-900 outline-none border-rose-300 dark:border-rose-800 text-rose-600 dark:text-rose-400 focus:border-rose-500" />
                                </div>

                                {{-- PPN --}}
                                <div class="flex justify-between items-center text-sm sm:text-base">
                                    <span class="font-semibold text-gray-600 dark:text-gray-400">Total PPN (+)</span>
                                    <input type="text" inputmode="numeric" placeholder="0"
                                        x-on:input="onInput('ppn', $el)"
                                        class="w-32 p-1.5 text-right font-bold border-2 rounded dark:bg-gray-900 outline-none border-gray-300 dark:border-gray-700 dark:text-white focus:border-success-500" />
                                </div>

                                {{-- Ongkir --}}
                                <div class="flex justify-between items-center text-sm sm:text-base">
                                    <span class="font-semibold text-gray-600 dark:text-gray-400">Ongkos Kirim (+)</span>
                                    <input type="text" inputmode="numeric" placeholder="0"
                                        x-on:input="onInput('ongkir', $el)"
                                        class="w-32 p-1.5 text-right font-bold border-2 rounded dark:bg-gray-900 outline-none border-gray-300 dark:border-gray-700 dark:text-white focus:border-success-500" />
                                </div>

                                {{-- Biaya Lain --}}
                                <div class="flex justify-between items-center text-sm sm:text-base">
                                    <span class="font-semibold text-gray-600 dark:text-gray-400">Biaya Lainnya (+)</span>
                                    <input type="text" inputmode="numeric" placeholder="0"
                                        x-on:input="onInput('biayaLain', $el)"
                                        class="w-32 p-1.5 text-right font-bold border-2 rounded dark:bg-gray-900 outline-none border-gray-300 dark:border-gray-700 dark:text-white focus:border-success-500" />
                                </div>

                            </div>

                            {{-- Grand Total — instan karena Alpine computed property --}}
                            <div class="pt-2">
                                <label class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest block mb-1">TOTAL YANG HARUS DIBAYAR</label>
                                <div class="w-full p-4 bg-success-50 dark:bg-success-900/30 border-2 border-success-400 dark:border-success-600 rounded-xl text-right flex flex-col justify-end">
                                    <span class="text-xs sm:text-sm font-bold text-success-700 dark:text-success-400 block mb-0.5">Rp</span>
                                    <span class="text-4xl sm:text-5xl font-black text-success-700 dark:text-success-400 tracking-tighter break-all"
                                        x-text="fmt(grandTotal)"></span>
                                </div>
                            </div>

                            {{-- Kasir --}}
                            <div class="mt-2 p-4 bg-gray-100 dark:bg-gray-950 rounded-xl border border-gray-300 dark:border-gray-700">
                                <h3 class="font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-o-banknotes class="w-5 h-5 text-primary-500" /> Metode Pembayaran
                                </h3>

                                {{-- Metode: murni Alpine, tidak memicu re-render Livewire --}}
                                <div class="grid grid-cols-2 gap-2 mb-4"
                                    x-data="{ active: '{{ $payment_method }}' }">
                                    @foreach(\App\Models\PembelianMetodePembayaran::labelMetode() as $val => $label)
                                    <button type="button"
                                        @click="active = '{{ $val }}'; $wire.set('payment_method', '{{ $val }}', false)"
                                        :class="active === '{{ $val }}'
                                        ? 'bg-primary-100 border-primary-500 text-primary-700 dark:bg-primary-900/40 dark:border-primary-500 dark:text-primary-300'
                                        : 'bg-white border-gray-200 text-gray-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300'"
                                        class="p-2 sm:p-3 border-2 font-bold text-xs sm:text-sm rounded-lg transition-colors text-left">
                                        {{ $label }}
                                    </button>
                                    @endforeach
                                </div>

                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Tgl Bayar</label>
                                            <input type="date" wire:model="tanggal_bayar"
                                                class="w-full p-2 text-sm font-bold bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md dark:text-white outline-none focus:border-primary-500 [color-scheme:light] dark:[color-scheme:dark]" />
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">No. Bukti / Ref</label>
                                            <input type="text" wire:model="payment_reference" placeholder="Opsional..."
                                                class="w-full p-2 text-sm font-bold bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md dark:text-white outline-none focus:border-primary-500" />
                                        </div>
                                    </div>

                                    {{-- Nominal Bayar — Alpine only, instan --}}
                                    <div>
                                        <div class="flex justify-between items-end mb-1">
                                            <label class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Jumlah Dibayar</label>
                                            {{-- Uang Pas: set Alpine state + update input value langsung --}}
                                            <button type="button" @click="setBayarPas()"
                                                class="text-xs font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 px-2 py-1 rounded hover:bg-primary-100 transition-colors">
                                                Uang Pas
                                            </button>
                                        </div>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-black text-gray-400 text-xl">Rp</span>
                                            <input id="input-bayar" type="text" inputmode="numeric" placeholder="0"
                                                x-on:input="onInput('bayar', $el)"
                                                x-on:blur="onBlur('bayar', $el)"
                                                class="w-full p-4 pl-12 text-2xl font-black text-right bg-white dark:bg-gray-900 border-2 border-primary-300 dark:border-primary-800 rounded-xl text-primary-800 dark:text-primary-300 outline-none focus:border-primary-500 focus:ring-4 focus:ring-primary-500/20" />
                                        </div>
                                    </div>

                                    <div>
                                        <input type="text" wire:model="payment_catatan" placeholder="Catatan kasir opsional..."
                                            class="w-full p-2 text-sm font-medium bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md dark:text-white outline-none focus:border-primary-500" />
                                    </div>
                                </div>

                                {{-- Sisa / Kembalian — pakai statusBayar agar kondisi mutually exclusive --}}
                                <div class="mt-3">
                                    <template x-if="statusBayar === 'kurang'">
                                        <div class="p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-lg flex justify-between items-center">
                                            <span class="font-bold text-rose-700 dark:text-rose-400 text-sm">Kurang / Ngutang:</span>
                                            <span class="font-black text-rose-700 dark:text-rose-400 text-lg" x-text="'Rp ' + fmt(sisaBayar)"></span>
                                        </div>
                                    </template>
                                    <template x-if="statusBayar === 'kembalian'">
                                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg flex justify-between items-center">
                                            <span class="font-bold text-amber-700 dark:text-amber-400 text-sm">Kembalian:</span>
                                            <span class="font-black text-amber-700 dark:text-amber-400 text-lg" x-text="'Rp ' + fmt(Math.abs(sisaBayar))"></span>
                                        </div>
                                    </template>
                                    <template x-if="statusBayar === 'pas'">
                                        <div class="p-3 bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 rounded-lg flex justify-between items-center">
                                            <span class="font-bold text-success-700 dark:text-success-400 text-sm">Lunas ✓</span>
                                            <span class="font-black text-success-700 dark:text-success-400 text-lg">Pas</span>
                                        </div>
                                    </template>
                                </div>

                            </div>

                        </div>
                    </div>

                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 sm:gap-3 bg-primary-600 hover:bg-primary-700 text-white font-bold text-lg sm:text-xl p-4 sm:p-6 rounded-2xl shadow-lg hover:shadow-xl transition-all transform active:scale-95 border-b-4 border-primary-800 shrink-0 mt-2 sm:mt-0">
                        <x-heroicon-o-check class="w-6 h-6 sm:w-7 sm:h-7" stroke-width="3" />
                        SIMPAN TRANSAKSI
                    </button>

                </div>

            </form>
        </div>{{-- end x-data wrapper --}}

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    @endpush
</x-filament-panels::page>