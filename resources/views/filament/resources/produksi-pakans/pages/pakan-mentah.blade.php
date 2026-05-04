<x-filament-panels::page>

    {{-- ===== HEADER META (Tanggal & Keterangan) ===== --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 p-4 mb-4">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                    Tanggal Produksi
                </label>
                <p class="mt-1 text-sm font-semibold text-gray-800">
                    {{ $record->tanggal_produksi->translatedFormat('d F Y') }}
                </p>
            </div>
            @if($record->keterangan)
            <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                    Keterangan
                </label>
                <p class="mt-1 text-sm text-gray-700">{{ $record->keterangan }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ===== TABEL STOK PAKAN MENTAH ===== --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 overflow-hidden mb-4">

        {{-- Section Header --}}
        <div class="bg-indigo-600 px-4 py-2.5 flex items-center gap-2">
            <x-heroicon-o-table-cells class="w-4 h-4 text-white" />
            <h3 class="text-sm font-medium text-white">Stok Pakan Mentah</h3>
        </div>

        {{-- Legend --}}
        <div class="flex gap-4 px-4 pt-3 pb-1 flex-wrap">
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <div class="w-3 h-3 rounded bg-yellow-50 border border-yellow-200"></div>
                Kolom yang dapat diisi
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <div class="w-3 h-3 rounded bg-blue-50 border border-blue-200"></div>
                Kolom keluar
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <div class="w-3 h-3 rounded bg-green-50 border border-green-200"></div>
                Stok akhir (otomatis)
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse" id="tbl-mentah">
                <thead>
                    <tr>
                        <th rowspan="2" class="text-left px-3 py-2 text-xs font-medium text-gray-500 bg-gray-50 border-b border-gray-200 min-w-[120px]">
                            Nama Barang
                        </th>
                        <th rowspan="2" class="text-center px-3 py-2 text-xs font-medium text-gray-500 bg-gray-50 border-b border-gray-200">
                            Satuan
                        </th>
                        <th rowspan="2" class="text-center px-3 py-2 text-xs font-medium text-gray-500 bg-gray-50 border-b border-gray-200">
                            Awal
                        </th>
                        <th rowspan="2" class="text-center px-3 py-2 text-xs font-medium text-gray-500 bg-gray-50 border-b border-gray-200">
                            Masuk
                        </th>
                        <th colspan="3" class="text-center px-3 py-2 text-xs font-medium text-blue-700 bg-blue-50 border-b border-gray-200">
                            Keluar
                        </th>
                        <th rowspan="2" class="text-center px-3 py-2 text-xs font-medium text-gray-500 bg-gray-50 border-b border-gray-200">
                            Akhir
                        </th>
                    </tr>
                    <tr>
                        <th class="text-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 border-b border-gray-200">Pullet</th>
                        <th class="text-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 border-b border-gray-200">L1</th>
                        <th class="text-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 border-b border-gray-200">L2</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mentahItems as $item)
                    <tr class="hover:bg-gray-50 border-b border-gray-100"
                        data-awal="{{ $item->stok_awal ?? 0 }}">
                        <td class="px-3 py-2 font-medium text-gray-800 whitespace-nowrap">
                            {{ $item->barang->nama_barang }}
                        </td>
                        <td class="px-3 py-2 text-center text-gray-400 text-xs">
                            {{ $item->barang->satuan }}
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-700">
                            {{ number_format($item->stok_awal, 0, ',', '.') }}
                        </td>

                        {{-- Kolom Masuk --}}
                        <td class="bg-yellow-50 p-0">
                            <input
                                type="number" min="0"
                                name="mentah[{{ $item->id }}][masuk]"
                                value="{{ old('mentah.'.$item->id.'.masuk', $item->masuk) }}"
                                placeholder="0"
                                class="w-full h-full px-2 py-2 text-right text-sm bg-transparent border-0 focus:ring-1 focus:ring-indigo-300 focus:outline-none"
                                oninput="hitungAkhir(this)">
                        </td>

                        {{-- Kolom Keluar Pullet --}}
                        <td class="bg-blue-50 p-0">
                            <input
                                type="number" min="0"
                                name="mentah[{{ $item->id }}][keluar_pullet]"
                                value="{{ old('mentah.'.$item->id.'.keluar_pullet', $item->keluar_pullet) }}"
                                placeholder="0"
                                class="w-full h-full px-2 py-2 text-right text-sm bg-transparent border-0 focus:ring-1 focus:ring-blue-200 focus:outline-none text-blue-800"
                                oninput="hitungAkhir(this)">
                        </td>

                        {{-- Kolom Keluar L1 --}}
                        <td class="bg-blue-50 p-0">
                            <input
                                type="number" min="0"
                                name="mentah[{{ $item->id }}][keluar_l1]"
                                value="{{ old('mentah.'.$item->id.'.keluar_l1', $item->keluar_l1) }}"
                                placeholder="0"
                                class="w-full h-full px-2 py-2 text-right text-sm bg-transparent border-0 focus:ring-1 focus:ring-blue-200 focus:outline-none text-blue-800"
                                oninput="hitungAkhir(this)">
                        </td>

                        {{-- Kolom Keluar L2 --}}
                        <td class="bg-blue-50 p-0">
                            <input
                                type="number" min="0"
                                name="mentah[{{ $item->id }}][keluar_l2]"
                                value="{{ old('mentah.'.$item->id.'.keluar_l2', $item->keluar_l2) }}"
                                placeholder="0"
                                class="w-full h-full px-2 py-2 text-right text-sm bg-transparent border-0 focus:ring-1 focus:ring-blue-200 focus:outline-none text-blue-800"
                                oninput="hitungAkhir(this)">
                        </td>

                        {{-- Stok Akhir (otomatis dihitung JS) --}}
                        <td class="px-3 py-2 text-right font-semibold text-green-700 bg-green-50 akhir-cell">
                            {{ number_format($item->stok_akhir, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 font-medium text-xs text-gray-600 border-t-2 border-gray-200">
                        <td colspan="2" class="px-3 py-2">Total</td>
                        <td class="px-3 py-2 text-right" id="tot-awal">—</td>
                        <td class="px-3 py-2 text-right" id="tot-masuk">—</td>
                        <td class="px-3 py-2 text-right" id="tot-pullet">—</td>
                        <td class="px-3 py-2 text-right" id="tot-l1">—</td>
                        <td class="px-3 py-2 text-right" id="tot-l2">—</td>
                        <td class="px-3 py-2 text-right text-green-700 font-bold" id="tot-akhir">—</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ===== TOMBOL SIMPAN ===== --}}
    <div class="flex justify-end gap-3">
        <a href="{{ ProduksiPakanResource::getUrl('index') }}"
            class="fi-btn fi-color-gray fi-btn-outlined">
            Kembali
        </a>
        <button
            wire:click="simpan"
            class="fi-btn fi-btn-color-primary fi-btn-size-md fi-btn-filled">
            Simpan
        </button>
    </div>

</x-filament-panels::page>

@push('scripts')
<script>
    function hitungAkhir(el) {
        const tr = el.closest('tr');
        const awal = parseFloat(tr.dataset.awal) || 0;
        const inputs = tr.querySelectorAll('input[type="number"]');
        const masuk = parseFloat(inputs[0].value) || 0;
        const pullet = parseFloat(inputs[1].value) || 0;
        const l1 = parseFloat(inputs[2].value) || 0;
        const l2 = parseFloat(inputs[3].value) || 0;

        const akhir = awal + masuk - pullet - l1 - l2;

        // Tampilkan di kolom akhir
        tr.querySelector('.akhir-cell').textContent =
            akhir.toLocaleString('id-ID');

        // Update hidden input agar ikut terkirim ke server
        let hiddenAkhir = tr.querySelector('input[name*="stok_akhir"]');
        if (hiddenAkhir) hiddenAkhir.value = akhir;

        hitungTotal();
    }

    function hitungTotal() {
        const rows = document.querySelectorAll('#tbl-mentah tbody tr');
        let totAwal = 0,
            totMasuk = 0,
            totP = 0,
            totL1 = 0,
            totL2 = 0,
            totAkhir = 0;

        rows.forEach(tr => {
            totAwal += parseFloat(tr.dataset.awal) || 0;
            const inputs = tr.querySelectorAll('input[type="number"]');
            totMasuk += parseFloat(inputs[0]?.value) || 0;
            totP += parseFloat(inputs[1]?.value) || 0;
            totL1 += parseFloat(inputs[2]?.value) || 0;
            totL2 += parseFloat(inputs[3]?.value) || 0;
            const akhirText = tr.querySelector('.akhir-cell')?.textContent
                .replace(/\./g, '').replace(',', '.');
            totAkhir += parseFloat(akhirText) || 0;
        });

        document.getElementById('tot-awal').textContent = totAwal.toLocaleString('id-ID');
        document.getElementById('tot-masuk').textContent = totMasuk.toLocaleString('id-ID');
        document.getElementById('tot-pullet').textContent = totP.toLocaleString('id-ID');
        document.getElementById('tot-l1').textContent = totL1.toLocaleString('id-ID');
        document.getElementById('tot-l2').textContent = totL2.toLocaleString('id-ID');
        document.getElementById('tot-akhir').textContent = totAkhir.toLocaleString('id-ID');
    }

    document.addEventListener('DOMContentLoaded', hitungTotal);
</script>
@endpush