<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LabaRugiExport implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected array $styleMap     = [];
    protected array $subtotalRows = [];
    protected int   $dataStartRow = 5;
    protected int   $lastDataRow  = 0;
    protected int   $periodCount  = 0;

    // Format Excel untuk angka Rupiah dan Qty
    const FMT_RUPIAH = '#,##0';
    const FMT_QTY    = '#,##0.##';

    public function __construct(
        protected array $laporanData,
        protected array $bulanList,
        protected array $ringkasanPerBulan,
        protected bool  $tampilkanSaldoNol = false,
    ) {
        $this->periodCount = count($bulanList);
    }

    public function title(): string
    {
        if (count($this->bulanList) === 1) {
            $p = $this->bulanList[0];
            return mb_substr($this->getNamaBulan($p['bulan']) . ' ' . $p['tahun'], 0, 31);
        }
        $first = $this->bulanList[0];
        $last  = $this->bulanList[count($this->bulanList) - 1];
        return mb_substr(
            $this->getNamaBulan($first['bulan']) . $first['tahun']
            . '-' . $this->getNamaBulan($last['bulan']) . $last['tahun'],
            0, 31
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────
    protected function periodeKey(array $p): string
    {
        return $p['tahun'] . '-' . str_pad($p['bulan'], 2, '0', STR_PAD_LEFT);
    }

    protected function getNamaBulan(int $b): string
    {
        return ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][$b] ?? '';
    }

    protected function getNamaBulanFull(int $b): string
    {
        return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$b] ?? '';
    }

    // Indeks kolom (1-based) per periode
    protected function qtyCol(int $pIdx): int    { return 3 + $pIdx * 3; }
    protected function detailCol(int $pIdx): int  { return 4 + $pIdx * 3; }
    protected function jumlahCol(int $pIdx): int  { return 5 + $pIdx * 3; }

    protected function colLetter(int $colIdx): string
    {
        $letter = '';
        while ($colIdx > 0) {
            $mod    = ($colIdx - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $colIdx = (int)(($colIdx - $mod) / 26);
        }
        return $letter;
    }

    protected function hasNilai(array $node): bool
    {
        foreach ($this->bulanList as $p) {
            if (($node['nilai_per_periode'][$this->periodeKey($p)] ?? 0) != 0) return true;
        }
        foreach ($node['children'] ?? [] as $child) {
            if ($this->hasNilai($child)) return true;
        }
        return false;
    }

    // Konversi ke float untuk disimpan ke sel (bukan string)
    protected function toRupiah(?float $v): ?float
    {
        if ($v === null) return null;
        return $v; // disimpan sebagai angka, format #,##0 via NumberFormat
    }

    protected function toQty(?float $v): ?float
    {
        if ($v === null || $v == 0) return null;
        return $v;
    }

    // ── Build rows ───────────────────────────────────────────────────
    public function array(): array
    {
        $out = [];

        // Row 1: Judul
        $row1 = array_merge(['INA TELUR', 'Laporan Laba Rugi'], array_fill(0, $this->periodCount * 3, null));
        $out[] = $row1;
        $this->styleMap[1] = 'title';

        // Row 2: Periode
        $first = $this->bulanList[0];
        $last  = $this->bulanList[count($this->bulanList) - 1];
        $periodeLabel = $this->periodCount === 1
            ? $this->getNamaBulanFull($first['bulan']) . ' ' . $first['tahun']
            : $this->getNamaBulanFull($first['bulan']) . ' ' . $first['tahun']
              . ' s/d ' . $this->getNamaBulanFull($last['bulan']) . ' ' . $last['tahun'];

        $out[] = array_merge(['Periode:', $periodeLabel], array_fill(0, $this->periodCount * 3, null));
        $this->styleMap[2] = 'subtitle';

        // Row 3: Header periode
        $row3 = ['Kode', 'Nama Akun'];
        foreach ($this->bulanList as $p) {
            $row3[] = $this->getNamaBulanFull($p['bulan']) . ' ' . $p['tahun'];
            $row3[] = null;
            $row3[] = null;
        }
        $out[] = $row3;
        $this->styleMap[3] = 'periodheader';

        // Row 4: Sub-header Qty / Rincian / Jumlah
        $row4 = ['', ''];
        for ($p = 0; $p < $this->periodCount; $p++) {
            $row4[] = 'Qty';
            $row4[] = 'Rincian';
            $row4[] = 'Jumlah';
        }
        $out[] = $row4;
        $this->styleMap[4] = 'subcolheader';

        $this->dataStartRow = 5;
        $currentRow = $this->dataStartRow;

        // Tentukan posisi subtotal (sama dengan logika blade)
        $lastPendapatanIdx = $lastReturIdx = $lastHppIdx = $lastBebanIdx = $lastLainIdx = null;
        foreach ($this->laporanData as $idx => $section) {
            $tipe = $section['tipe'] ?? '';
            if ($tipe === 'pendapatan')                              $lastPendapatanIdx = $idx;
            if ($tipe === 'retur_potongan')                         $lastReturIdx      = $idx;
            if (in_array($tipe, ['hpp', 'beban_produksi']))         $lastHppIdx        = $idx;
            if ($tipe === 'beban_usaha')                            $lastBebanIdx      = $idx;
            if (in_array($tipe, ['pendapatan_lain', 'beban_lain'])) $lastLainIdx       = $idx;
        }
        if ($lastReturIdx === null) $lastReturIdx = $lastPendapatanIdx;

        foreach ($this->laporanData as $idx => $section) {
            if (!$this->tampilkanSaldoNol && !$this->hasNilai($section)) {
                // skip
            } else {
                [$out, $currentRow] = $this->appendNode($out, $section, 0, $currentRow);
            }

            if ($idx === $lastPendapatanIdx)
                [$out, $currentRow] = $this->appendSubtotal($out, $currentRow, 'Pendapatan Bruto',           'total_pendapatan',   'subtotal_pendapatan');
            if ($idx === $lastReturIdx)
                [$out, $currentRow] = $this->appendSubtotal($out, $currentRow, 'Penjualan Bersih',           'penjualan_bersih',   'subtotal_penjualan');
            if ($idx === $lastHppIdx) {
                [$out, $currentRow] = $this->appendSubtotal($out, $currentRow, 'Total HPP & Biaya Produksi', 'total_hpp',          'subtotal_hpp');
                [$out, $currentRow] = $this->appendSubtotal($out, $currentRow, 'Laba Kotor',                 'laba_kotor',         'laba_kotor');
            }
            if ($idx === $lastBebanIdx) {
                [$out, $currentRow] = $this->appendSubtotal($out, $currentRow, 'Total Beban Usaha',          'total_beban_usaha',  'subtotal_beban');
                [$out, $currentRow] = $this->appendSubtotal($out, $currentRow, 'Laba (Rugi) Usaha',          'laba_usaha',         'laba_usaha');
            }
            if ($idx === $lastLainIdx)
                [$out, $currentRow] = $this->appendSubtotal($out, $currentRow, 'Laba (Rugi) Sebelum Pajak',  'laba_sebelum_pajak', 'laba_sebelum_pajak');
            if ($idx === count($this->laporanData) - 1)
                [$out, $currentRow] = $this->appendSubtotal($out, $currentRow, 'LABA (RUGI) BERSIH',         'laba_sebelum_pajak', 'laba_bersih');
        }

        $this->lastDataRow = $currentRow - 1;
        return $out;
    }

    protected function appendNode(array $out, array $node, int $depth, int $currentRow): array
    {
        if (!$this->tampilkanSaldoNol && !$this->hasNilai($node)) {
            return [$out, $currentRow];
        }

        $isGroup     = $node['type'] === 'group';
        $isAnakAkun  = $node['type'] === 'anak_akun';
        $hasChildren = !empty($node['children']);

        if (($isGroup || $isAnakAkun) && $hasChildren) {
            // Baris header group/anak_akun
            $indent = str_repeat('   ', $depth);
            $row = ['', $indent . $node['nama']];
            foreach ($this->bulanList as $p) {
                $row[] = null; // qty
                $row[] = null; // rincian
                $row[] = null; // jumlah (ditampilkan di subtotal)
            }
            $this->styleMap[$currentRow] = $depth === 0 ? 'group_header' : ($depth === 1 ? 'group_sub' : 'item');
            $out[] = $row;
            $currentRow++;

            foreach ($node['children'] as $child) {
                [$out, $currentRow] = $this->appendNode($out, $child, $depth + 1, $currentRow);
            }

            // Baris total group (hanya untuk group depth 0)
            if ($isGroup) {
                $totalRow = ['', str_repeat('   ', $depth) . 'Total ' . $node['nama']];
                foreach ($this->bulanList as $p) {
                    $k   = $this->periodeKey($p);
                    $val = (float)($node['nilai_per_periode'][$k] ?? 0);
                    $totalRow[] = null;
                    $totalRow[] = null;
                    $totalRow[] = $this->toRupiah($val);
                }
                $this->styleMap[$currentRow] = 'group_total';
                $out[] = $totalRow;
                $currentRow++;
            } elseif ($isAnakAkun) {
                // Total anak_akun
                $totalRow = [$node['kode'] ?? '', str_repeat('   ', $depth) . 'Total ' . $node['nama']];
                foreach ($this->bulanList as $p) {
                    $k   = $this->periodeKey($p);
                    $val = (float)($node['nilai_per_periode'][$k] ?? 0);
                    $totalRow[] = null;
                    $totalRow[] = $this->toRupiah($val); // di kolom rincian
                    $totalRow[] = null;
                }
                $this->styleMap[$currentRow] = 'anak_total';
                $out[] = $totalRow;
                $currentRow++;
            }
        } else {
            // Leaf node / sub_anak_akun
            $indent = str_repeat('   ', $depth);
            $kode   = $node['kode'] ?? '';
            $row = [$kode, $indent . $node['nama']];

            foreach ($this->bulanList as $p) {
                $k   = $this->periodeKey($p);
                $val = (float)($node['nilai_per_periode'][$k] ?? 0);
                $qty = isset($node['qty_per_periode'][$k]) && $node['qty_per_periode'][$k] !== null
                    ? (float)$node['qty_per_periode'][$k]
                    : null;

                if (!$this->tampilkanSaldoNol && $val == 0 && $qty === null) {
                    $row[] = null;
                    $row[] = null;
                    $row[] = null;
                } else {
                    $row[] = $this->toQty($qty);      // Qty  → #,##0.##
                    $row[] = $this->toRupiah($val);   // Rincian → #,##0
                    $row[] = null;                    // Jumlah dikosongkan untuk leaf
                }
            }
            $this->styleMap[$currentRow] = 'item';
            $out[] = $row;
            $currentRow++;
        }

        return [$out, $currentRow];
    }

    protected function appendSubtotal(array $out, int $currentRow, string $label, string $ringkasanKey, string $styleKey): array
    {
        $row = ['', $label];
        foreach ($this->bulanList as $p) {
            $k   = $this->periodeKey($p);
            $val = (float)($this->ringkasanPerBulan[$k][$ringkasanKey] ?? 0);
            $row[] = null;
            $row[] = null;
            $row[] = $this->toRupiah($val); // kolom Jumlah
        }
        $this->styleMap[$currentRow]  = $styleKey;
        $this->subtotalRows[$currentRow] = $styleKey;
        $out[] = $row;
        return [$out, $currentRow + 1];
    }

    // ── Column widths ────────────────────────────────────────────────
    public function columnWidths(): array
    {
        $widths = ['A' => 12, 'B' => 36];
        for ($p = 0; $p < $this->periodCount; $p++) {
            $widths[$this->colLetter($this->qtyCol($p))]    = 10;
            $widths[$this->colLetter($this->detailCol($p))] = 18;
            $widths[$this->colLetter($this->jumlahCol($p))] = 20;
        }
        return $widths;
    }

    // ── Styles ───────────────────────────────────────────────────────
    public function styles(Worksheet $sheet): array
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $lastCol   = $this->colLetter(2 + $this->periodCount * 3);
        $lastRow   = $this->lastDataRow ?: $sheet->getHighestRow();

        // Merge judul
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->mergeCells('A2:' . $lastCol . '2');

        // Merge header periode (row 3)
        for ($p = 0; $p < $this->periodCount; $p++) {
            $sheet->mergeCells(
                $this->colLetter($this->qtyCol($p)) . '3:' .
                $this->colLetter($this->jumlahCol($p)) . '3'
            );
        }

        // Freeze
        $sheet->freezePane('C5');

        // ── FORMAT NUMBER: semua kolom angka ──────────────────────────
        for ($p = 0; $p < $this->periodCount; $p++) {
            // Qty
            $qtyLetter = $this->colLetter($this->qtyCol($p));
            $sheet->getStyle($qtyLetter . '5:' . $qtyLetter . $lastRow)
                ->getNumberFormat()->setFormatCode(self::FMT_QTY);

            // Rincian (nilai per akun)
            $detailLetter = $this->colLetter($this->detailCol($p));
            $sheet->getStyle($detailLetter . '5:' . $detailLetter . $lastRow)
                ->getNumberFormat()->setFormatCode(self::FMT_RUPIAH);

            // Jumlah (subtotal)
            $jumlahLetter = $this->colLetter($this->jumlahCol($p));
            $sheet->getStyle($jumlahLetter . '5:' . $jumlahLetter . $lastRow)
                ->getNumberFormat()->setFormatCode(self::FMT_RUPIAH);
        }

        // Row styles
        foreach ($this->styleMap as $rowIdx => $styleKey) {
            $this->applyStyle($sheet, $rowIdx, $styleKey, $lastCol);
        }

        // Border
        if ($lastRow >= 3) {
            $sheet->getStyle('A3:' . $lastCol . $lastRow)
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB('FFD1D5DB');
            $sheet->getStyle('A3:' . $lastCol . $lastRow)
                ->getBorders()->getOutline()
                ->setBorderStyle(Border::BORDER_MEDIUM)
                ->getColor()->setARGB('FF374151');
        }

        // Alignment kolom angka (right)
        for ($p = 0; $p < $this->periodCount; $p++) {
            foreach ([$this->qtyCol($p), $this->detailCol($p), $this->jumlahCol($p)] as $col) {
                $sheet->getStyle($this->colLetter($col) . '1:' . $this->colLetter($col) . $lastRow)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }

        // Wrap kolom B
        $sheet->getStyle('B1:B' . $lastRow)->getAlignment()->setWrapText(true);

        return [];
    }

    protected function applyStyle(Worksheet $sheet, int $row, string $styleKey, string $lastCol): void
    {
        $range = "A{$row}:{$lastCol}{$row}";

        match ($styleKey) {
            'title' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF111827']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]),
            'subtitle' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['size' => 10, 'color' => ['argb' => 'FF6B7280']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]),
            'periodheader' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF1F2937']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]),
            'subcolheader' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF9CA3AF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9FAFB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]),
            'group_header' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF1F2937']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
            ]),
            'group_sub' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF374151']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
            ]),
            'group_total' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF1F2937']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD1D5DB']],
            ]),
            'anak_total' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF374151']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
            ]),
            'item' => $sheet->getStyle($range)->applyFromArray([
                'font' => ['color' => ['argb' => 'FF374151']],
            ]),
            'subtotal_pendapatan', 'subtotal_penjualan' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF1D4ED8']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDBEAFE']],
            ]),
            'subtotal_hpp' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFB45309']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
            ]),
            'subtotal_beban' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF9D174D']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFCE7F3']],
            ]),
            'laba_kotor', 'laba_usaha', 'laba_sebelum_pajak' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF065F46']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD1FAE5']],
            ]),
            'laba_bersih' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF065F46']],
            ]),
            default => null,
        };
    }
}