<x-filament::page>

    <form wire:submit.prevent="loadData" class="mb-4 flex gap-4">
        <x-filament::input type="date" wire:model="from" />
        <x-filament::input type="date" wire:model="to" />

        <x-filament::select wire:model="type">
            <option value="main">Penjualan</option>
            <option value="detail">Detail Penjualan</option>
            <option value="full">Full Penjualan</option>
        </x-filament::select>

        <x-filament::button type="submit">
            Preview
        </x-filament::button>
    </form>

    <div class="overflow-auto border">
        <table class="w-full text-sm border-collapse">
            <thead class="bg-blue-500 text-white">
                <tr>
                    @foreach(array_keys($data[0] ?? []) as $col)
                        <th class="border px-2 py-1 text-left">
                            {{ strtoupper(str_replace('_', ' ', $col)) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                    <tr>
                        @foreach($row as $value)
                            <td class="border px-2 py-1">
                                {{ is_array($value) ? '...' : $value }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</x-filament::page>
