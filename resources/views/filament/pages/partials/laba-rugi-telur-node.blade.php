@php
    $isGroup     = $node['type'] === 'group';
    $isAnak      = $node['type'] === 'anak_akun';
    $isSub       = $node['type'] === 'sub_anak_akun';
    $hasChildren = !empty($node['children']);
    $rowId       = 'row-' . ($node['kode'] ?? md5($node['nama'] . uniqid()));
@endphp

@if($isGroup && !$node['hidden'])

    {{-- Header group --}}
    <tr class="bg-gray-50 dark:bg-gray-800/40 border-b border-gray-100 dark:border-gray-800">
        <td class="px-4 py-3"></td>
        <td colspan="{{ count($buls) + 1 }}"
            class="px-4 py-3 font-bold text-xs uppercase text-gray-900 dark:text-gray-100 tracking-widest"
            style="padding-left: {{ 16 + $depth * 16 }}px">
            {{ $node['nama'] }}
        </td>
    </tr>

    @foreach($node['children'] as $child)
        @include('filament.pages.partials.laba-rugi-telur-node', [
            'node'  => $child,
            'depth' => $depth + 1,
            'buls'  => $buls,
        ])
    @endforeach

    {{-- Subtotal per group (jika punya children) --}}
    @if($hasChildren)
    <tr class="bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
        <td class="px-4 py-2"></td>
        <td class="px-4 py-2 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide italic"
            style="padding-left: {{ 16 + $depth * 16 }}px">
            Total {{ $node['nama'] }}
        </td>
        @foreach($buls as $bulan)
            @php $val = $node['nilai_per_bulan'][$bulan] ?? 0; @endphp
            <td class="px-4 py-2 text-right text-sm font-bold
                {{ $val >= 0 ? 'text-gray-700 dark:text-gray-300' : 'text-red-600 dark:text-red-400' }}">
                {{ $this->formatRupiah($val) }}
            </td>
        @endforeach
    </tr>
    @endif

@elseif($isAnak)

    <tr x-data="{ open: false }"
        x-init="$watch('allOpen', value => { open = value; document.querySelectorAll('[data-parent=\'{{ $rowId }}\']').forEach(r => r.style.display = value ? '' : 'none'); })"
        @click="open = !open; document.querySelectorAll('[data-parent=\'{{ $rowId }}\']').forEach(r => r.style.display = open ? '' : 'none')"
        class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors border-t border-gray-100 dark:border-gray-800">

        <td class="px-4 py-2.5 text-sm font-medium text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-2">
                @if($hasChildren)
                    <svg :class="open ? 'rotate-90' : ''"
                         class="w-3 h-3 flex-shrink-0 transition-transform text-gray-400"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/>
                    </svg>
                @else
                    <span class="w-3"></span>
                @endif
                {{ $node['kode'] }}
            </div>
        </td>
        <td class="px-4 py-2.5 text-sm font-semibold text-gray-800 dark:text-gray-200">
            {{ $node['nama'] }}
        </td>
        @foreach($buls as $bulan)
            @php $val = $node['nilai_per_bulan'][$bulan] ?? 0; @endphp
            <td class="px-4 py-2.5 text-right text-sm font-semibold
                {{ $val >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-red-600 dark:text-red-400' }}">
                {{ $this->formatRupiah($val) }}
            </td>
        @endforeach
    </tr>

    @foreach($node['children'] as $child)
        @include('filament.pages.partials.laba-rugi-telur-node', [
            'node'     => $child,
            'depth'    => $depth + 1,
            'buls'     => $buls,
            'parentId' => $rowId,
        ])
    @endforeach

@elseif($isSub)

    <tr data-parent="{{ $parentId ?? '' }}"
        data-collapse
        style="display: none;"
        class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 border-t border-dashed border-gray-100 dark:border-gray-800">

        <td class="py-2 text-xs font-mono text-gray-400 dark:text-gray-500 italic"
            style="padding-left: 40px">
            {{ $node['kode'] }}
        </td>
        <td class="py-2 text-sm text-gray-500 dark:text-gray-400 italic"
            style="padding-left: 48px">
            {{ $node['nama'] }}
        </td>
        @foreach($buls as $bulan)
            @php $val = $node['nilai_per_bulan'][$bulan] ?? 0; @endphp
            <td class="px-4 py-2 text-right text-sm text-gray-400 dark:text-gray-500 italic">
                {{ $this->formatRupiah($val) }}
            </td>
        @endforeach
    </tr>

@endif