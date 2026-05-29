<x-filament-panels::page>
    @vite(['resources/css/app.css'])

    <div class="min-h-full pb-10 transition-colors duration-300">

        {{-- ============================================================ --}}
        {{-- WRAPPER ALPINE: satu x-data di level form agar semua bagian  --}}
        {{-- bisa share state grand total & bayar tanpa round-trip server  --}}
        {{-- ============================================================ --}}
        <div
            x-data="{
                items: @entangle('items'),
                ongkir: @entangle('ongkir'),
                biayaLain: @entangle('biaya_lain'),
                bayar: @entangle('payment_amount'),
                nomorNota: @entangle('nomor_nota'),
                tanggal: @entangle('tanggal'),
                supplierId: @entangle('supplier_id'),
                supplierName: @entangle('supplier_name'),
                supplierPhone: @entangle('supplier_phone'),
                supplierAddress: @entangle('supplier_address'),
                isNewSupplier: @entangle('is_new_supplier'),
                catatan: @entangle('catatan'),
                paymentMethod: @entangle('payment_method'),
                tanggalBayar: @entangle('tanggal_bayar'),
                paymentReference: @entangle('payment_reference'),
                paymentCatatan: @entangle('payment_catatan'),

                get subTotal() {
                    return this.items.reduce((acc, item) => acc + (parseFloat(item.qty || 0) * parseFloat(item.harga_beli || 0)), 0);
                },

                get grandTotal() {
                    return Math.max(0,
                        Math.round(this.subTotal)
                        + Math.round(parseFloat(this.ongkir) || 0)
                        + Math.round(parseFloat(this.biayaLain) || 0)
                    );
                },

                get sisaBayar() {
                    if (this.grandTotal <= 0) return 0;
                    return Math.round(this.grandTotal) - Math.round(parseFloat(this.bayar) || 0);
                },

                get statusBayar() {
                    let b = parseFloat(this.bayar) || 0;
                    if (b <= 0 || this.grandTotal <= 0) return 'none';
                    if (this.sisaBayar > 0)  return 'kurang';
                    if (this.sisaBayar < 0)  return 'kembalian';
                    return 'pas';
                },

                fmt(n) {
                    return Math.round(n).toLocaleString('id-ID');
                },

                format(val) {
                    if (val === null || val === undefined || val === '' || val == 0) return '0';
                    let cleaned = val.toString().replace(/\D/g, '');
                    return cleaned.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                },

                onInput(field, el) {
                    let raw = el.value.replace(/\D/g, '');
                    this[field] = raw ? parseInt(raw) : 0;
                    el.value = this.format(this[field]);
                },

                setBayarPas() {
                    this.bayar = this.grandTotal;
                },

                saveToLocalStorage() {
                    const state = {
                        items: this.items,
                        nomor_nota: this.nomorNota,
                        tanggal: this.tanggal,
                        supplier_id: this.supplierId,
                        supplier_name: this.supplierName,
                        supplier_phone: this.supplierPhone,
                        supplier_address: this.supplierAddress,
                        is_new_supplier: this.isNewSupplier,
                        catatan: this.catatan,
                        sub_total: this.subTotal,
                        ongkir: this.ongkir,
                        biaya_lain: this.biayaLain,
                        payment_method: this.paymentMethod,
                        payment_amount: this.bayar,
                        tanggal_bayar: this.tanggalBayar,
                        payment_reference: this.paymentReference,
                        payment_catatan: this.paymentCatatan
                    };
                    localStorage.setItem('pembelian_state', JSON.stringify(state));
                },

                init() {
                    // State will be saved dynamically via form-level input/change bubbling
                    // and after successful Livewire commits. No active watches needed,
                    // which completely prevents reactivity conflicts and field lockups.
                }
            }">

            <form wire:submit.prevent="simpan" 
                @input.debounce.500ms="saveToLocalStorage()"
                @change="saveToLocalStorage()"
                class="pos-pro-dashboard flex flex-col gap-4 lg:gap-6">

                {{-- MAIN INFO BAR --}}
                <div class="flex flex-wrap lg:flex-nowrap items-center justify-between bg-white dark:bg-gray-900 px-4 py-4 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm gap-x-6 gap-y-2">
                    <div class="flex flex-wrap lg:flex-nowrap items-center gap-4 lg:gap-6 w-full lg:w-auto">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Nomor Nota:</span>
                            <div class="flex flex-col">
                                <input type="text" x-model="nomorNota" required class="px-2 py-1 bg-gray-50/50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded text-sm font-black text-primary-600 font-mono focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 w-36 transition-all" />
                                @error('nomor_nota')
                                    <span class="text-[10px] text-red-500 font-bold mt-0.5">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="h-8 w-px bg-gray-100 dark:bg-gray-800 hidden sm:block"></div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Tanggal:</span>
                            <div class="flex flex-col">
                                <input type="date" x-model="tanggal" required class="px-2 py-1 bg-gray-50/50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded text-xs font-medium text-gray-600 dark:text-gray-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all" />
                                @error('tanggal')
                                    <span class="text-[10px] text-red-500 font-bold mt-0.5">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="h-8 w-px bg-gray-100 dark:bg-gray-800 hidden sm:block"></div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Kasir:</span>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase">{{ $created_by_name }}</span>
                        </div>
                    </div>
                    <div class="hidden lg:flex items-center gap-2 bg-gray-50 dark:bg-gray-800/50 px-3 py-1.5 rounded-lg border border-gray-100 dark:border-gray-700">
                        <x-heroicon-s-building-storefront class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
                        <span class="text-[10px] font-bold text-gray-600 dark:text-gray-400 uppercase tracking-tight">PEMBELIAN STOCK</span>
                    </div>
                </div>

                {{-- TWO-COLUMN CONTENT GRID --}}
                <div class="flex flex-col xl:flex-row gap-4 xl:gap-6">

                    {{-- LEFT COLUMN: OPERATIONAL --}}
                    <div class="w-full xl:w-[68%] flex flex-col gap-4 xl:gap-6 order-1">

                        {{-- CARD: INFORMASI SUPPLIER --}}
                        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm p-4 sm:p-6 space-y-4">
                            <div class="flex justify-between items-center border-b border-gray-50 dark:border-gray-800 pb-3 bg-transparent">
                                <h3 class="text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-widest flex items-center gap-2">
                                    <x-heroicon-o-building-storefront class="w-4 h-4 text-primary-500" /> Informasi Supplier
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                                <div class="md:col-span-2 flex flex-col gap-1.5 relative">
                                    <div wire:ignore
                                        x-data="{
                                            isOpen: false,
                                            search: '',
                                            suppliers: @js(\App\Models\Supplier::select('id', 'nama', 'telepon', 'alamat')->get()),
                                            init() {
                                                this.search = $wire.supplier_name || '';
                                                this.$watch('$wire.supplier_name', value => {
                                                    this.search = value || '';
                                                });
                                            },
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
                                        @click.away="isOpen = false"
                                        class="flex flex-col gap-1.5 relative">

                                        <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide ml-1 flex items-center justify-between">
                                            <span>Cari Supplier <span class="text-danger-500 font-bold">*</span></span>
                                            <span x-show="isNewSupplier" x-cloak class="text-[9px] bg-amber-100 text-amber-700 px-1.5 py-0.5 font-bold border border-amber-300 uppercase tracking-wider rounded">Input Manual</span>
                                        </label>

                                        <div class="relative flex items-center">
                                            <input type="text" x-model="search" @focus="isOpen = true" placeholder="Ketik nama supplier..."
                                                class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg pl-3 pr-8 py-1.5 text-xs focus:ring-2 focus:ring-primary-500/20 dark:text-white transition-all outline-none">
                                            <button type="button" x-show="search.length > 0" @click="clearSearch()" x-cloak class="absolute right-2 text-gray-400 hover:text-rose-500 p-0.5 transition-colors">
                                                <x-heroicon-o-x-mark class="w-4 h-4" stroke-width="3" />
                                            </button>
                                        </div>

                                        <div x-show="isOpen" x-transition x-cloak class="absolute top-[55px] z-50 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl max-h-48 overflow-y-auto p-1 rounded-lg">
                                            <template x-for="sup in filteredSuppliers" :key="sup.id">
                                                <button type="button" @click="selectSupplier(sup)" class="w-full text-left px-3 py-2 hover:bg-primary-50 dark:hover:bg-primary-900/40 rounded flex flex-col group border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors">
                                                    <span class="font-bold text-gray-800 dark:text-gray-200 text-xs group-hover:text-primary-700" x-text="sup.nama"></span>
                                                    <span class="text-[10px] text-gray-500 dark:text-gray-400" x-text="sup.telepon ? sup.telepon : '-'"></span>
                                                </button>
                                            </template>
                                            <button type="button" @click="createNewSupplier()" class="w-full text-left px-3 py-2 mt-1 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/30 dark:hover:bg-amber-900/60 border border-amber-300 dark:border-amber-700 rounded flex items-center gap-2 transition-colors group">
                                                <div class="p-1 bg-amber-200 dark:bg-amber-800 rounded-full group-hover:bg-amber-300 dark:group-hover:bg-amber-700 transition-colors">
                                                    <x-heroicon-o-plus class="w-3.5 h-3.5 text-amber-800 dark:text-amber-200 font-bold" />
                                                </div>
                                                <div>
                                                    <span class="font-bold text-amber-800 dark:text-amber-200 text-xs">Tambah "<span x-text="search || 'Baru'"></span>"</span>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                    @error('supplier_id')
                                        <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide ml-1">Nama Supplier</label>
                                    <div class="relative">
                                        <input type="text" x-model="supplierName" placeholder="Nama..."
                                            x-bind:disabled="!isNewSupplier"
                                            x-bind:class="isNewSupplier ? 'bg-white dark:bg-gray-950 border-amber-300 dark:border-amber-700 focus:ring-2 focus:ring-amber-500/20 dark:text-white' : 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 cursor-not-allowed opacity-90'"
                                            class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 text-xs focus:ring-2 focus:ring-primary-500/20 dark:text-white" />
                                    </div>
                                    @error('supplier_name')
                                        <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide ml-1">Telepon Supplier</label>
                                    <div class="relative">
                                        <input type="text" x-model="supplierPhone" placeholder="Telepon..."
                                            x-bind:disabled="!isNewSupplier"
                                            x-bind:class="isNewSupplier ? 'bg-white dark:bg-gray-950 border-amber-300 dark:border-amber-700 focus:ring-2 focus:ring-amber-500/20 dark:text-white' : 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 cursor-not-allowed opacity-90'"
                                            class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 text-xs focus:ring-2 focus:ring-primary-500/20 dark:text-white" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide ml-1">Alamat Supplier</label>
                                <textarea x-model="supplierAddress" rows="1" placeholder="Alamat..."
                                    x-bind:disabled="!isNewSupplier"
                                    x-bind:class="isNewSupplier ? 'bg-white dark:bg-gray-950 border-amber-300 dark:border-amber-700 focus:ring-2 focus:ring-amber-500/20 dark:text-white' : 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 cursor-not-allowed opacity-90'"
                                    class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 text-xs focus:ring-2 focus:ring-primary-500/20 dark:text-white resize-none"></textarea>
                            </div>
                        </div>

                        {{-- SEARCH BARANG CARD --}}
                        <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm"
                            x-data
                            @click.outside="$wire.closeDropdown()">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-2 flex items-center pointer-events-none text-gray-400">
                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                </div>
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="search"
                                    placeholder="Cari barang untuk dibeli / barcode... (/)"
                                    id="pembelian-search-input"
                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg !pl-8 pr-4 py-2 text-sm focus:ring-2 focus:ring-primary-500/20 transition-all"
                                    @keydown.slash.window.prevent="document.getElementById('pembelian-search-input').focus()"
                                    wire:focus="openDropdown" />

                                @if($showDropdown && !empty($searchResults))
                                <div class="absolute inset-x-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 max-h-60 overflow-y-auto">
                                    @foreach($searchResults as $barang)
                                    <div wire:click="selectBarang({{ $barang['id'] }})" class="px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer flex justify-between items-center border-b border-gray-50 dark:border-gray-700 last:border-0">
                                        <div>
                                            <div class="font-semibold text-sm text-gray-900 dark:text-white">{{ $barang['nama_barang'] }}</div>
                                            <div class="text-[10px] text-gray-500 uppercase">{{ $barang['kode_barang'] }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-bold text-sm text-primary-600">Rp{{ number_format($barang['harga_beli']) }}</div>
                                            <div class="text-[9px] text-gray-400">Satuan: {{ $barang['satuan'] }}</div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- CART TABLE / CARDS --}}
                        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-800 flex justify-between items-center bg-gray-50/30 dark:bg-gray-800/30">
                                <h3 class="text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-widest flex items-center gap-2">
                                    <x-heroicon-o-shopping-cart class="w-4 h-4 text-primary-500" /> Item Pembelian
                                </h3>
                                <span class="text-[10px] font-black text-primary-600 bg-primary-50 dark:bg-primary-900/40 px-3 py-1 rounded-full uppercase">{{ count($items) }} Items</span>
                            </div>

                            @error('items')
                            <div class="bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 p-4 m-4 rounded-r-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <x-heroicon-s-x-circle class="h-5 w-5 text-red-500" />
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-xs font-bold text-red-700 dark:text-red-400">{{ $message }}</p>
                                    </div>
                                </div>
                            </div>
                            @enderror

                            <div>
                                {{-- Desktop Table --}}
                                <div class="hidden md:block min-w-full">
                                    <table class="w-full text-left table-fixed border-collapse">
                                        <thead class="bg-gray-50/50 dark:bg-gray-800/50">
                                            <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                                <th class="px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider w-[25%]">Item</th>
                                                <th class="px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-center w-[22%]">Qty</th>
                                                <th class="px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-center w-[8%]">Satuan</th>
                                                <th class="px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right w-[22%]">Harga Beli</th>
                                                <th class="px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right w-[20%]">Total</th>
                                                <th class="px-2 py-2 w-[3%]"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            @forelse($items as $index => $item)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors group">

                                                <td class="px-2 py-2 align-middle">
                                                    <div class="font-medium text-sm text-gray-900 dark:text-white line-clamp-1">
                                                        {{ $item['nama_barang'] }}
                                                    </div>
                                                </td>

                                                <td class="px-2 py-2 align-middle">
                                                    <div class="flex items-center justify-center bg-gray-100/50 dark:bg-gray-800 rounded-md p-0.5 border border-gray-200 dark:border-gray-700"
                                                         x-data="{
                                                             get qty() {
                                                                return items[{{ $index }}].qty;
                                                             },
                                                             set qty(val) {
                                                                items[{{ $index }}].qty = val;
                                                             },
                                                             format(val) {
                                                                if (val === null || val === undefined || val === '') return '';
                                                                let str = val.toString();
                                                                if (typeof val === 'number') {
                                                                    let parts = str.split('.');
                                                                    let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                                                    return parts[1] !== undefined ? integerPart + ',' + parts[1] : integerPart;
                                                                }
                                                                let clean = str.replace(/[^0-9,]/g, '');
                                                                let parts = clean.split(',');
                                                                let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                                                return parts[1] !== undefined ? integerPart + ',' + parts[1] : integerPart;
                                                             }
                                                         }">
                                                         <button @click="
                                                                 let q = Math.max(0.01, parseFloat(qty || 1) - 1);
                                                                 qty = q;
                                                             " type="button" class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-primary-600">
                                                             <x-heroicon-o-minus class="w-3 h-3" />
                                                         </button>
                                                         <input type="text"
                                                             :value="format(qty)"
                                                             @input="
                                                                 let inputVal = $event.target.value;
                                                                 let clean = inputVal.replace(/[^0-9,]/g, '');
                                                                 let raw = clean.replace(',', '.');
                                                                 qty = raw ? parseFloat(raw) : 0;
                                                                 $el.value = format(clean);
                                                             "
                                                             class="w-28 text-center border-none bg-transparent p-0 text-xs font-bold focus:ring-0 dark:text-white" />
                                                         <button @click="
                                                                 let q = parseFloat(qty || 1) + 1;
                                                                 qty = q;
                                                             " type="button" class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-primary-600">
                                                             <x-heroicon-o-plus class="w-3 h-3" />
                                                         </button>
                                                     </div>
                                                </td>

                                                <td class="px-2 py-2 text-center align-middle">
                                                    <span class="text-[10px] font-bold bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 px-2 py-1 rounded uppercase border border-gray-100 dark:border-gray-700">
                                                        {{ $item['satuan'] }}
                                                    </span>
                                                </td>

                                                <td class="px-2 py-2 align-middle" x-data="{
                                                        get harga() { return items[{{ $index }}].harga_beli; },
                                                        set harga(v) { items[{{ $index }}].harga_beli = v; }
                                                    }">
                                                    <input
                                                        type="text"
                                                        :value="format(harga)"
                                                        @input="
                                                                let raw = $event.target.value.replace(/\D/g, '');
                                                                harga = raw ? parseInt(raw) : 0;
                                                                $el.value = format(harga);
                                                            "
                                                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md py-1 px-2 text-xs font-bold text-right focus:ring-2 focus:ring-primary-500/10 dark:text-white outline-none" />
                                                </td>

                                                <td class="px-2 py-2 text-right font-bold text-sm text-primary-600 dark:text-primary-400 align-middle">
                                                    <span x-text="'Rp ' + fmt(parseFloat(items[{{ $index }}].qty || 0) * parseFloat(items[{{ $index }}].harga_beli || 0))"></span>
                                                </td>

                                                <td class="px-2 py-2 text-center align-middle">
                                                    <button wire:click="removeItem({{ $index }})" type="button" class="text-gray-300 hover:text-red-500 transition-colors">
                                                        <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                                    </button>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="py-12">
                                                    <div class="flex flex-col items-center justify-center w-full">
                                                        <x-heroicon-o-shopping-bag class="w-10 h-10 text-gray-200 dark:text-gray-750 mb-1" />
                                                        <span class="text-[10px] font-black uppercase text-gray-400 dark:text-gray-600 tracking-[0.2em]">Belum ada barang</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Mobile Cards --}}
                                <div class="md:hidden divide-y divide-gray-50 dark:divide-gray-800">
                                    @foreach($items as $index => $item)
                                     <div class="p-4 space-y-3" x-data="{
                                             get qty() {
                                                return items[{{ $index }}].qty;
                                             },
                                             set qty(val) {
                                                items[{{ $index }}].qty = val;
                                             },
                                             get harga() {
                                                return items[{{ $index }}].harga_beli;
                                             },
                                             set harga(val) {
                                                items[{{ $index }}].harga_beli = val;
                                             },
                                             get subtotal() {
                                                return parseFloat(this.qty || 0) * parseFloat(this.harga || 0);
                                             },
                                             formatQty(val) {
                                                if (val === null || val === undefined || val === '') return '';
                                                let str = val.toString();
                                                if (typeof val === 'number') {
                                                    let parts = str.split('.');
                                                    let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                                    return parts[1] !== undefined ? integerPart + ',' + parts[1] : integerPart;
                                                }
                                                let clean = str.replace(/[^0-9,]/g, '');
                                                let parts = clean.split(',');
                                                let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                                return parts[1] !== undefined ? integerPart + ',' + parts[1] : integerPart;
                                             },
                                             formatHarga(val) {
                                                if (val === null || val === undefined || val === '') return '';
                                                let cleaned = val.toString().replace(/\D/g, '');
                                                return cleaned.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                             }
                                         }">
                                         <div class="flex justify-between items-start gap-3">
                                             <div class="flex-grow">
                                                 <div class="font-bold text-sm text-gray-900 dark:text-white leading-tight">{{ $item['nama_barang'] }}</div>
                                                 <div class="text-[10px] text-gray-400 font-bold uppercase mt-1">{{ $item['satuan'] }}</div>
                                             </div>
                                             <button wire:click="removeItem({{ $index }})" type="button" class="text-gray-300 hover:text-red-500 p-1"><x-heroicon-o-trash class="w-4 h-4" /></button>
                                         </div>
                                         <div class="grid grid-cols-2 gap-4">
                                             <div class="space-y-1">
                                                 <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Qty</label>
                                                 <div class="flex items-center bg-gray-50 dark:bg-gray-800 rounded-xl p-1 border border-gray-100 dark:border-gray-700">
                                                     <button @click="qty = Math.max(0.01, parseFloat(qty || 1) - 1)" type="button" class="w-8 h-8 flex items-center justify-center text-gray-400"><x-heroicon-o-minus class="w-3.5 h-3.5" /></button>
                                                     <input type="text"
                                                         :value="formatQty(qty)"
                                                         @input="
                                                             let inputVal = $event.target.value;
                                                             let clean = inputVal.replace(/[^0-9,]/g, '');
                                                             let raw = clean.replace(',', '.');
                                                             qty = raw ? parseFloat(raw) : 0;
                                                             $el.value = formatQty(clean);
                                                         "
                                                         class="w-full text-center border-none bg-transparent p-0 text-xs font-black focus:ring-0 dark:text-white" />
                                                     <button @click="qty = parseFloat(qty || 1) + 1" type="button" class="w-8 h-8 flex items-center justify-center text-gray-400"><x-heroicon-o-plus class="w-3.5 h-3.5" /></button>
                                                 </div>
                                             </div>
                                             <div class="space-y-1">
                                                 <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Harga Beli</label>
                                                 <input type="text"
                                                     :value="formatHarga(harga)"
                                                     @input="
                                                         let raw = $event.target.value.replace(/\D/g, '');
                                                         harga = raw ? parseInt(raw) : 0;
                                                         $el.value = formatHarga(harga);
                                                     "
                                                     class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl py-2 px-3 text-sm font-black text-right focus:ring-2 focus:ring-primary-500/10 dark:text-white transition-all outline-none" />
                                             </div>
                                             <div class="space-y-1 text-right col-span-2 bg-gray-50 dark:bg-gray-800/30 p-2.5 rounded-xl">
                                                 <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Subtotal</label>
                                                 <div class="text-sm font-black text-primary-600 dark:text-primary-400" x-text="'Rp ' + fmt(subtotal)"></div>
                                             </div>
                                         </div>
                                     </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- CARD: FILE PENDUKUNG & CATATAN --}}
                        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide ml-1">Foto Nota</label>
                                <label for="foto-upload" class="w-full p-4 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 hover:bg-gray-100 dark:bg-gray-950 dark:hover:bg-gray-800 transition-colors cursor-pointer flex flex-col items-center justify-center gap-2 text-center relative overflow-hidden min-h-[100px]">
                                    @if (!$foto_nota)
                                    <div class="p-2 bg-primary-100 dark:bg-primary-900/40 rounded-full">
                                        <x-heroicon-o-arrow-up-tray class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                    </div>
                                    <div><span class="text-xs font-bold text-primary-600 dark:text-primary-400">Pilih / Foto Nota</span></div>
                                    @endif
                                    <input type="file" wire:model="foto_nota" multiple accept="image/*" class="hidden" id="foto-upload" />

                                    @if ($foto_nota)
                                    <div class="w-full flex flex-col items-center">
                                        <div class="grid grid-cols-3 gap-2 w-full mb-2">
                                            @foreach ($foto_nota as $foto)
                                            <div class="relative rounded-lg overflow-hidden border border-primary-500 shadow aspect-square">
                                                <img src="{{ $foto->temporaryUrl() }}" alt="Preview" class="w-full h-full object-cover">
                                            </div>
                                            @endforeach
                                        </div>
                                        <div class="flex items-center gap-1 text-[10px] text-primary-600 dark:text-primary-400 font-bold bg-primary-50 dark:bg-primary-900/40 px-2.5 py-1 rounded-full">
                                            <x-heroicon-o-arrow-path class="w-3 h-3" /> Ganti foto
                                        </div>
                                    </div>
                                    @endif

                                    <div wire:loading wire:target="foto_nota" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm flex flex-col items-center justify-center z-10">
                                        <x-heroicon-o-arrow-path class="w-6 h-6 text-primary-500 animate-spin mb-1" />
                                        <span class="text-[10px] font-bold text-primary-600 dark:text-primary-400">Memproses...</span>
                                    </div>
                                </label>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide ml-1">Catatan Transaksi</label>
                                <textarea x-model="catatan" rows="4" placeholder="Tulis catatan di sini..."
                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 text-xs focus:ring-2 focus:ring-primary-500/20 dark:text-white resize-none h-[100px] outline-none"></textarea>
                            </div>
                        </div>

                    </div>

                    {{-- RIGHT COLUMN: CHECKOUT PANEL --}}
                    <div class="w-full xl:w-[32%] flex flex-col gap-4 xl:gap-6 xl:sticky xl:top-[5.5rem] pb-2 order-2">

                        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden shrink-0">
                            <div class="p-4 lg:p-5 bg-primary-600 dark:bg-black text-white relative overflow-hidden shrink-0 transition-colors duration-300">
                                <div class="relative z-10">
                                    <span class="text-[9px] font-bold uppercase tracking-widest text-primary-100 dark:text-primary-500 block mb-1">TOTAL YANG HARUS DIBAYAR</span>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-sm font-bold opacity-50 dark:opacity-30">Rp</span>
                                        <span class="text-2xl lg:text-3xl font-black tracking-tight leading-none" x-text="fmt(grandTotal)"></span>
                                    </div>
                                </div>
                                <div class="absolute -right-6 -bottom-6 opacity-10 dark:opacity-5"><x-heroicon-s-banknotes class="w-24 h-24" /></div>
                            </div>

                            <div class="p-4 sm:p-6 flex flex-col gap-4">
                                {{-- Rincian Biaya Global --}}
                                <div class="space-y-3 border-b-2 border-dashed border-gray-100 dark:border-gray-800 pb-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Subtotal Barang</span>
                                        <span class="font-black text-sm text-gray-955 dark:text-white" x-text="'Rp ' + fmt(subTotal)"></span>
                                    </div>

                                    {{-- Ongkir --}}
                                    <div class="flex justify-between items-center"
                                         x-data="{
                                             ongkirInput: format(ongkir),
                                             init() {
                                                 this.$watch('ongkir', v => {
                                                     this.ongkirInput = format(v);
                                                 });
                                             }
                                         }">
                                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Ongkos Kirim (+)</span>
                                        <div class="relative flex items-center">
                                            <span class="absolute left-2.5 text-[10px] font-bold text-gray-400">Rp</span>
                                            <input type="text"
                                                x-model="ongkirInput"
                                                @input="
                                                    let raw = $event.target.value.replace(/\D/g, '');
                                                    ongkir = raw ? parseInt(raw) : 0;
                                                    ongkirInput = format(ongkir);
                                                "
                                                class="w-36 pl-7 pr-2.5 py-1 text-right font-black text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg dark:text-white focus:ring-2 focus:ring-primary-500/10 focus:border-primary-500 transition-all outline-none" />
                                        </div>
                                    </div>

                                    {{-- Biaya Lain --}}
                                    <div class="flex justify-between items-center"
                                         x-data="{
                                             biayaLainInput: format(biayaLain),
                                             init() {
                                                 this.$watch('biayaLain', v => {
                                                     this.biayaLainInput = format(v);
                                                 });
                                             }
                                         }">
                                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Biaya Lainnya (+)</span>
                                        <div class="relative flex items-center">
                                            <span class="absolute left-2.5 text-[10px] font-bold text-gray-400">Rp</span>
                                            <input type="text"
                                                x-model="biayaLainInput"
                                                @input="
                                                    let raw = $event.target.value.replace(/\D/g, '');
                                                    biayaLain = raw ? parseInt(raw) : 0;
                                                    biayaLainInput = format(biayaLain);
                                                "
                                                class="w-36 pl-7 pr-2.5 py-1 text-right font-black text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg dark:text-white focus:ring-2 focus:ring-primary-500/10 focus:border-primary-500 transition-all outline-none" />
                                        </div>
                                    </div>
                                </div>

                                {{-- Kasir / Pembayaran Section --}}
                                <div class="space-y-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                                    <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-wider flex items-center gap-1.5 ml-1">
                                        <x-heroicon-o-banknotes class="w-4 h-4 text-primary-500" /> Metode Pembayaran
                                    </h3>

                                    {{-- Metode --}}
                                    <div class="grid grid-cols-2 gap-1 p-1 bg-gray-100/50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                        @foreach(\App\Models\PembelianMetodePembayaran::labelMetode() as $val => $label)
                                        <button type="button"
                                            @click="paymentMethod = '{{ $val }}'"
                                            :class="paymentMethod === '{{ $val }}'
                                                    ? 'bg-white dark:bg-gray-700 shadow-sm text-primary-600 font-black'
                                                    : 'text-gray-500'"
                                            class="py-1.5 rounded-md text-[10px] font-bold uppercase transition-all text-center">
                                            {{ $label }}
                                        </button>
                                        @endforeach
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-wider ml-1">Tgl Bayar</label>
                                            <input type="date" x-model="tanggalBayar"
                                                class="w-full p-2 text-xs font-bold bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg dark:text-white outline-none focus:ring-2 focus:ring-primary-500/10 focus:border-primary-500 transition-all" />
                                        </div>
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-wider ml-1">No. Bukti / Ref</label>
                                            <input type="text" x-model="paymentReference" placeholder="Opsional..."
                                                class="w-full p-2 text-xs font-bold bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg dark:text-white outline-none focus:ring-2 focus:ring-primary-500/10 focus:border-primary-500 transition-all" />
                                        </div>
                                    </div>

                                    {{-- Nominal Bayar & Kurang/Kembali Card (Gaya POS) --}}
                                    <div class="space-y-1.5 bg-gray-50/50 dark:bg-gray-900 p-3 rounded-lg border border-gray-100 dark:border-gray-800"
                                         x-data="{
                                             bayarInput: format(bayar),
                                             init() {
                                                 this.$watch('bayar', v => {
                                                     this.bayarInput = format(v);
                                                 });
                                             }
                                         }">

                                        <div class="flex justify-between items-center">
                                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide ml-1">Nominal Bayar</label>
                                            <button type="button" @click="setBayarPas()" class="text-[9px] font-bold text-primary-600 hover:underline uppercase tracking-wide">Bayar Pas</button>
                                        </div>
                                        <div class="flex items-center gap-1.5 border-b border-primary-500 pb-0.5">
                                            <span class="text-lg font-bold text-primary-600">Rp</span>
                                            <input
                                                type="text"
                                                x-model="bayarInput"
                                                @input="
                                                    let raw = $event.target.value.replace(/\D/g, '');
                                                    bayar = raw ? parseInt(raw) : 0;
                                                    bayarInput = format(bayar);
                                                "
                                                class="w-full bg-transparent border-none p-0 text-xl lg:text-2xl font-black focus:ring-0 tracking-tight dark:text-white"
                                                placeholder="0" />
                                        </div>

                                        <div class="pt-0.5 flex justify-between items-center" x-show="grandTotal > 0">
                                            <span class="text-[9px] font-bold text-gray-400 uppercase ml-1" 
                                                x-text="sisaBayar > 0 ? 'Kurang (Hutang)' : (sisaBayar < 0 ? 'Kembali' : 'Pas')"></span>
                                            <span class="text-base lg:text-lg font-bold"
                                                :class="sisaBayar > 0 ? 'text-red-500' : (sisaBayar < 0 ? 'text-green-500' : 'text-primary-600')"
                                                x-text="fmt(Math.abs(sisaBayar))">
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Catatan Kasir --}}
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-wider ml-1">Catatan Kasir</label>
                                        <input type="text" x-model="paymentCatatan" placeholder="Catatan kasir opsional..."
                                            class="w-full p-2 text-xs font-semibold bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg dark:text-white outline-none focus:ring-2 focus:ring-primary-500/10 focus:border-primary-500 transition-all" />
                                    </div>
                                </div>
                            </div>

                            {{-- Card Footer Button --}}
                            <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/20">
                                <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-bold text-sm py-2.5 rounded-lg shadow-sm transition-all active:translate-y-0.5 tracking-wide shrink-0">
                                    <x-heroicon-o-check class="w-4 h-4 stroke-[3px]" />
                                    SIMPAN TRANSAKSI
                                </button>
                            </div>
                        </div>

                    </div>

                </div>

            </form>
        </div>{{-- end x-data wrapper --}}

    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            // 1. Restore the full state from localStorage if it exists
            const savedState = localStorage.getItem('pembelian_state');
            if (savedState) {
                try {
                    const state = JSON.parse(savedState);
                    if (state && state.items && Object.keys(state.items).length > 0) {
                        @this.dispatch('restoreState', { state: state });
                    }
                } catch (e) {
                    console.error('Failed to parse Pembelian state:', e);
                }
            }

            // 2. Automatically save the full state to localStorage on every Livewire update/render using dynamic properties
            Livewire.hook('commit', ({ component, succeed }) => {
                succeed(() => {
                    if (component.id === @this.id) {
                        const state = {
                            items: component.$wire.items,
                            nomor_nota: component.$wire.nomor_nota,
                            tanggal: component.$wire.tanggal,
                            supplier_id: component.$wire.supplier_id,
                            supplier_name: component.$wire.supplier_name,
                            supplier_phone: component.$wire.supplier_phone,
                            supplier_address: component.$wire.supplier_address,
                            is_new_supplier: component.$wire.is_new_supplier,
                            catatan: component.$wire.catatan,
                            sub_total: component.$wire.sub_total,
                            total_diskon: component.$wire.total_diskon,
                            total_ppn: component.$wire.total_ppn,
                            ongkir: component.$wire.ongkir,
                            biaya_lain: component.$wire.biaya_lain,
                            payment_method: component.$wire.payment_method,
                            payment_amount: component.$wire.payment_amount,
                            tanggal_bayar: component.$wire.tanggal_bayar,
                            payment_reference: component.$wire.payment_reference,
                            payment_catatan: component.$wire.payment_catatan
                        };
                        localStorage.setItem('pembelian_state', JSON.stringify(state));
                    }
                });
            });

            // 3. Clear state on successful transaction save
            window.addEventListener('clearLocalStorage', () => {
                localStorage.removeItem('pembelian_state');
            });
        });
    </script>
</x-filament-panels::page>