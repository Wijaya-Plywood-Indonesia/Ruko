<x-filament::page>
    {{ $this->form }}

    @if ($suratJalan)
        <x-filament::section heading="Informasi Surat Jalan">
            <div class="grid grid-cols-2 gap-4">
                <div><b>No Surat:</b> {{ $suratJalan->no_surat_jalan }}</div>
                <div><b>Tanggal:</b> {{ $suratJalan->tanggal_kirim->format('d-m-Y') }}</div>
                <div><b>Toko Asal:</b> {{ $suratJalan->tokoAsal->nama_toko }}</div>
                <div><b>Toko Tujuan:</b> {{ $suratJalan->tokoTujuan->nama_toko }}</div>
                <div><b>Supir:</b> {{ $suratJalan->nama_supir }}</div>
                <div><b>Plat:</b> {{ $suratJalan->plat }}</div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Detail Barang">
            <table class="w-full border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border">Barang</th>
                        <th class="p-2 border">Qty Kirim</th>
                        <th class="p-2 border">Qty Diterima</th>
                        <th class="p-2 border">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($suratJalan->details as $index => $detail)
                        <tr>
                            <td class="p-2 border">
                                {{ $detail->barang->nama_barang }}
                            </td>
                            <td class="p-2 border text-center">
                                {{ $detail->qty_kirim }}
                            </td>
                            <td class="p-2 border">
                                <input type="number"
                                       min="0"
                                       class="w-full border rounded p-1"
                                       wire:model.defer="suratJalan.details.{{ $index }}.qty_diterima">
                            </td>
                            <td class="p-2 border">
                                <input type="text"
                                       class="w-full border rounded p-1"
                                       wire:model.defer="suratJalan.details.{{ $index }}.catatan">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::button
            color="success"
            class="mt-4"
            wire:click="submit">
            Selesaikan Penerimaan
        </x-filament::button>
    @endif
</x-filament::page>
