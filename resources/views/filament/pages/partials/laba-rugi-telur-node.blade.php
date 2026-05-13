@php
    $isGroup     = $node['type'] === 'group';
    $isAnak      = $node['type'] === 'anak_akun';
    $isSub       = $node['type'] === 'sub_anak_akun';
    $hasChildren = !empty($node['children']);
    $rowId       = 'row-' . ($node['kode'] ?? md5($node['nama'] . uniqid()));
    $colSpan     = count($buls) * 2;

    if (!isset($pKey) || !is_callable($pKey)) {
        $pKey = fn(array $p): string => $p['tahun'] . '-' . str_pad($p['bulan'], 2, '0', STR_PAD_LEFT);
    }
@endphp

@if($isGroup && !$node['hidden'])

    {{-- ── GROUP HEADER ── --}}
    <tr class="border-b border-gray-100 dark:border-gray-800">
        <td class="px-4 py-2"></td>
        <td colspan="{{ $colSpan + 1 }}"
            class="py-2.5 text-[11px] font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-widest"
            style="padding-left: {{ 16 + $depth * 16 }}px; border-left: 2px solid #e5e7eb;">
            {{ $node['nama'] }}
        </td>
    </tr>

    @foreach($node['children'] as $child)
        @include('filament.pages.partials.laba-rugi-telur-node', [
            'node'  => $child,
            'depth' => $depth + 1,
            'buls'  => $buls,
            'pKey'  => $pKey,
        ])
    @endforeach

    {{-- Subtotal group --}}
    @if($hasChildren)
    <tr class="border-t border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/30">
        <td class="px-4 py-2"></td>
        <td class="py-2 text-xs font-semibold text-gray-800 dark:text-gray-100"
            style="padding-left: {{ 16 + $depth * 16 }}px">
            Total {{ $node['nama'] }}
        </td>
        @foreach($buls as $periode)
            @php $val = $node['nilai_per_bulan'][$pKey($periode)] ?? 0; @endphp
            <td class="px-4 py-2 border-r border-gray-100 dark:border-gray-800"></td>
            <td class="px-4 py-2 text-right text-sm font-semibold border-l border-gray-200 dark:border-gray-700
                {{ $val >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-rose-500 dark:text-rose-400' }}">
                @if($val < 0)({{ $this->formatRupiah($val) }})@else{{ $this->formatRupiah($val) }}@endif
            </td>
        @endforeach
    </tr>
    @endif

@elseif($isAnak)

    <tr x-data="{ open: true }"
        x-init="
            $watch('allOpen', value => {
                open = value;
                document.querySelectorAll('[data-parent=\'{{ $rowId }}\']').forEach(r => r.style.display = value ? '' : 'none');
            });
            document.querySelectorAll('[data-parent=\'{{ $rowId }}\']').forEach(r => r.style.display = '');
        "
        @click="open = !open; document.querySelectorAll('[data-parent=\'{{ $rowId }}\']').forEach(r => r.style.display = open ? '' : 'none')"
        class="{{ $hasChildren ? 'cursor-pointer' : '' }} hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors
               border-t border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-800/10">

        <td class="px-4 py-2.5 text-xs font-mono font-semibold text-gray-800 dark:text-gray-100 whitespace-nowrap"
            style="padding-left: {{ 16 + $depth * 16 }}px">
            <div class="flex items-center gap-1.5">
                @if($hasChildren)
                    <svg :class="open ? 'rotate-90' : ''"
                         class="w-3 h-3 flex-shrink-0 transition-transform text-gray-400 dark:text-gray-500"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                @else
                    <span class="w-3 inline-block"></span>
                @endif
                {{ $node['kode'] }}
            </div>
        </td>

        <td class="px-4 py-2.5 text-sm font-semibold text-gray-800 dark:text-gray-100">
            {{ $node['nama'] }}
        </td>

        @foreach($buls as $periode)
            @php $val = $node['nilai_per_bulan'][$pKey($periode)] ?? 0; @endphp
            <td class="px-4 py-2.5 border-r border-gray-100 dark:border-gray-800"></td>
            <td class="px-4 py-2.5 text-right text-xs font-medium border-l border-gray-100 dark:border-gray-800
                {{ $val != 0 ? ($val >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-rose-500 dark:text-rose-400') : 'text-gray-400 dark:text-gray-600' }}">
                @if($val != 0)
                    @if($val < 0)({{ $this->formatRupiah($val) }})@else{{ $this->formatRupiah($val) }}@endif
                @endif
            </td>
        @endforeach
    </tr>

    @foreach($node['children'] as $child)
        @include('filament.pages.partials.laba-rugi-telur-node', [
            'node'     => $child,
            'depth'    => $depth + 1,
            'buls'     => $buls,
            'pKey'     => $pKey,
            'parentId' => $rowId,
        ])
    @endforeach

@elseif($isSub)

    <tr data-parent="{{ $parentId ?? '' }}"
        data-collapse
        class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20 transition-colors
               border-t border-dashed border-gray-100 dark:border-gray-800/60">

        <td class="py-1.5 text-xs font-mono text-gray-800 dark:text-gray-100 whitespace-nowrap"
            style="padding-left: {{ 20 + ($depth + 1) * 16 }}px">
            {{ $node['kode'] }}
        </td>

        <td class="py-1.5 text-sm text-gray-800 dark:text-gray-100"
            style="padding-left: {{ 8 + ($depth + 1) * 4 }}px">
            {{ $node['nama'] }}
        </td>

        @foreach($buls as $periode)
            @php $val = $node['nilai_per_bulan'][$pKey($periode)] ?? 0; @endphp
            <td class="px-4 py-1.5 text-right text-sm border-r border-gray-100 dark:border-gray-800
                {{ $val >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-rose-500 dark:text-rose-400' }}">
                {{ $this->formatRupiah($val) }}
            </td>
            <td class="border-l border-gray-100 dark:border-gray-800"></td>
        @endforeach
    </tr>

@endif