<table border="1" style="border-collapse: collapse;">
    <tr>
        <td colspan="9" align="center" style="font-weight: bold; font-size: 16px;">Buku Besar</td>
    </tr>
    <tr>
        <td colspan="9" align="center" style="font-weight: bold; font-size: 12px;">Periode: {{ \Carbon\Carbon::parse($filterBulan)->locale('id')->isoFormat('MMMM YYYY') }}</td>
    </tr>
    <tr><td colspan="9"></td></tr>

    @foreach($indukAkuns as $induk)
        @php
            $totalInduk = $induk->anakAkuns
                ->whereNull('parent')
                ->sum(fn($a) => $export->getTotalRecursive($a));

            $adaMutasiInduk = collect(array_keys($export->saldoMap))->contains(function ($kode) use ($induk) {
                return $induk->anakAkuns->whereNull('parent')->contains(function ($anak) use ($kode) {
                    if (($anak->kode_anak_akun ?? null) === $kode) return true;
                    foreach (($anak->subAnakAkuns ?? collect()) as $sub) {
                        if ($sub->kode_sub_anak_akun === $kode) return true;
                    }
                    foreach (($anak->children ?? collect()) as $child) {
                        if (($child->kode_anak_akun ?? null) === $kode) return true;
                        foreach (($child->subAnakAkuns ?? collect()) as $sub) {
                            if ($sub->kode_sub_anak_akun === $kode) return true;
                        }
                    }
                    return false;
                });
            });
        @endphp

        @if($adaMutasiInduk || $totalInduk != 0)
            <tr>
                <td colspan="8" style="background-color: #a9d08e; font-weight: bold;">
                    [{{ $induk->kode_induk_akun }}] {{ $induk->nama_induk_akun }}
                </td>
                <td style="background-color: #a9d08e; font-weight: bold; text-align: right;">
                    {{ $totalInduk }}
                </td>
            </tr>

            @foreach($induk->anakAkuns->whereNull('parent') as $anak)
                @include('exports.partials.buku-besar-anak', ['akun' => $anak, 'depth' => 0])
            @endforeach
        @endif
    @endforeach
</table>
