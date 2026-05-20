@php
    /*
    |---------------------------------------------------------------
    | Palet warna subtotal — muted/soft, tidak mencolok
    |
    | Prinsip:
    |   - Baris: background sangat tipis, hampir tidak kelihatan
    |   - Text: warna kalem, sedikit lebih gelap dari body text
    |   - Border: garis halus, bukan warna cerah
    |   - Angka positif: slate/stone, bukan hijau terang
    |   - Angka negatif: rose muted, bukan merah terang
    |---------------------------------------------------------------
    */
    $styleMap = match($style) {
        // Pendapatan Bruto — hint hijau sangat pudar
        'pendapatan_bruto'   => [
            'row'    => 'bg-teal-50/60 dark:bg-teal-950/20',
            'text'   => 'text-teal-700 dark:text-teal-500',
            'border' => 'border-teal-200 dark:border-teal-900',
            'num'    => 'text-teal-700 dark:text-teal-400',
        ],
        // Penjualan Bersih — slate netral
        'penjualan_bersih'   => [
            'row'    => 'bg-slate-100/80 dark:bg-slate-800/40',
            'text'   => 'text-slate-600 dark:text-slate-300',
            'border' => 'border-slate-300 dark:border-slate-700',
            'num'    => 'text-slate-700 dark:text-slate-200',
        ],
        // Total HPP — stone/warm gray
        'total_hpp'          => [
            'row'    => 'bg-stone-50 dark:bg-stone-900/20',
            'text'   => 'text-stone-500 dark:text-stone-400',
            'border' => 'border-stone-200 dark:border-stone-700',
            'num'    => 'text-stone-600 dark:text-stone-300',
        ],
        // Laba Kotor — biru sangat muted
        'laba_kotor'         => [
            'row'    => 'bg-blue-50/50 dark:bg-blue-950/20',
            'text'   => 'text-blue-600 dark:text-blue-400',
            'border' => 'border-blue-200 dark:border-blue-900',
            'num'    => 'text-blue-700 dark:text-blue-300',
        ],
        // Total Beban — rose sangat tipis
        'total_beban'        => [
            'row'    => 'bg-rose-50/40 dark:bg-rose-950/10',
            'text'   => 'text-rose-500 dark:text-rose-400',
            'border' => 'border-rose-200 dark:border-rose-900',
            'num'    => 'text-rose-600 dark:text-rose-400',
        ],
        // Laba Usaha — indigo muted
        'laba_usaha'         => [
            'row'    => 'bg-indigo-50/50 dark:bg-indigo-950/20',
            'text'   => 'text-indigo-600 dark:text-indigo-400',
            'border' => 'border-indigo-200 dark:border-indigo-900',
            'num'    => 'text-indigo-700 dark:text-indigo-300',
        ],
        // Laba Sebelum Pajak — paling menonjol tapi tetap kalem: slate gelap
        'laba_sebelum_pajak' => [
            'row'    => 'bg-gray-100 dark:bg-gray-800/60',
            'text'   => 'text-gray-700 dark:text-gray-200',
            'border' => 'border-gray-300 dark:border-gray-600',
            'num'    => 'text-gray-800 dark:text-gray-100',
        ],
        default              => [
            'row'    => 'bg-gray-50 dark:bg-gray-800/30',
            'text'   => 'text-gray-600 dark:text-gray-300',
            'border' => 'border-gray-200 dark:border-gray-700',
            'num'    => 'text-gray-700 dark:text-gray-200',
        ],
    };

    if (!isset($pKey) || !is_callable($pKey)) {
        $pKey = fn(array $p): string => $p['tahun'] . '-' . str_pad($p['bulan'], 2, '0', STR_PAD_LEFT);
    }
@endphp

<tr class="{{ $styleMap['row'] }} border-t border-b {{ $styleMap['border'] }}">
    <td class="py-3"></td>
    <td class="px-4 py-3">
        <p class="text-xs font-semibold {{ $styleMap['text'] }} tracking-wide">
            {{ $label }}
        </p>
        @if(!empty($rumus))
            <p class="text-[9px] text-gray-400 dark:text-gray-600 mt-0.5">{{ $rumus }}</p>
        @endif
    </td>
    @foreach($buls as $periode)
        @php
            $k   = $pKey($periode);
            $val = $r[$k][$key] ?? 0;
            $isNeg = $val < 0;
            $numClass = $isNeg ? 'text-rose-500 dark:text-rose-400' : $styleMap['num'];
        @endphp
        {{-- Kolom rincian: kosong --}}
        <td class="border-r border-gray-200 dark:border-gray-700"></td>
        {{-- Kolom jumlah: angka dengan garis bawah ganda --}}
        <td class="px-4 py-3 text-right border-l border-gray-200 dark:border-gray-700">
            <span class="text-sm font-semibold {{ $numClass }}
                         border-t border-b-2 border-double {{ $styleMap['border'] }} px-1 inline-block tabular-nums">
                @if($isNeg)({{ $this->formatRupiah($val) }})@else{{ $this->formatRupiah($val) }}@endif
            </span>
        </td>
    @endforeach
</tr>