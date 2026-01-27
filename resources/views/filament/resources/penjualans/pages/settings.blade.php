    {{-- Page content --}}
    <div class="p-8">
    <h1 class="text-3xl font-bold uppercase">INI CUSTOM PAGE</h1>
    <hr class="my-4">

    <table class="w-full border-collapse border border-gray-300 mt-5">
        <thead>
            <tr class="bg-gray-100">
                <th class="border p-2">ID</th>
                <th class="border p-2">Nama Penjualan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allPenjualan as $item)
                <tr>
                    <td class="border p-2">{{ $item->id }}</td>
                    {{-- <td class="border p-2">{{ $item->nama }}</td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
