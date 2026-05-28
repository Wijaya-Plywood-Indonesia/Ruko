<x-filament::page>
    @vite(['resources/css/app.css'])

    <div class="pos-pro-dashboard min-h-screen -m-8 p-8 bg-gray-100 dark:bg-gray-950 flex flex-col gap-4 lg:gap-6" x-data="{ search: @entangle('search') }">
        

        {{-- MAIN INFO BAR --}}
        <div class="flex flex-wrap items-center justify-between bg-white dark:bg-gray-900 px-4 py-4 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm gap-x-6 gap-y-2">
            <div class="flex flex-wrap items-center gap-6 w-full lg:w-auto">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Nota:</span>
                    <input type="text" wire:model.live="no_nota" class="px-2 py-1 bg-gray-50/50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded text-sm font-black text-primary-600 font-mono focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 w-32 transition-all" />
                </div>
                <div class="h-8 w-px bg-gray-100 dark:bg-gray-800 hidden sm:block"></div>
                <div class="flex items-center gap-2 flex-grow min-w-[200px]">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Catatan:</span>
                    <input type="text" wire:model.live="keterangan_nota" placeholder="Tambahkan catatan nota..." class="px-2 py-1 bg-gray-50/50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded text-sm text-gray-600 dark:text-gray-400 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 w-full placeholder:text-gray-300 dark:placeholder:text-gray-700 transition-all" />
                </div>
                <div class="h-8 w-px bg-gray-100 dark:bg-gray-800 hidden sm:block"></div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Kasir:</span>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ auth()->user()->name }}</span>
                </div>
                <div class="h-8 w-px bg-gray-100 dark:bg-gray-800 hidden sm:block"></div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Waktu:</span>
                    <input type="datetime-local" wire:model.live="tanggal" class="px-2 py-1 bg-gray-50/50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded text-xs font-medium text-gray-600 dark:text-gray-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all" />
                </div>
            </div>
            <div class="hidden lg:flex items-center gap-2 bg-gray-50 dark:bg-gray-800/50 px-3 py-1.5 rounded-lg border border-gray-100 dark:border-gray-700">
                <x-heroicon-s-building-storefront class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
                <span class="text-[10px] font-bold text-gray-600 dark:text-gray-400 uppercase tracking-tight">{{ $namaToko }}</span>
            </div>
        </div>

        <div class="flex flex-col xl:flex-row gap-4 xl:gap-6">
            
            {{-- LEFT SECTION: OPERATIONAL --}}
            <div class="w-full xl:w-[68%] flex flex-col gap-4 xl:gap-6 order-1">
                
                {{-- SEARCH CARD --}}
                <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm" @click.outside="$wire.set('showDropdown', false)">
                    <div class="space-y-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-2 flex items-center pointer-events-none text-gray-400">
                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                            </div>
                            <input 
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Cari barang / barcode... (/)"
                                id="pos-search-input"
                                class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg !pl-8 pr-4 py-2 text-sm focus:ring-2 focus:ring-primary-500/20 transition-all"
                                @keydown.slash.window.prevent="document.getElementById('pos-search-input').focus()"
                                wire:focus="openDropdown"
                            />

                             @if($showDropdown)
                                <div wire:key="search-dropdown-results" class="absolute inset-x-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 max-h-60 overflow-y-auto">
                                    @foreach($searchResults as $barang)
                                        <div wire:key="search-item-{{ $barang->id }}" wire:click="selectBarang({{ $barang->id }})" class="px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer flex justify-between items-center border-b border-gray-50 dark:border-gray-700 last:border-0">
                                            <div>
                                                <div class="font-semibold text-sm text-gray-900 dark:text-white">
                                                    {{ $barang->nama_barang }}
                                                    @if($barang->satuan)
                                                        <span class="text-[10px] text-gray-500 font-bold ml-1">({{ $barang->satuan->nama_satuan }})</span>
                                                    @endif
                                                </div>
                                                <div class="text-[10px] text-gray-500 uppercase">{{ $barang->barcode }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-bold text-sm text-primary-600">Rp{{ number_format($barang->harga_jual) }}</div>
                                                <div class="text-[9px] text-gray-400">Stok: {{ number_format($barang->stok_aktual, 2) }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div wire:key="col-tipe-pelanggan" class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide ml-1">Tipe Pelanggan</label>
                                <div class="grid grid-cols-2 gap-1 p-1 bg-gray-100/50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <button type="button" wire:click="toggleMember(0)" class="py-1.5 rounded-md text-[10px] font-bold uppercase transition-all {{ !$is_member ? 'bg-white dark:bg-gray-700 shadow-sm text-primary-600' : 'text-gray-500 dark:text-gray-400' }}">Umum</button>
                                    <button type="button" wire:click="toggleMember(1)" class="py-1.5 rounded-md text-[10px] font-bold uppercase transition-all {{ $is_member ? 'bg-white dark:bg-gray-700 shadow-sm text-primary-600' : 'text-gray-500 dark:text-gray-400' }}">Member</button>
                                </div>
                            </div>
                            
                            @if($is_member)
                            <div wire:key="col-kode-member" class="flex flex-col gap-1.5 relative" @click.outside="if ($wire.customerResults && $wire.customerResults.length) $wire.set('customerResults', [])">
                                <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide ml-1">Kode Member / Cari</label>
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.300ms="kode_member" 
                                    placeholder="Ketik nama/kode/telepon..." 
                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-primary-500/20 dark:text-white" 
                                />
                                
                                {{-- Customer Search Results Dropdown --}}
                                @if(!empty($customerResults))
                                    <div wire:key="col-customer-results-dropdown" class="absolute top-full left-0 right-0 z-50 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                                        @foreach($customerResults as $res)
                                            <button 
                                                type="button"
                                                wire:click="selectCustomer({{ $res->id }})"
                                                wire:key="cust-res-{{ $res->id }}"
                                                class="w-full px-4 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-50 dark:border-gray-800 last:border-0 transition-colors"
                                            >
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-bold dark:text-white">{{ $res->nama }}</span>
                                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">{{ $res->nik }} | {{ $res->telepon }}</span>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @endif

                            <div wire:key="col-customer-info-{{ $is_member ? 'member' : 'umum' }}" class="{{ $is_member ? 'md:col-span-2' : 'md:col-span-3' }} flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide ml-1">Nama & Alamat Terpilih</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" wire:model.live="nama_customer" placeholder="Nama..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-primary-500/20 dark:text-white" />
                                    <input type="text" wire:model.live="alamat" placeholder="Alamat..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-primary-500/20 dark:text-white" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CART TABLE / CARDS --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-800 flex justify-between items-center bg-gray-50/30 dark:bg-gray-800/30">
                        <h3 class="text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-widest">Item Belanja</h3>
                        <span class="text-[10px] font-black text-primary-600 bg-primary-50 dark:bg-primary-900/40 px-3 py-1 rounded-full uppercase">{{ count($cart) }} Items</span>
                    </div>
                    
                    <div>
                        {{-- Desktop Table --}}
                        <div class="hidden md:block w-full overflow-x-auto">
                            <table class="w-full min-w-[700px] text-left table-fixed border-collapse">
                                <thead class="bg-gray-50/50 dark:bg-gray-800/50">
                                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                        <th class="px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider w-[25%]">Item</th>
                                        <th class="px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-center w-[22%]">Qty</th>
                                        <th class="px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right w-[18%]">H. Jual</th>
                                        <th class="px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right w-[16%]">Potongan</th>
                                        <th class="px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right w-[16%]">Total</th>
                                        <th class="px-2 py-2 w-[3%]"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($cart as $id => $item)
                                        <tr wire:key="cart-item-desktop-{{ $id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                                            <td class="px-2 py-2">
                                                <div class="font-medium text-sm text-gray-900 dark:text-white line-clamp-1">
                                                    {{ $item['nama_barang'] }} 
                                                    <span class="text-[10px] text-gray-400 font-normal ml-1">• Rp{{ number_format($item['harga_awal']) }}</span>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2">
                                                <div class="flex items-center justify-center gap-1.5" x-data="{
                                                    qty: @entangle('cart.'.$id.'.qty'),
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
                                                    <div class="flex items-center justify-center bg-gray-100/50 dark:bg-gray-800 rounded-md p-0.5 border border-gray-200 dark:border-gray-700">
                                                        <button wire:click="decrementQty({{ $id }})" class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-primary-600"><x-heroicon-o-minus class="w-3 h-3" /></button>
                                                        <input type="text"
                                                            :value="format(qty)"
                                                            @input="
                                                                let inputVal = $event.target.value;
                                                                let clean = inputVal.replace(/[^0-9,]/g, '');
                                                                let raw = clean.replace(',', '.');
                                                                qty = raw ? parseFloat(raw) : 0;
                                                                $el.value = format(clean);
                                                            "
                                                            @change="$wire.updateQty({{ $id }})"
                                                            class="w-20 text-center border-none bg-transparent p-0 text-xs font-bold focus:ring-0" />
                                                        <button wire:click="incrementQty({{ $id }})" class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-primary-600"><x-heroicon-o-plus class="w-3 h-3" /></button>
                                                    </div>
                                                    <span class="text-xs text-gray-500 font-bold whitespace-nowrap">{{ $item['satuan'] }}</span>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2" x-data="{
                                                harga: @entangle('cart.'.$id.'.harga_jual'),
                                                format(val) {
                                                    if (val === null || val === undefined || val === '') return '';
                                                    let cleaned = val.toString().replace(/\D/g, '');
                                                    return cleaned.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                                }
                                            }">
                                                <input type="text"
                                                    :value="format(harga)"
                                                    @input="
                                                        let raw = $event.target.value.replace(/\D/g, '');
                                                        harga = raw ? parseInt(raw) : 0;
                                                        $el.value = format(harga);
                                                    "
                                                    @change="$wire.updateHargaJual({{ $id }})"
                                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md py-1 px-2 text-xs font-bold text-right focus:ring-2 focus:ring-primary-500/10" />
                                            </td>
                                            <td class="px-2 py-2" x-data="{
                                                potongan: @entangle('cart.'.$id.'.potongan'),
                                                format(val) {
                                                    if (val === null || val === undefined || val === '') return '';
                                                    let cleaned = val.toString().replace(/\D/g, '');
                                                    return cleaned.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                                }
                                            }">
                                                <input type="text"
                                                    :value="format(potongan)"
                                                    @input="
                                                        let raw = $event.target.value.replace(/\D/g, '');
                                                        potongan = raw ? parseInt(raw) : 0;
                                                        $el.value = format(potongan);
                                                    "
                                                    @change="$wire.updatePotongan({{ $id }})"
                                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md py-1 px-2 text-xs font-bold text-right text-red-500 focus:ring-2 focus:ring-red-500/10" />
                                            </td>
                                            <td class="px-2 py-2 text-right font-bold text-sm text-primary-600">
                                                {{ number_format($item['subtotal']) }}
                                            </td>
                                            <td class="px-2 py-2 text-center">
                                                <button wire:click="removeFromCart({{ $id }})" class="text-gray-300 hover:text-red-500 transition-colors">
                                                    <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Mobile Cards --}}
                        <div class="md:hidden divide-y divide-gray-50 dark:divide-gray-800">
                            @forelse($cart as $id => $item)
                                <div wire:key="cart-item-mobile-{{ $id }}" class="p-4 space-y-3">
                                    <div class="flex justify-between items-start gap-3">
                                        <div class="flex-grow">
                                            <div class="font-bold text-sm text-gray-900 dark:text-white leading-tight">{{ $item['nama_barang'] }}</div>
                                            <div class="text-[10px] text-gray-400 font-bold uppercase mt-1">{{ $item['satuan'] }} • Rp{{ number_format($item['harga_awal']) }}</div>
                                        </div>
                                        <button wire:click="removeFromCart({{ $id }})" class="text-gray-300 hover:text-red-500 p-1"><x-heroicon-o-trash class="w-4 h-4" /></button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Qty</label>
                                            <div class="flex items-center bg-gray-50 dark:bg-gray-800 rounded-xl p-1 border border-gray-100 dark:border-gray-700" x-data="{
                                                qty: @entangle('cart.'.$id.'.qty'),
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
                                                <button wire:click="decrementQty({{ $id }})" class="w-8 h-8 flex items-center justify-center text-gray-400"><x-heroicon-o-minus class="w-3.5 h-3.5" /></button>
                                                <input type="text"
                                                    :value="format(qty)"
                                                    @input="
                                                        let inputVal = $event.target.value;
                                                        let clean = inputVal.replace(/[^0-9,]/g, '');
                                                        let raw = clean.replace(',', '.');
                                                        qty = raw ? parseFloat(raw) : 0;
                                                        $el.value = format(clean);
                                                    "
                                                    @change="$wire.updateQty({{ $id }})"
                                                    class="w-full text-center border-none bg-transparent p-0 text-xs font-black focus:ring-0" />
                                                <button wire:click="incrementQty({{ $id }})" class="w-8 h-8 flex items-center justify-center text-gray-400"><x-heroicon-o-plus class="w-3.5 h-3.5" /></button>
                                            </div>
                                        </div>
                                        <div class="space-y-1" x-data="{
                                            harga: @entangle('cart.'.$id.'.harga_jual'),
                                            format(val) {
                                                if (val === null || val === undefined || val === '') return '';
                                                let cleaned = val.toString().replace(/\D/g, '');
                                                return cleaned.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                            }
                                        }">
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">H. Jual</label>
                                            <input type="text"
                                                :value="format(harga)"
                                                @input="
                                                    let raw = $event.target.value.replace(/\D/g, '');
                                                    harga = raw ? parseInt(raw) : 0;
                                                    $el.value = format(harga);
                                                "
                                                @change="$wire.updateHargaJual({{ $id }})"
                                                class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl py-2 px-3 text-sm font-black text-right focus:ring-2 focus:ring-primary-500/10 transition-all" />
                                        </div>
                                        <div class="space-y-1" x-data="{
                                            potongan: @entangle('cart.'.$id.'.potongan'),
                                            format(val) {
                                                if (val === null || val === undefined || val === '') return '';
                                                let cleaned = val.toString().replace(/\D/g, '');
                                                return cleaned.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                            }
                                        }">
                                            <label class="text-[10px] font-black text-red-400 uppercase tracking-widest ml-1">Diskon</label>
                                            <input type="text"
                                                :value="format(potongan)"
                                                @input="
                                                    let raw = $event.target.value.replace(/\D/g, '');
                                                    potongan = raw ? parseInt(raw) : 0;
                                                    $el.value = format(potongan);
                                                "
                                                @change="$wire.updatePotongan({{ $id }})"
                                                class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl py-2 px-3 text-sm font-black text-right text-red-500 focus:ring-2 focus:ring-red-500/10 transition-all" />
                                        </div>
                                        <div class="space-y-1 text-right">
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Subtotal</label>
                                            <div class="py-1.5 font-black text-sm text-primary-600">Rp{{ number_format($item['subtotal']) }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="py-20 text-center opacity-20"><x-heroicon-o-shopping-bag class="w-12 h-12 mx-auto mb-2" /><span class="text-xs font-black uppercase">Kosong</span></div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT SECTION: CHECKOUT --}}
            <div id="checkout-section" class="w-full xl:w-[32%] order-2">
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 shadow-lg flex flex-col overflow-hidden"
                     x-data="{ 
                        total: @entangle('total'),
                        bayar: @entangle('bayar'),
                        bayar_tunai: @entangle('bayar_tunai'),
                        bayar_transfer: @entangle('bayar_transfer'),
                        metode: @entangle('metode_pembayaran'),
                        format(val) { 
                            if (val === null || val === undefined || val === '' || val == 0) return '0';
                            let cleaned = val.toString().replace(/\D/g, '');
                            return cleaned.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        },
                        get currentTotalBayar() {
                            if (this.metode === 'TUNAI & TRANSFER') {
                                return (parseInt(this.bayar_tunai) || 0) + (parseInt(this.bayar_transfer) || 0);
                            }
                            return parseInt(this.bayar) || 0;
                        },
                        get diff() {
                            return Math.abs(this.currentTotalBayar - this.total);
                        },
                        get isKurang() {
                            return this.currentTotalBayar < this.total;
                        }
                     }">
                    <div class="p-4 lg:p-5 bg-primary-600 dark:bg-black text-white relative overflow-hidden shrink-0 transition-colors duration-300">
                        <div class="relative z-10">
                            <span class="text-[9px] font-bold uppercase tracking-widest text-primary-100 dark:text-primary-500 block mb-1">Grand Total</span>
                            <div class="flex items-baseline gap-1">
                                <span class="text-sm font-bold opacity-50 dark:opacity-30">Rp</span>
                                <span class="text-2xl lg:text-3xl font-black tracking-tight leading-none">{{ number_format($this->total) }}</span>
                            </div>
                        </div>
                        <div class="absolute -right-6 -bottom-6 opacity-10 dark:opacity-5"><x-heroicon-s-banknotes class="w-24 h-24" /></div>
                    </div>

                    <div class="p-4 lg:p-5 space-y-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide block ml-1">Metode Pembayaran</label>
                            <div class="grid grid-cols-3 gap-1 p-1 bg-gray-100/50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                <button wire:click="$set('metode_pembayaran', 'TUNAI')" class="py-1.5 rounded-md text-[10px] font-bold uppercase transition-all {{ $metode_pembayaran === 'TUNAI' ? 'bg-white dark:bg-gray-700 shadow-sm text-primary-600' : 'text-gray-500' }}">Tunai</button>
                                <button wire:click="$set('metode_pembayaran', 'TRANSFER')" class="py-1.5 rounded-md text-[10px] font-bold uppercase transition-all {{ $metode_pembayaran === 'TRANSFER' ? 'bg-white dark:bg-gray-700 shadow-sm text-primary-600' : 'text-gray-500' }}">Transfer</button>
                                <button wire:click="$set('metode_pembayaran', 'TUNAI & TRANSFER')" class="py-1.5 rounded-md text-[10px] font-bold uppercase transition-all {{ $metode_pembayaran === 'TUNAI & TRANSFER' ? 'bg-white dark:bg-gray-700 shadow-sm text-primary-600' : 'text-gray-500' }}">Tunai & Transfer</button>
                            </div>
                            @if($metode_pembayaran === 'TRANSFER' || $metode_pembayaran === 'TUNAI & TRANSFER')
                                <div wire:key="payment-bank-selector" class="space-y-2">
                                    <div class="relative">
                                        <select wire:model.live="rekening_perusahaan_id" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 pr-8 text-sm focus:ring-2 focus:ring-primary-500/10 cursor-pointer">
                                            <option value="">Pilih Bank...</option>
                                            @foreach($rekeningPerusahaan as $rek)
                                                <option value="{{ $rek->id }}">{{ $rek->atas_nama }} | {{ $rek->nama_bank }} | {{ $rek->no_rekening }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if($selectedBank)
                                        <div wire:key="payment-selected-bank-details" class="p-2 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-100 dark:border-primary-800/50">
                                            <div class="flex flex-col">
                                                <span class="text-[8px] font-bold text-primary-600 dark:text-primary-400 uppercase">Rekening Atas Nama</span>
                                                <span class="text-xs font-bold text-gray-700 dark:text-gray-200">{{ $selectedBank->atas_nama }}</span>
                                                <div class="mt-1 flex justify-between items-center">
                                                    <span class="text-sm font-black text-primary-700 dark:text-primary-300 font-mono tracking-tight">{{ $selectedBank->no_rekening }}</span>
                                                    <span class="text-[8px] font-bold px-1.5 py-0.5 bg-primary-200 dark:bg-primary-800 rounded text-primary-800 dark:text-primary-200 uppercase">{{ $selectedBank->nama_bank }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="space-y-1.5 bg-gray-50/50 dark:bg-gray-900 p-3 rounded-lg border border-gray-100 dark:border-gray-800">
                            
                            @if($metode_pembayaran === 'TUNAI & TRANSFER')
                                <div wire:key="payment-split-fields" class="flex flex-wrap gap-3 mb-2">
                                    <div class="flex-1 min-w-[140px] space-y-1">
                                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Tunai (Cash)</label>
                                        <div class="flex items-center gap-1 border-b border-primary-500 pb-0.5">
                                            <span class="text-xs font-bold text-primary-600">Rp</span>
                                            <input 
                                                type="text" 
                                                :value="format(bayar_tunai)"
                                                @input="
                                                    let raw = $event.target.value.replace(/\D/g, '');
                                                    bayar_tunai = raw ? parseInt(raw) : 0;
                                                    $el.value = format(bayar_tunai);
                                                "
                                                class="w-full bg-transparent border-none p-0 text-lg font-black focus:ring-0 tracking-tight dark:text-white text-gray-900" 
                                            />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-[140px] space-y-1">
                                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Transfer</label>
                                        <div class="flex items-center gap-1 border-b border-primary-500 pb-0.5">
                                            <span class="text-xs font-bold text-primary-600">Rp</span>
                                            <input 
                                                type="text" 
                                                :value="format(bayar_transfer)"
                                                @input="
                                                    let raw = $event.target.value.replace(/\D/g, '');
                                                    bayar_transfer = raw ? parseInt(raw) : 0;
                                                    $el.value = format(bayar_transfer);
                                                "
                                                class="w-full bg-transparent border-none p-0 text-lg font-black focus:ring-0 tracking-tight dark:text-white text-gray-900" 
                                            />
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div wire:key="payment-single-fields" class="space-y-1">
                                    <div class="flex justify-between items-center">
                                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide ml-1">Nominal Bayar</label>
                                    </div>
                                    <div class="flex items-center gap-1.5 border-b border-primary-500 pb-0.5">
                                        <span class="text-lg font-bold text-primary-600">Rp</span>
                                        <input 
                                            type="text" 
                                            :value="format(bayar)"
                                            @input="
                                                let raw = $event.target.value.replace(/\D/g, '');
                                                bayar = raw ? parseInt(raw) : 0;
                                                $el.value = format(bayar);
                                            "
                                            class="w-full bg-transparent border-none p-0 text-xl lg:text-2xl font-black focus:ring-0 tracking-tight" 
                                            placeholder="0" 
                                        />
                                    </div>
                                </div>
                            @endif

                            <div class="pt-0.5 flex justify-between items-center">
                                <span class="text-[9px] font-bold text-gray-400 uppercase ml-1" x-text="isKurang ? 'Kurang' : 'Kembali'"></span>
                                <span class="text-base lg:text-lg font-bold" 
                                      :class="isKurang ? 'text-red-500' : 'text-green-500'"
                                      x-text="format(diff)">
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide block ml-1">Catatan Pembayaran</label>
                            <textarea wire:model.live="keterangan_pembayaran" rows="2" placeholder="Catatan pembayaran..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-primary-500/10 dark:text-white dark:placeholder-gray-600"></textarea>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide block ml-1">Pengiriman</label>
                            <select wire:model.live="metode_pengiriman" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-primary-500/10 cursor-pointer transition-all dark:text-white">
                                <option value="DIBAWA_SENDIRI">Dibawa Sendiri</option>
                                <option value="DIKIRIM">Dikirim oleh Kami</option>
                            </select>
                        </div>

                        @if($metode_pengiriman === 'DIKIRIM')
                            <div wire:key="shipping-fields-container" class="space-y-3 pt-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide block ml-1">Kendaraan</label>
                                        <input type="text" wire:model.live="kendaraan" placeholder="Kendaraan..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-primary-500/10 dark:text-white" />
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide block ml-1">Plat Kendaraan</label>
                                        <input type="text" wire:model.live="plat_kendaraan" placeholder="Plat..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-primary-500/10 dark:text-white" />
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide block ml-1">Nama Sopir</label>
                                    <input type="text" wire:model.live="nama_sopir" placeholder="Nama Sopir..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-primary-500/10 dark:text-white" />
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/20">
                        <div class="flex gap-2">
                            <button 
                                @click="
                                    $wire.set('bayar', bayar);
                                    $wire.set('bayar_tunai', bayar_tunai);
                                    $wire.set('bayar_transfer', bayar_transfer);
                                    $wire.simpanPenjualan();
                                "
                                class="flex-grow py-2.5 btn-primary text-white rounded-lg font-bold text-sm active:translate-y-0.5 transition-all  tracking-wide"
                                @keydown.window.f8.prevent="
                                    $wire.set('bayar', bayar);
                                    $wire.set('bayar_tunai', bayar_tunai);
                                    $wire.set('bayar_transfer', bayar_transfer);
                                    $wire.simpanPenjualan();
                                "
                            >
                               Save
                            </button>
                            <button wire:click="resetPos" class="py-2.5 px-4 py-1 btn-danger text-white text-sm font-bold  tracking-wide transition-all rounded-lg">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .pos-pro-dashboard { font-family: 'Inter', sans-serif; }
        
        #pos-search-input { padding-left: 2rem !important; }
        
        .btn-primary { background-color: #d97706 !important; }
        .btn-primary:hover { background-color: #b45309 !important; }
        .btn-danger { background-color: #dc2626 !important; }
        .btn-danger:hover { background-color: #b91c1c !important; }
        
        .dark .btn-primary { border-color: #92400e !important; }
        .dark .btn-danger { border-color: #991b1b !important; }
        
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        
        input:focus, select:focus, textarea:focus { outline: none; }
        
        .overflow-y-auto::-webkit-scrollbar { width: 3px; }
        .overflow-y-auto::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        
        @media (max-width: 1024px) { 
            .pos-pro-dashboard { height: auto; overflow: visible; } 
        }
    </style>

    <script>
        document.addEventListener('livewire:initialized', () => {
            // 1. Restore the full state from localStorage if it exists
            const savedState = localStorage.getItem('pos_state');
            if (savedState) {
                try {
                    const state = JSON.parse(savedState);
                    if (state && state.cart && Object.keys(state.cart).length > 0) {
                        @this.dispatch('restoreState', { state: state });
                    }
                } catch (e) {
                    console.error('Failed to parse POS state:', e);
                }
            }

            // 2. Automatically save the full state to localStorage on every Livewire update/render
            Livewire.hook('commit', ({ component, succeed }) => {
                succeed(() => {
                    if (component.id === @this.id) {
                        const state = {
                            cart: @this.cart,
                            is_member: @this.is_member,
                            pembeli_id: @this.pembeli_id,
                            nama_customer: @this.nama_customer,
                            alamat: @this.alamat,
                            telepon: @this.telepon,
                            kode_member: @this.kode_member,
                            metode_pembayaran: @this.metode_pembayaran,
                            bayar: @this.bayar,
                            bayar_tunai: @this.bayar_tunai,
                            bayar_transfer: @this.bayar_transfer,
                            rekening_perusahaan_id: @this.rekening_perusahaan_id,
                            keterangan_pembayaran: @this.keterangan_pembayaran,
                            keterangan_nota: @this.keterangan_nota,
                            metode_pengiriman: @this.metode_pengiriman,
                            kendaraan: @this.kendaraan,
                            plat_kendaraan: @this.plat_kendaraan,
                            nama_sopir: @this.nama_sopir
                        };
                        localStorage.setItem('pos_state', JSON.stringify(state));
                    }
                });
            });
        });
    </script>
</x-filament::page>
