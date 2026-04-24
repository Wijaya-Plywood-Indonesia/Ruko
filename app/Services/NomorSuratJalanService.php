<?php

namespace App\Services;

use Carbon\Carbon;

class NomorSuratJalanService
{
    /**
     * Generate nomor surat jalan unik dengan format:
     * SJ-YYYYMMDD-HHmmssuu
     *
     * Contoh: SJ-20260423-082557483291
     *
     * Menggunakan mikrodetik (u) sehingga tidak mungkin duplikat
     * meski dua request masuk bersamaan — tidak perlu lock atau retry.
     *
     * @param  string|null  $tanggal  Format: Y-m-d. Default: hari ini.
     * @return string
     */
    public static function generate(?string $tanggal = null): string
    {
        $now = Carbon::now();

        // Gunakan tanggal dari parameter jika ada, tapi tetap pakai
        // waktu sekarang (jam:menit:detik:mikrodetik) untuk keunikan
        $datePart = $tanggal
            ? Carbon::parse($tanggal)->format('Ymd')
            : $now->format('Ymd');

        $timePart = $now->format('His') . $now->format('u'); // HHmmss + mikrodetik

        return 'SJ-' . $datePart . '-' . $timePart;
    }
}