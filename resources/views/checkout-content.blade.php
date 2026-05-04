<div class="p-5 space-y-6 overflow-y-auto">
    {{-- Metode Pembayaran --}}
    <div class="space-y-3">
        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">
            Metode Pembayaran
        </label>
        <div class="grid grid-cols-2 gap-1 p-1 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
            <button wire:click="$set('metode_pembayaran', 'TUNAI')" 
                class="py-2 rounded-lg text-[10px] font-black uppercase transition-all {{ $metode_pembayaran === 'TUNAI' ? 'bg-white dark:bg-gray-700 shadow-sm text-primary-600' : 'text-gray-400' }}">
                Tunai
            </button>
            <button wire:click="$set('metode_pembayaran', 'TRANSFER')" 
                class="py-2 rounded-lg text-[10px] font-black uppercase transition-all {{ $metode_pembayaran === 'TRANSFER' ? 'bg-white dark:bg-gray-700 shadow-sm text-primary-600' : 'text-gray-400' }}">
                Transfer
            </button>
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

    {{-- Nominal Bayar --}}
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
            <input type="number" wire:model.lazy="bayar" class="w-full bg-transparent border-none p-0 text-3xl font-black focus:ring-0 tracking-tighter" placeholder="0" />
        </div>
        <div class="pt-2 flex justify-between items-center">
            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ $this->bayar < $this->total ? 'Kurang' : 'Kembali' }}</span>
            <span class="text-xl font-black {{ $this->bayar < $this->total ? 'text-red-500' : 'text-green-500' }}">Rp{{ number_format(abs($this->bayar - $this->total)) }}</span>
        </div>
    </div>

    {{-- Pengiriman --}}
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

    {{-- Catatan --}}
    <div class="flex flex-col gap-2">
        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Catatan</label>
        <textarea wire:model.live="keterangan_nota" rows="2" placeholder="Catatan nota..." class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl py-2 px-3 text-xs focus:ring-2 focus:ring-primary-500/20"></textarea>
    </div>
</div>

{{-- Footer --}}
<div class="p-4 border-t border-gray-100 dark:border-gray-800 shrink-0">
    <div class="flex gap-2">
        <button wire:click="simpanPenjualan" class="flex-grow py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-black text-sm border border-white/30 shadow-sm active:scale-[0.97] transition-all" @keydown.window.f8.prevent="$wire.simpanPenjualan()">
            Simpan
        </button>
        <button wire:click="resetPos" class="py-3 px-5 text-[10px] font-black text-gray-400 hover:text-red-500 uppercase tracking-widest transition-colors border border-gray-200 dark:border-gray-700 rounded-xl hover:border-red-200">
            Reset
        </button>
    </div>
</div>
