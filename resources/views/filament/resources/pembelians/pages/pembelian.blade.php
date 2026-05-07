<x-filament-panels::page>
    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @endpush

    <div class="min-h-full pb-10 transition-colors duration-300">

        <form wire:submit.prevent="simpan" class="flex flex-col lg:flex-row gap-4 sm:gap-6 items-start">

            {{-- BAGIAN KIRI: Informasi Pembelian --}}
            <div class="w-full lg:w-[65%] xl:w-2/3 bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-800 p-4 sm:p-5 border-b border-gray-200 dark:border-gray-800">
                    <h2 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <x-heroicon-o-information-circle class="w-6 h-6 text-primary-500" />
                        Informasi Pembelian
                    </h2>
                </div>

                <div class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-x-4 sm:gap-x-6 gap-y-5 sm:gap-y-8">

                    {{-- Input Group: Nomor Nota --}}
                    <div class="col-span-1 flex flex-col gap-1.5 sm:gap-2">
                        <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-hashtag class="w-5 h-5 text-primary-500" /> Nomor Nota <span class="text-danger-500 font-bold">*</span>
                        </label>
                        <input type="text" wire:model="nomor_nota" required placeholder="INV-909921"
                            class="w-full p-3 sm:p-4 text-base sm:text-lg bg-white dark:bg-gray-950 border-2 border-gray-300 dark:border-gray-700  focus:border-primary-500 dark:text-white transition-all outline-none" />
                    </div>

                    {{-- Input Group: Dibuat Oleh (Disabled) --}}
                    <div class="col-span-1 flex flex-col gap-1.5 sm:gap-2">
                        <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-user class="w-5 h-5 text-primary-500" /> Dibuat Oleh
                        </label>
                        <div class="relative">
                            {{-- PERBAIKAN: Ubah wire:model menjadi created_by_name --}}
                            <input type="text" wire:model="created_by_name" disabled
                                class="w-full p-3 sm:p-4 pl-10 sm:pl-12 text-base sm:text-lg font-bold bg-gray-100 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 cursor-not-allowed opacity-90" />
                            <x-heroicon-o-lock-closed class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                        </div>
                    </div>

                    {{-- Input Group: Tanggal --}}
                    <div class="col-span-1 flex flex-col gap-1.5 sm:gap-2">
                        <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-calendar class="w-5 h-5 text-primary-500" /> Tanggal <span class="text-danger-500 font-bold">*</span>
                        </label>

                        {{-- Wrapper Alpine.js untuk inisialisasi Flatpickr --}}
                        <div wire:ignore
                            x-data="{
                                date: @entangle('tanggal'),
                                init() {
                                    flatpickr(this.$refs.picker, {
                                        locale: 'id', // Menggunakan Bahasa Indonesia
                                        altInput: true, // Membuat input bayangan yang mudah dibaca
                                        altFormat: 'd F Y', // Format Tampil: 07 Mei 2026
                                        dateFormat: 'Y-m-d', // Format yang dikirim ke PHP/DB: 2026-05-07
                                        defaultDate: this.date,
                                        onChange: (selectedDates, dateStr) => {
                                            this.date = dateStr; // Sinkronisasi ke Livewire
                                        }
                                    });
                                }
                             }">

                            {{-- Input Asli yang akan diubah bentuknya oleh Flatpickr --}}
                            <input
                                x-ref="picker"
                                type="text"
                                placeholder="Pilih Tanggal..."
                                class="w-full p-3 sm:p-4 text-base sm:text-lg bg-white dark:bg-gray-950 border-2 border-gray-300 dark:border-gray-700 focus:border-primary-500 dark:text-white transition-all outline-none cursor-pointer" />
                        </div>
                    </div>

                    {{-- Input Group: Cari / Pilih / Tambah Supplier --}}
                    <div class="col-span-1 flex flex-col gap-1.5 sm:gap-2 relative"
                        x-data="{
                            isOpen: false,
                            search: '',
                            isNew: @entangle('is_new_supplier').live,
                            suppliers: @js(\App\Models\Supplier::select('id', 'nama', 'telepon', 'alamat')->get()),
                            
                            get filteredSuppliers() {
                                if (this.search === '') return this.suppliers;
                                return this.suppliers.filter(s => s.nama.toLowerCase().includes(this.search.toLowerCase()));
                            },
                            
                            selectSupplier(sup) {
                                $wire.set('supplier_id', sup.id);
                                $wire.set('supplier_name', sup.nama);
                                $wire.set('supplier_phone', sup.telepon);
                                $wire.set('supplier_address', sup.alamat);
                                this.isNew = false;
                                this.search = sup.nama;
                                this.isOpen = false;
                            },
                            
                            createNewSupplier() {
                                $wire.set('supplier_id', null);
                                $wire.set('supplier_name', this.search); // Otomatis isi nama dari yang diketik
                                $wire.set('supplier_phone', '');
                                $wire.set('supplier_address', '');
                                this.isNew = true;
                                this.isOpen = false;
                            },
                    
                            clearSearch() {
                                this.search = '';
                                $wire.set('supplier_id', null);
                                $wire.set('supplier_name', '');
                                $wire.set('supplier_phone', '');
                                $wire.set('supplier_address', '');
                                this.isNew = false;
                                // this.isOpen = true; // Buka komen jika ingin list langsung muncul setelah di-clear
                            }
                        }"
                        @click.away="isOpen = false">

                        <label class="flex items-center justify-between gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                            <span class="flex items-center gap-1.5 sm:gap-2">
                                <x-heroicon-o-building-storefront class="w-5 h-5 text-primary-500" /> Pilih / Cari Supplier <span class="text-danger-500 font-bold">*</span>
                            </span>
                            {{-- Label penanda Mode Baru (Muncul jika is_new_supplier = true) --}}
                            <span x-show="isNew" x-cloak class="text-[10px] sm:text-xs bg-amber-100 text-amber-700 px-2 py-1 font-bold border border-amber-300 uppercase tracking-wider">
                                Input Manual
                            </span>
                        </label>

                        <div class="relative flex items-center">
                            <input type="text" x-model="search" @focus="isOpen = true" placeholder="Ketik nama supplier..."
                                class="w-full p-3 sm:p-4 text-base sm:text-lg bg-white dark:bg-gray-950 border-2 border-gray-300 dark:border-gray-700 focus:ring-4 focus:ring-primary-500/30 focus:border-primary-500 dark:text-white transition-all font-bold outline-none pr-10">

                            {{-- Tombol X untuk reset pencarian --}}
                            <button type="button" x-show="search.length > 0" @click="clearSearch()" x-cloak
                                class="absolute right-3 text-gray-400 hover:text-rose-500 p-1 transition-colors">
                                <x-heroicon-o-x-mark class="w-6 h-6" stroke-width="3" />
                            </button>
                        </div>

                        @error('supplier_id') <span class="text-danger-500 text-sm font-bold">{{ $message }}</span> @enderror

                        {{-- Dropdown Hasil Pencarian --}}
                        <div x-show="isOpen" x-transition x-cloak class="absolute top-[85px] sm:top-[95px] z-50 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl max-h-60 overflow-y-auto p-1.5 custom-scroll">

                            {{-- Looping Data Supplier --}}
                            <template x-for="sup in filteredSuppliers" :key="sup.id">
                                <button type="button" @click="selectSupplier(sup)" class="w-full text-left px-3 py-3 hover:bg-primary-50 dark:hover:bg-primary-900/40 rounded-lg flex flex-col group border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors">
                                    <span class="font-bold text-gray-800 dark:text-gray-200 text-base sm:text-lg group-hover:text-primary-700" x-text="sup.nama"></span>
                                    <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400" x-text="sup.telepon ? sup.telepon : '-'"></span>
                                </button>
                            </template>

                            {{-- Tombol Buat Supplier Baru (Berdasarkan ketikan) --}}
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

                    {{-- Input Group: Nama Supplier (Dinamis) --}}
                    <div class="col-span-1 flex flex-col gap-1.5 sm:gap-2">
                        <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-user class="w-5 h-5 text-primary-500" /> Nama Supplier
                        </label>
                        <div class="relative">
                            <input type="text" wire:model="supplier_name" placeholder="Otomatis terisi..."
                                x-bind:disabled="!$wire.is_new_supplier"
                                x-bind:class="$wire.is_new_supplier ? 'bg-white dark:bg-gray-950 focus:border-amber-500 focus:ring-amber-500/30' : 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-90'"
                                class="w-full p-3 sm:p-4 pl-10 sm:pl-12 text-base sm:text-lg font-semibold border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 transition-all outline-none" />

                            {{-- Ganti Ikon Gembok / Pensil secara dinamis --}}
                            <div x-show="!$wire.is_new_supplier"><x-heroicon-o-lock-closed class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" /></div>
                            <div x-show="$wire.is_new_supplier" x-cloak><x-heroicon-o-pencil-square class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-amber-500 w-5 h-5" /></div>
                        </div>
                        @error('supplier_name') <span class="text-danger-500 text-sm font-bold">{{ $message }}</span> @enderror
                    </div>

                    {{-- Input Group: Telepon Supplier (Dinamis) --}}
                    <div class="col-span-1 flex flex-col gap-1.5 sm:gap-2">
                        <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-phone class="w-5 h-5 text-primary-500" /> Telepon Supplier
                        </label>
                        <div class="relative">
                            <input type="text" wire:model="supplier_phone" placeholder="Otomatis terisi..."
                                x-bind:disabled="!$wire.is_new_supplier"
                                x-bind:class="$wire.is_new_supplier ? 'bg-white dark:bg-gray-950 focus:border-amber-500 focus:ring-amber-500/30' : 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-90'"
                                class="w-full p-3 sm:p-4 pl-10 sm:pl-12 text-base sm:text-lg font-semibold border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 transition-all outline-none" />

                            <div x-show="!$wire.is_new_supplier"><x-heroicon-o-lock-closed class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" /></div>
                            <div x-show="$wire.is_new_supplier" x-cloak><x-heroicon-o-pencil-square class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-amber-500 w-5 h-5" /></div>
                        </div>
                    </div>

                    {{-- Input Group: Alamat Supplier (Dinamis) --}}
                    <div class="col-span-1 md:col-span-2 flex flex-col gap-1.5 sm:gap-2">
                        <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-map-pin class="w-5 h-5 text-primary-500" /> Alamat Supplier
                        </label>
                        <div class="relative">
                            <textarea wire:model="supplier_address" rows="2" placeholder="Otomatis terisi..."
                                x-bind:disabled="!$wire.is_new_supplier"
                                x-bind:class="$wire.is_new_supplier ? 'bg-white dark:bg-gray-950 focus:border-amber-500 focus:ring-amber-500/30' : 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-90'"
                                class="w-full p-3 sm:p-4 pl-10 sm:pl-12 text-base sm:text-lg font-semibold border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 transition-all outline-none resize-none"></textarea>

                            <div x-show="!$wire.is_new_supplier"><x-heroicon-o-lock-closed class="absolute left-3 sm:left-4 top-4 sm:top-5 text-gray-400 w-5 h-5" /></div>
                            <div x-show="$wire.is_new_supplier" x-cloak><x-heroicon-o-pencil-square class="absolute left-3 sm:left-4 top-4 sm:top-5 text-amber-500 w-5 h-5" /></div>
                        </div>
                    </div>

                    {{-- Input Group: Status --}}
                    <div class="col-span-1 md:col-span-2 flex flex-col gap-1.5 sm:gap-2">
                        <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-primary-500" /> Status <span class="text-danger-500 font-bold">*</span>
                        </label>
                        <select wire:model="status" required
                            class="w-full p-3 sm:p-4 text-base sm:text-lg font-bold bg-amber-50 text-amber-900 dark:bg-amber-900/20 dark:text-amber-400 border-2 border-amber-300 dark:border-amber-700/50 transition-all cursor-pointer outline-none">

                            {{-- Looping dari Model Pembelian --}}
                            @foreach(\App\Models\Pembelian::labelStatus() as $value => $label)
                            <option value="{{ $value }}" class="bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100 font-semibold">{{ $label }}</option>
                            @endforeach

                        </select>
                    </div>

                    {{-- Input Group: Foto Nota --}}
                    <div class="col-span-1 md:col-span-2 flex flex-col gap-1.5 sm:gap-2">
                        <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-photo class="w-5 h-5 text-primary-500" /> Foto Nota
                        </label>

                        <label for="foto-upload" class="w-full p-6 sm:p-8 border-4 border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 hover:bg-gray-100 dark:bg-gray-950 dark:hover:bg-gray-800 transition-colors cursor-pointer flex flex-col items-center justify-center gap-2 sm:gap-3 text-center  overflow-hidden relative">

                            {{-- Jika BELUM ada foto, tampilkan ikon upload --}}
                            @if (!$foto_nota)
                            <div class="p-3 sm:p-4 bg-primary-100 dark:bg-primary-900/40 rounded-full">
                                <x-heroicon-o-arrow-up-tray class="w-8 h-8 sm:w-10 sm:h-10 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <span class="text-lg sm:text-xl font-bold text-primary-600 dark:text-primary-400 block sm:inline">Sentuh untuk Foto / Pilih File</span>
                            </div>
                            @endif

                            <input type="file" wire:model="foto_nota" multiple accept="image/*" class="hidden" id="foto-upload" />

                            {{-- PREVIEW GAMBAR: Jika ADA foto yang dipilih --}}
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

                            {{-- Loading indikator saat gambar sedang diproses Livewire --}}
                            <div wire:loading wire:target="foto_nota" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm flex flex-col items-center justify-center z-10">
                                <x-heroicon-o-arrow-path class="w-10 h-10 text-primary-500 animate-spin mb-2" />
                                <span class="font-bold text-primary-600 dark:text-primary-400">Memproses gambar...</span>
                            </div>
                        </label>
                    </div>

                    {{-- Input Group: Catatan --}}
                    <div class="col-span-1 md:col-span-2 flex flex-col gap-1.5 sm:gap-2">
                        <label class="flex items-center gap-1.5 sm:gap-2 text-sm sm:text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-document-text class="w-5 h-5 text-primary-500" /> Catatan Tambahan
                        </label>
                        <textarea wire:model="catatan" rows="3" placeholder="Tulis pesan atau catatan di sini jika ada..."
                            class="w-full p-3 sm:p-4 text-base sm:text-lg bg-white dark:bg-gray-950 border-2 border-gray-300 dark:border-gray-700  focus:border-primary-500 dark:text-white transition-all resize-none outline-none"></textarea>
                    </div>

                </div>
            </div>

            {{-- BAGIAN KANAN: Nominal --}}
            <div class="w-full lg:w-[35%] xl:w-1/3 flex flex-col gap-4 sm:gap-6 lg:sticky lg:top-24 pb-2">

                <div class="bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden shrink-0">
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 sm:p-5 border-b border-gray-200 dark:border-gray-800">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                            <x-heroicon-o-calculator class="w-6 h-6 text-success-500" />
                            Nominal / Uang
                        </h2>
                    </div>

                    <div class="p-4 sm:p-6 flex flex-col gap-4 sm:gap-5">

                        @php
                        $nominals = [
                        'sub_total' => 'Sub Total',
                        'total_diskon' => 'Total Diskon (Potongan)',
                        'total_ppn' => 'Total PPN (Pajak)',
                        'ongkir' => 'Ongkos Kirim',
                        'biaya_lain' => 'Biaya Lainnya',
                        ];
                        @endphp

                        @foreach($nominals as $field => $label)
                        {{-- Implementasi Alpine.js untuk Auto-Format Separator Ribuan --}}
                        <div class="flex flex-col gap-1 sm:gap-1.5"
                            x-data="{
                                raw: @entangle($field).live,
                                formatted: '',
                                formatNum(val) {
                                    if (val === '' || val === null || val === undefined) return '';
                                    return parseInt(val.toString().replace(/\D/g, '') || 0).toLocaleString('id-ID');
                                }
                             }"
                            x-init="
                                formatted = formatNum(raw);
                                $watch('raw', value => formatted = formatNum(value));
                             ">
                            <label class="text-sm sm:text-base font-semibold text-gray-700 dark:text-gray-300">{{ $label }}</label>
                            <div class="relative">
                                <span class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-base sm:text-lg font-bold text-gray-400">Rp</span>
                                <input type="text" inputmode="numeric" placeholder="0"
                                    x-model="formatted"
                                    x-on:input="
                                        let unformatted = $event.target.value.replace(/\D/g, '');
                                        raw = unformatted ? parseInt(unformatted) : null;
                                        formatted = formatNum(raw);
                                        $event.target.value = formatted;
                                    "
                                    class="w-full p-2.5 sm:p-3 pl-10 sm:pl-12 text-lg sm:text-xl font-bold text-right bg-white dark:bg-gray-950 border-2 border-gray-300 dark:border-gray-700 focus:border-success-500 dark:text-white transition-all outline-none" />
                            </div>
                        </div>
                        @endforeach

                        {{-- Grand Total --}}
                        <div class="mt-2 sm:mt-4 pt-4 sm:pt-6 border-t-4 border-gray-200 dark:border-gray-700">
                            <label class="text-base sm:text-lg font-bold text-gray-800 dark:text-white mb-2 block">TOTAL YANG HARUS DIBAYAR</label>
                            <div class="w-full p-3 sm:p-4 bg-success-50 dark:bg-success-900/30 border-2 border-success-400 dark:border-success-600  text-right">
                                <span class="text-xs sm:text-sm font-bold text-success-700 dark:text-success-400 block mb-0.5 sm:mb-1">Rp</span>
                                {{-- $this->grand_total akan memanggil fungsi getGrandTotalProperty() dari PHP secara otomatis --}}
                                <span class="text-2xl sm:text-3xl md:text-4xl font-black text-success-700 dark:text-success-400 tracking-tight break-all">
                                    {{ number_format($this->grand_total, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol Submit Besar --}}
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 sm:gap-3 bg-primary-600 hover:bg-primary-700 text-white font-bold text-lg sm:text-xl p-4 sm:p-6 shadow-lg hover:shadow-xl transition-all transform active:scale-95 border-b-4 border-primary-800 shrink-0 mt-2 sm:mt-0">
                    <x-heroicon-o-check class="w-6 h-6 sm:w-7 sm:h-7" stroke-width="3" />
                    SIMPAN PEMBELIAN
                </button>

            </div>
        </form>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    {{-- Load Bahasa Indonesia --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    @endpush
</x-filament-panels::page>