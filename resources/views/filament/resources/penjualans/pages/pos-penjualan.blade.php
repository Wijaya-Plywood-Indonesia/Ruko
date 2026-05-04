<x-filament::page>
    @vite(['resources/css/app.css'])

    <div class="pos-pro-dashboard flex flex-col gap-4 lg:gap-6" x-data="{ search: @entangle('search') }">
        

        {{-- MAIN INFO BAR --}}
        <div class="flex flex-wrap items-center justify-between bg-white dark:bg-gray-900 px-4 py-3 lg:px-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm gap-x-6 gap-y-3">
            <div class="flex items-center gap-6">
                <div class="flex flex-col">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Nota</span>
                    <span class="text-xs lg:text-sm font-black text-primary-600 font-mono">{{ $no_nota }}</span>
                </div>
                <div class="h-6 w-px bg-gray-100 dark:bg-gray-800 hidden sm:block"></div>
                <div class="flex flex-col">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Kasir</span>
                    <span class="text-xs lg:text-sm font-bold text-gray-700 dark:text-gray-200">{{ auth()->user()->name }}</span>
                </div>
                <div class="h-6 w-px bg-gray-100 dark:bg-gray-800 hidden sm:block"></div>
                <div class="flex flex-col">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Waktu</span>
                    <span class="text-xs lg:text-sm font-medium text-gray-600 dark:text-gray-400">{{ \Carbon\Carbon::parse($tanggal)->format('d/m/Y H:i') }}</span>
                </div>
            </div>
            <div class="hidden lg:flex items-center gap-2 bg-primary-50 dark:bg-primary-900/20 px-4 py-2 rounded-xl border border-primary-100 dark:border-primary-800">
                <x-heroicon-o-building-storefront class="w-4 h-4 text-primary-600" />
                <span class="text-xs font-black text-primary-700 dark:text-primary-400 uppercase tracking-wider">{{ $namaToko }}</span>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            
            {{-- LEFT SECTION: OPERATIONAL --}}
            <div class="w-full lg:w-[68%] flex flex-col gap-4 lg:gap-6 order-2 lg:order-1">
                
                {{-- SEARCH CARD --}}
                <div class="bg-white dark:bg-gray-900 p-4 lg:p-5 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <div class="space-y-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                            </div>
                            <input 
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Cari barang / barcode... (/)"
                                id="pos-search-input"
                                class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl pl-12 pr-4 py-3 lg:py-4 text-sm font-medium focus:ring-2 focus:ring-primary-500/20"
                                @keydown.slash.window.prevent="document.getElementById('pos-search-input').focus()"
                                wire:focus="openDropdown"
                            />

                            @if($showDropdown)
                                <div class="absolute inset-x-0 mt-2 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-2xl z-50 max-h-80 overflow-y-auto">
                                    @foreach($searchResults as $barang)
                                        <div wire:click="selectBarang({{ $barang->id }})" class="p-4 hover:bg-primary-50 dark:hover:bg-primary-900/10 cursor-pointer flex justify-between items-center border-b border-gray-50 dark:border-gray-800 last:border-0">
                                            <div>
                                                <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $barang->nama_barang }}</div>
                                                <div class="text-[10px] text-gray-400">{{ $barang->barcode }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-black text-sm text-primary-600">Rp{{ number_format($barang->harga_jual) }}</div>
                                                <div class="text-[9px] font-bold text-gray-400 uppercase">Stok: {{ number_format($barang->stok_aktual, 2) }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Tipe Pelanggan</label>
                                <div class="grid grid-cols-2 gap-1 p-1 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                                    <button wire:click="$set('is_member', 0)" class="py-2 rounded-lg text-[10px] font-black uppercase transition-all {{ !$is_member ? 'bg-white dark:bg-gray-700 shadow-sm text-primary-600' : 'text-gray-400' }}">Umum</button>
                                    <button wire:click="$set('is_member', 1)" class="py-2 rounded-lg text-[10px] font-black uppercase transition-all {{ $is_member ? 'bg-white dark:bg-gray-700 shadow-sm text-primary-600' : 'text-gray-400' }}">Member</button>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Identitas</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" wire:model.live="nama_customer" placeholder="Nama..." class="w-full min-w-0 bg-gray-50 dark:bg-gray-800 border-none rounded-xl py-2 px-3 text-xs font-bold focus:ring-2 focus:ring-primary-500/20" />
                                    <input type="text" wire:model.live="alamat" placeholder="Alamat..." class="w-full min-w-0 bg-gray-50 dark:bg-gray-800 border-none rounded-xl py-2 px-3 text-xs font-bold focus:ring-2 focus:ring-primary-500/20" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CART TABLE / CARDS --}}
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-800 flex justify-between items-center bg-gray-50/30 dark:bg-gray-800/30">
                        <h3 class="text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-widest">Item Belanja</h3>
                        <span class="text-[10px] font-black text-primary-600 bg-primary-50 dark:bg-primary-900/40 px-3 py-1 rounded-full uppercase">{{ count($cart) }} Items</span>
                    </div>
                    
                    <div>
                        {{-- Desktop Table --}}
                        <div class="hidden md:block min-w-full">
                            <table class="w-full text-left table-fixed border-collapse">
                                <thead class="bg-white dark:bg-gray-900 sticky top-0 z-10">
                                    <tr class="text-[10px] uppercase font-black text-gray-400 border-b border-gray-50 dark:border-gray-800">
                                        <th class="w-[35%] px-6 py-4">Barang</th>
                                        <th class="w-[15%] px-4 py-4 text-center">Qty</th>
                                        <th class="w-[15%] px-4 py-4 text-right">Harga</th>
                                        <th class="w-[15%] px-4 py-4 text-right text-red-400">Pot.</th>
                                        <th class="w-[15%] px-4 py-4 text-right">Subtotal</th>
                                        <th class="w-[5%] px-6 py-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                    @foreach($cart as $id => $item)
                                        <tr class="hover:bg-primary-50/20 dark:hover:bg-primary-900/10">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $item['nama_barang'] }}</div>
                                                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter">{{ $item['satuan'] }} • Rp{{ number_format($item['harga_awal']) }}</div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-center bg-gray-50 dark:bg-gray-800 rounded-lg p-1 border border-gray-100 dark:border-gray-700">
                                                    <button wire:click="decrementQty({{ $id }})" class="w-6 h-6 flex items-center justify-center text-gray-400"><x-heroicon-o-minus class="w-3 h-3" /></button>
                                                    <input type="number" wire:model.lazy="cart.{{ $id }}.qty" wire:change="updateQty({{ $id }})" class="w-10 text-center border-none bg-transparent p-0 text-xs font-black" />
                                                    <button wire:click="incrementQty({{ $id }})" class="w-6 h-6 flex items-center justify-center text-gray-400"><x-heroicon-o-plus class="w-3 h-3" /></button>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-right">
                                                <input type="number" wire:model.lazy="cart.{{ $id }}.harga_jual" wire:change="updateHargaJual({{ $id }})" class="w-full text-right bg-transparent border-b border-gray-100 dark:border-gray-700 p-0 text-xs font-black" />
                                            </td>
                                            <td class="px-4 py-4 text-right">
                                                <input type="number" wire:model.lazy="cart.{{ $id }}.potongan" wire:change="updatePotongan({{ $id }})" class="w-full text-right bg-transparent border-b border-red-100 dark:border-red-900 p-0 text-xs font-black text-red-500" />
                                            </td>
                                            <td class="px-4 py-4 text-right font-black text-sm text-gray-900 dark:text-white">Rp{{ number_format($item['subtotal']) }}</td>
                                            <td class="px-6 py-4 text-right"><button wire:click="removeFromCart({{ $id }})" class="text-gray-300 hover:text-red-500"><x-heroicon-o-trash class="w-4 h-4" /></button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Mobile Cards --}}
                        <div class="md:hidden divide-y divide-gray-50 dark:divide-gray-800">
                            @forelse($cart as $id => $item)
                                <div class="p-4 space-y-3">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $item['nama_barang'] }}</div>
                                            <div class="text-[10px] text-gray-400 uppercase">{{ $item['satuan'] }} • H. Awal: Rp{{ number_format($item['harga_awal']) }}</div>
                                        </div>
                                        <button wire:click="removeFromCart({{ $id }})" class="text-red-300"><x-heroicon-o-trash class="w-4 h-4" /></button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-1">
                                            <label class="text-[9px] font-black text-gray-400 uppercase">Qty</label>
                                            <div class="flex items-center bg-gray-50 dark:bg-gray-800 rounded-lg p-1">
                                                <button wire:click="decrementQty({{ $id }})" class="w-8 h-8 flex items-center justify-center text-gray-400"><x-heroicon-o-minus class="w-3.5 h-3.5" /></button>
                                                <input type="number" wire:model.lazy="cart.{{ $id }}.qty" wire:change="updateQty({{ $id }})" class="w-full text-center border-none bg-transparent p-0 text-xs font-black" />
                                                <button wire:click="incrementQty({{ $id }})" class="w-8 h-8 flex items-center justify-center text-gray-400"><x-heroicon-o-plus class="w-3.5 h-3.5" /></button>
                                            </div>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[9px] font-black text-gray-400 uppercase">H. Jual</label>
                                            <input type="number" wire:model.lazy="cart.{{ $id }}.harga_jual" wire:change="updateHargaJual({{ $id }})" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-lg py-2 px-3 text-xs font-black text-right" />
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[9px] font-black text-red-400 uppercase">Diskon</label>
                                            <input type="number" wire:model.lazy="cart.{{ $id }}.potongan" wire:change="updatePotongan({{ $id }})" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-lg py-2 px-3 text-xs font-black text-right text-red-500" />
                                        </div>
                                        <div class="space-y-1 text-right">
                                            <label class="text-[9px] font-black text-gray-400 uppercase">Subtotal</label>
                                            <div class="py-2 font-black text-sm text-primary-600">Rp{{ number_format($item['subtotal']) }}</div>
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
            <div id="checkout-section" class="w-full lg:w-[32%] order-1 lg:order-2 lg:sticky lg:top-8 lg:self-start">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-xl flex flex-col overflow-hidden">
                    <div class="p-6 lg:p-8 bg-gray-900 dark:bg-black text-white relative overflow-hidden shrink-0">
                        <div class="relative z-10">
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-primary-500 block mb-2">Grand Total</span>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-xl font-bold opacity-40">Rp</span>
                                <span class="text-4xl lg:text-5xl font-black tracking-tighter leading-none">{{ number_format($this->total) }}</span>
                            </div>
                        </div>
                        <div class="absolute -right-10 -bottom-10 opacity-5"><x-heroicon-o-banknotes class="w-40 h-40" /></div>
                    </div>

                    <div class="p-5 space-y-6">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Metode Pembayaran</label>
                            <div class="grid grid-cols-2 gap-1 p-1 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                                <button wire:click="$set('metode_pembayaran', 'TUNAI')" class="py-2 rounded-lg text-[10px] font-black uppercase transition-all {{ $metode_pembayaran === 'TUNAI' ? 'bg-white dark:bg-gray-700 shadow-sm text-primary-600' : 'text-gray-400' }}">Tunai</button>
                                <button wire:click="$set('metode_pembayaran', 'TRANSFER')" class="py-2 rounded-lg text-[10px] font-black uppercase transition-all {{ $metode_pembayaran === 'TRANSFER' ? 'bg-white dark:bg-gray-700 shadow-sm text-primary-600' : 'text-gray-400' }}">Transfer</button>
                            </div>
                            @if($metode_pembayaran === 'TRANSFER')
                                <div class="relative">
                                    <select wire:model.live="rekening_perusahaan_id" class="w-full appearance-none bg-gray-50 dark:bg-gray-800 border-none rounded-xl py-2 px-3 pr-8 text-xs font-bold focus:ring-2 focus:ring-primary-500/20 cursor-pointer">
                                        <option value="">Pilih Bank...</option>
                                        @foreach($rekeningPerusahaan as $rek)
                                            <option value="{{ $rek->id }}">{{ $rek->nama_bank }} - {{ $rek->no_rekening }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                        <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-3 bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                            <div class="flex justify-between items-center">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Nominal Bayar</label>
                                <div class="flex gap-1">
                                    <button wire:click="setBayar('pas')" class="px-2 py-0.5 bg-gray-50 dark:bg-gray-800 text-[9px] font-black rounded border border-gray-200 dark:border-gray-700 hover:bg-primary-600 hover:text-white uppercase transition-all">Pas</button>
                                    <button wire:click="setBayar(100000)" class="px-2 py-0.5 bg-gray-50 dark:bg-gray-800 text-[9px] font-black rounded border border-gray-200 dark:border-gray-700 hover:bg-primary-600 hover:text-white uppercase transition-all">100k</button>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 border-b-2 border-primary-500 pb-2">
                                <span class="text-2xl font-black text-primary-600">Rp</span>
                                <input type="number" wire:model.lazy="bayar" class="w-full bg-transparent border-none p-0 text-3xl lg:text-4xl font-black focus:ring-0 tracking-tighter" placeholder="0" />
                            </div>
                            <div class="pt-2 flex justify-between items-center">
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ $this->bayar < $this->total ? 'Kurang' : 'Kembali' }}</span>
                                <span class="text-xl lg:text-2xl font-black {{ $this->bayar < $this->total ? 'text-red-500' : 'text-green-500' }}">Rp{{ number_format(abs($this->bayar - $this->total)) }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pengiriman</label>
                            <div class="relative">
                                <select wire:model.live="metode_pengiriman" class="w-full appearance-none bg-gray-50 dark:bg-gray-800 border-none rounded-xl py-2 px-3 pr-8 text-xs font-bold focus:ring-2 focus:ring-primary-500/20 cursor-pointer">
                                    <option value="DIBAWA_SENDIRI">Dibawa Sendiri</option>
                                    <option value="DIKIRIM">Dikirim Kurir</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                    <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Catatan</label>
                            <textarea wire:model.live="keterangan_nota" rows="2" placeholder="Catatan nota..." class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl py-2 px-3 text-xs focus:ring-2 focus:ring-primary-500/20"></textarea>
                        </div>
                    </div>

                    <div class="p-4 lg:p-5 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex gap-2">
                            <button 
                                wire:click="simpanPenjualan" 
                                class="flex-grow py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-black text-sm border border-white/30 shadow-sm active:scale-[0.97] transition-all"
                                @keydown.window.f8.prevent="$wire.simpanPenjualan()"
                            >
                                Simpan
                            </button>
                            <button wire:click="resetPos" class="py-3 px-5 text-[10px] font-black text-gray-400 hover:text-red-500 uppercase tracking-widest transition-colors border border-gray-200 dark:border-gray-700 rounded-xl hover:border-red-200">
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
        /* Fix Filament parent overflow that breaks sticky */
        .fi-main, .fi-main-ctn { overflow: visible !important; }
        .fi-page { overflow: visible !important; }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input:focus, select:focus, textarea:focus { outline: none; }
        .overflow-y-auto::-webkit-scrollbar { width: 3px; }
        .overflow-y-auto::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        @media (max-width: 1024px) { .pos-pro-dashboard { height: auto; overflow: visible; } }
    </style>

    <script>
        document.addEventListener('livewire:initialized', () => {
            const cart = localStorage.getItem('pos_cart');
            if (cart) { @this.dispatch('restoreCart', { cart: JSON.parse(cart) }); }
            @this.on('cartUpdated', () => { localStorage.setItem('pos_cart', JSON.stringify(@this.cart)); });
        });
    </script>
</x-filament::page>
