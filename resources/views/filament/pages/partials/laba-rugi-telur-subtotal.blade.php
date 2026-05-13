@php
    $styleMap = match($style) {
        'pendapatan_bruto'  => ['row' => 'bg-emerald-50/40 dark:bg-emerald-900/20',  'text' => 'text-emerald-700 dark:text-emerald-400'],
        'penjualan_bersih'  => ['row' => 'bg-slate-100/50 dark:bg-slate-800/40',     'text' => 'text-slate-800 dark:text-slate-200'],
        'total_hpp'         => ['row' => 'bg-orange-50/40 dark:bg-orange-900/20',    'text' => 'text-orange-700 dark:text-orange-400'],
        'laba_kotor'        => ['row' => 'bg-blue-50/60 dark:bg-blue-900/20',        'text' => 'text-blue-700 dark:text-blue-400'],
        'total_beban'       => ['row' => 'bg-red-50/30 dark:bg-red-900/10',          'text' => 'text-red-600 dark:text-red-400'],
        'laba_usaha'        => ['row' => 'bg-indigo-50 dark:bg-indigo-900/30',       'text' => 'text-indigo-800 dark:text-indigo-400'],
        'laba_sebelum_pajak'=> ['row' => 'bg-violet-100 dark:bg-violet-900/30',      'text' => 'text-violet-900 dark:text-violet-300'],
        default             => ['row' => 'bg-gray-50 dark:bg-gray-800',              'text' => 'text-gray-900 dark:text-gray-100'],
    };
@endphp

<tr class="{{ $styleMap['row'] }} border-y-2 border-gray-200 dark:border-gray-700">
    <td class="py-4"></td>
    <td class="py-4 px-4">
        <p class="text-sm font-bold {{ $styleMap['text'] }} uppercase tracking-widest">
            {{ $label }}
        </p>
        @if(!empty($rumus))
            <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium italic mt-0.5">
                {{ $rumus }}
            </p>
        @endif
    </td>
    @foreach($buls as $bulan)
        @php $val = $r[$bulan][$key] ?? 0; @endphp
        <td class="px-4 py-4 text-right text-sm font-black
            {{ $val >= 0 ? $styleMap['text'] : 'text-red-600 dark:text-red-400' }}">
            @if($val < 0)({{ $this->formatRupiah($val) }})@else{{ $this->formatRupiah($val) }}@endif
        </td>
    @endforeach
</tr> 