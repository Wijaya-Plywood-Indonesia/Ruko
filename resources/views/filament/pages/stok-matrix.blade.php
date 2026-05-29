<x-filament::page>
    {{-- Main Container with Alpine.js --}}
    <div x-data="{
        search: '',
        filterStatus: 'all',
        items: @js($barangs->map(fn($barang) => [
            'id' => $barang->id,
            'nama' => $barang->nama_barang,
            'satuan' => $barang->satuan?->nama_satuan ?? 'pcs',
            'akun' => $barang->subAnakAkun?->kode_sub_anak_akun ?? '',
            'qty' => $stok[$barang->id]->stok ?? 0.0
        ])),
        get filteredItems() {
            return this.items.filter(item => {
                const matchesSearch = item.nama.toLowerCase().includes(this.search.toLowerCase()) || 
                                      item.akun.toLowerCase().includes(this.search.toLowerCase());
                
                const matchesStatus = this.filterStatus === 'all' || 
                                      (this.filterStatus === 'available' && item.qty > 0) || 
                                      (this.filterStatus === 'empty' && item.qty <= 0);
                                      
                return matchesSearch && matchesStatus;
            });
        },
        formatQty(val) {
            return Number(val).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }" class="space-y-4">

        {{-- Minimal Search & Filter Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-3 shadow-sm">
            
            {{-- Live Search Input --}}
            <div class="relative flex-grow max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    type="text" 
                    x-model="search" 
                    placeholder="Cari nama barang atau no akun..." 
                    class="w-full pl-9 pr-8 py-2 bg-gray-50/60 focus:bg-white dark:bg-gray-950/40 dark:focus:bg-gray-950 border border-gray-200 dark:border-gray-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 rounded-xl text-xs transition-all outline-none text-gray-700 dark:text-gray-300"
                />
                <button 
                    x-show="search.length > 0" 
                    @click="search = ''" 
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Dynamic Tabs for Status Filtering --}}
            <div class="flex bg-gray-100/80 dark:bg-gray-950/60 p-1 rounded-xl border border-gray-200/50 dark:border-gray-800/80 self-start sm:self-auto">
                <button 
                    @click="filterStatus = 'all'"
                    :class="filterStatus === 'all' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                    class="px-3 py-1 rounded-lg text-[11px] font-bold transition-all flex items-center gap-1.5"
                >
                    Semua
                    <span class="bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-[9px] font-mono text-gray-600 dark:text-gray-300" x-text="items.length"></span>
                </button>
                <button 
                    @click="filterStatus = 'available'"
                    :class="filterStatus === 'available' ? 'bg-white dark:bg-gray-900 shadow-sm text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                    class="px-3 py-1 rounded-lg text-[11px] font-bold transition-all flex items-center gap-1.5"
                >
                    Tersedia
                    <span class="bg-emerald-50 dark:bg-emerald-950/30 px-1.5 py-0.5 rounded text-[9px] font-mono text-emerald-600 dark:text-emerald-400" x-text="items.filter(i => i.qty > 0).length"></span>
                </button>
                <button 
                    @click="filterStatus = 'empty'"
                    :class="filterStatus === 'empty' ? 'bg-white dark:bg-gray-900 shadow-sm text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                    class="px-3 py-1 rounded-lg text-[11px] font-bold transition-all flex items-center gap-1.5"
                >
                    Habis
                    <span class="bg-rose-50 dark:bg-rose-950/30 px-1.5 py-0.5 rounded text-[9px] font-mono text-rose-600 dark:text-rose-400" x-text="items.filter(i => i.qty <= 0).length"></span>
                </button>
            </div>

        </div>

        {{-- High Density Responsive Grid Table with Borders (Desktop: 5, Tablet: 4, Handphone: 3) --}}
        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-0 border-t border-l border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden shadow-sm">
            <template x-for="item in filteredItems" :key="item.id">
                <div class="bg-white dark:bg-gray-900 border-r border-b border-gray-200 dark:border-gray-800 p-2.5 flex items-center justify-between gap-3 text-xs hover:bg-gray-50/50 dark:hover:bg-gray-950/30 transition-colors duration-150">
                    
                    {{-- Product Name and Account Code --}}
                    <div class="min-w-0 pr-2">
                        <span class="font-bold text-gray-800 dark:text-gray-200 block truncate text-xs" x-text="item.nama" :title="item.nama"></span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 font-mono" x-text="item.akun"></span>
                    </div>
                    
                    {{-- Quantities and Units --}}
                    <div class="text-right flex-shrink-0">
                        <span class="font-mono font-bold text-xs" :class="item.qty > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" x-text="formatQty(item.qty)"></span>
                        <span class="text-[9px] font-bold text-gray-400 dark:text-gray-500 block uppercase tracking-wider" x-text="item.satuan"></span>
                    </div>

                </div>
            </template>
            
            {{-- Empty Results State --}}
            <div x-show="filteredItems.length === 0" class="col-span-full py-8 text-center bg-white dark:bg-gray-900 border-r border-b border-gray-200 dark:border-gray-800">
                <svg class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.008 1.24l.885 1.77a2.25 2.25 0 002.007 1.24h1.98a2.25 2.25 0 002.007-1.24l.885-1.77a2.25 2.25 0 012.007-1.24h3.86m-18 0h18" />
                </svg>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Tidak ada barang yang cocok dengan kriteria pencarian.</p>
            </div>
        </div>

    </div>
</x-filament::page>