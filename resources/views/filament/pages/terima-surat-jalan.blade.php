<x-filament::page>
    {{ $this->form }}

    @if ($suratJalan)

    {{-- =========================
    INFORMASI SURAT JALAN (A -> B)
    ========================= --}}
    <x-filament::section>
        <div class="sj-flow">

            <div class="sj-flow-line">
                <div class="sj-point">
                    <span class="sj-point-title">Toko Asal</span>
                    <span class="sj-point-name">
                        {{ $suratJalan->tokoAsal->nama_toko }}
                    </span>
                </div>

                <div class="sj-arrow">
                    ➜
                </div>

                <div class="sj-point">
                    <span class="sj-point-title">Toko Tujuan</span>
                    <span class="sj-point-name">
                        {{ $suratJalan->tokoTujuan->nama_toko }}
                    </span>
                </div>
            </div>

            <div class="sj-meta">
                <div class="sj-badge">
                    <span>No Surat Jalan</span>
                    <strong>{{ $suratJalan->no_surat_jalan }}</strong>
                </div>
                <div class="sj-badge">
                    <span>Tanggal</span>
                    <strong>{{ $suratJalan->tanggal_kirim->format('d-m-Y') }}</strong>
                </div>
                <div class="sj-badge">
                    <span>Supir</span>
                    <strong>{{ $suratJalan->nama_supir }}</strong>
                </div>
                <div class="sj-badge">
                    <span>Plat</span>
                    <strong>{{ $suratJalan->plat }}</strong>
                </div>
            </div>

        </div>
    </x-filament::section>

    {{-- =========================
    DETAIL BARANG
    ========================= --}}
    <x-filament::section heading="Detail Barang">
        <div class="overflow-x-auto">
            <table class="sj-table">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th class="text-center w-28">Qty Kirim</th>
                        <th class="text-center w-32">Qty Diterima</th>
                        <th class="text-center w-20">✔</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($details as $index => $detail)
                    <tr class="{{ $detail['locked'] ? 'opacity-60 bg-gray-50' : '' }}">
                        {{-- BARANG --}}
                        <td class="text-center align-middle">
                            {{ $detail['barang'] }}
                        </td>

                        {{-- QTY KIRIM --}}
                        <td class="text-center align-middle font-semibold" align="center">
                            {{ number_format($detail['qty_kirim'], 2, '.', '') }}
                        </td>

                        {{-- QTY DITERIMA --}}
                        <td class="text-center align-middle">
                           <input
    type="text"
    inputmode="decimal"
    pattern="[0-9]+([.,][0-9]{1,2})?"
    class="sj-input sj-input-number text-center"
    wire:key="qty-{{ $detail['id'] }}"
    wire:model.lazy="details.{{ $index }}.qty_diterima"
    @disabled($detail['locked'])
    onclick="this.select()"
>
                        </td>

                        {{-- CHECKBOX LOCK --}}
                        <td class="text-center align-middle">
                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300"
                                wire:model="details.{{ $index }}.locked">
                        </td>

                        {{-- CATATAN --}}
                        <td class="text-center align-middle">
                            <input type="text" class="sj-input text-center"
                                wire:model.defer="details.{{ $index }}.catatan" @disabled($detail['locked'])>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- =========================
    ACTION
    ========================= --}}
    <div class="mt-4 flex justify-end">
        <x-filament::button color="success" wire:click="submit">
            Selesaikan Penerimaan
        </x-filament::button>
    </div>

    @endif

    {{-- =========================
    CSS
    ========================= --}}
    <style>
        .sj-flow {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .sj-flow-line {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .sj-point {
            flex: 1;
            border: 2px solid #d1d5db;
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            transition: .25s;
        }

        .sj-point:hover {
            border-color: rgb(59 130 246);
            box-shadow: 0 6px 16px rgba(59, 130, 246, .15);
            transform: translateY(-2px);
        }

        .sj-point-title {
            font-size: .75rem;
            color: #6b7280;
        }

        .sj-point-name {
            font-weight: 600;
        }

        .sj-arrow {
            font-size: 1.5rem;
            color: rgb(59 130 246);
            animation: arrowMove 1.5s infinite;
        }

        @keyframes arrowMove {
            0% {
                transform: translateX(0);
                opacity: .6;
            }

            50% {
                transform: translateX(6px);
                opacity: 1;
            }

            100% {
                transform: translateX(0);
                opacity: .6;
            }
        }

        .sj-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
        }

        .sj-badge {
            background: #f9fafb;
            border: 1px dashed #d1d5db;
            border-radius: 10px;
            padding: 10px;
            font-size: .8rem;
        }

        .sj-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #9ca3af;
        }

        .sj-table th,
        .sj-table td {
            border: 2px solid #9ca3af;
            padding: 8px;
            font-size: .875rem;
        }

        .sj-table thead {
            background: #e5e7eb;
        }

        .sj-table tbody tr:hover {
            background: #f9fafb;
        }

        .sj-input {
            width: 100%;
            height: 34px;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
        }

        .sj-input:focus {
            outline: none;
            border-color: rgb(59 130 246);
            box-shadow: 0 0 0 1px rgb(59 130 246 / 40%);
        }

        .sj-input-number {
            text-align: center;
        }
    </style>

</x-filament::page>