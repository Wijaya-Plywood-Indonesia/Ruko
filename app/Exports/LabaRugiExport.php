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
    // styleMap: [ excelRowIndex => styleInfo ]
    protected array $styleMap      = [];
    protected array $subtotalRows  = [];  // [ excelRow => styleKey ]
    protected int   $headerRow     = 3;
    protected int   $subHeaderRow  = 4;
    protected int   $dataStartRow  = 5;
    protected int   $lastDataRow   = 0;
    protected int   $periodCount   = 0;

    // Column layout:
    // A = Kode, B = Nama Akun
    // For each period: Qty, Rincian, Jumlah  (3 cols per period)
    // period col start: C=2, then every 3

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
            $this->getNamaBulan($first['bulan']) . $first['tahun'] . '-' . $this->getNamaBulan($last['bulan']) . $last['tahun'],
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
        return ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][$b] ?? '';
    }

    protected function getNamaBulanFull(int $b): string
    {
        return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$b] ?? '';
    }

    /** Col index (1-based) untuk kolom qty periode ke-n (0-based) */
    protected function qtyCol(int $pIdx): int  { return 3 + $pIdx * 3; }
    protected function detailCol(int $pIdx): int { return 4 + $pIdx * 3; }
    protected function jumlahCol(int $pIdx): int { return 5 + $pIdx * 3; }

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

    protected function fmt(float $v): string
    {
        return number_format(abs($v), 0, ',', '.');
    }

    protected function hasNilai(array $node): bool
    {
        foreach ($this->bulanList as $p) {
            $k = $this->periodeKey($p);
            if (($node['nilai_per_periode'][$k] ?? 0) != 0) return true;
        }
        foreach ($node['children'] ?? [] as $child) {
            if ($this->hasNilai($child)) return true;
        }
        return false;
    }

    // ── Build rows ───────────────────────────────────────────────────
    public function array(): array
    {
        $out = [];

        // Row 1: Judul
        $row1 = ['INA TELUR', 'Laporan Laba Rugi'];
        for ($p = 0; $p < $this->periodCount; $p++) {
            $row1[] = ''; $row1[] = ''; $row1[] = '';
        }
        $out[] = $row1;
        $this->styleMap[1] = 'title';

        // Row 2: Periode range
        $first = $this->bulanList[0];
        $last  = $this->bulanList[count($this->bulanList) - 1];
        $periodeLabel = $this->periodCount === 1
            ? $this->getNamaBulanFull($first['bulan']) . ' ' . $first['tahun']
            : $this->getNamaBulanFull($first['bulan']) . ' ' . $first['tahun'] . ' s/d ' . $this->getNamaBulanFull($last['bulan']) . ' ' . $last['tahun'];

        $row2 = ['Periode:', $periodeLabel];
        for ($p = 0; $p < $this->periodCount; $p++) {
            $row2[] = ''; $row2[] = ''; $row2[] = '';
        }
        $out[] = $row2;
        $this->styleMap[2] = 'subtitle';

        // Row 3: Header periode (merged per 3 cols)
        $this->headerRow = 3;
        $row3 = ['Kode', 'Nama Akun'];
        foreach ($this->bulanList as $p) {
            $row3[] = $this->getNamaBulanFull($p['bulan']) . ' ' . $p['tahun'];
            $row3[] = '';
            $row3[] = '';
        }
        $out[] = $row3;
        $this->styleMap[3] = 'periodheader';

        // Row 4: Sub-header (Qty | Rincian | Jumlah per periode)
        $this->subHeaderRow = 4;
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

        // Determine subtotal insertion indices (same logic as blade)
        $lastPendapatanIdx = null;
        $lastReturIdx      = null;
        $lastHppIdx        = null;
        $lastBebanIdx      = null;
        $lastLainIdx       = null;
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

            // Insert subtotal rows sama seperti blade
            if ($idx === $lastPendapatanIdx) {
                [$out, $currentRow] = $this->appendSubtotalRow($out, $currentRow, 'Pendapatan Bruto', 'total_pendapatan', 'subtotal_pendapatan');
            }
            if ($idx === $lastReturIdx) {
                [$out, $currentRow] = $this->appendSubtotalRow($out, $currentRow, 'Penjualan Bersih', 'penjualan_bersih', 'subtotal_penjualan');
            }
            if ($idx === $lastHppIdx) {
                [$out, $currentRow] = $this->appendSubtotalRow($out, $currentRow, 'Total HPP & Biaya Produksi', 'total_hpp', 'subtotal_hpp');
                [$out, $currentRow] = $this->appendSubtotalRow($out, $currentRow, 'Laba Kotor', 'laba_kotor', 'laba_kotor');
            }
            if ($idx === $lastBebanIdx) {
                [$out, $currentRow] = $this->appendSubtotalRow($out, $currentRow, 'Total Beban Usaha', 'total_beban_usaha', 'subtotal_beban');
                [$out, $currentRow] = $this->appendSubtotalRow($out, $currentRow, 'Laba (Rugi) Usaha', 'laba_usaha', 'laba_usaha');
            }
            if ($idx === $lastLainIdx) {
                [$out, $currentRow] = $this->appendSubtotalRow($out, $currentRow, 'Laba (Rugi) Sebelum Pajak', 'laba_sebelum_pajak', 'laba_sebelum_pajak');
            }
            if ($idx === count($this->laporanData) - 1) {
                [$out, $currentRow] = $this->appendSubtotalRow($out, $currentRow, 'LABA (RUGI) BERSIH', 'laba_sebelum_pajak', 'laba_bersih');
            }
        }

        $this->lastDataRow = $currentRow - 1;

        return $out;
    }

    protected function appendNode(array $out, array $node, int $depth, int $currentRow): array
    {
        if (!$this->tampilkanSaldoNol && !$this->hasNilai($node)) {
            return [$out, $currentRow];
        }

        $isGroup    = $node['type'] === 'group';
        $isAnakAkun = $node['type'] === 'anak_akun';
        $isSub      = $node['type'] === 'sub_anak_akun';
        $hasChildren = !empty($node['children']);

        // Baris group/anak_akun tanpa nilai langsung (hanya jika punya children)
        if (($isGroup || $isAnakAkun) && $hasChildren) {
            // Header baris
            $indent = str_repeat('   ', $depth);
            $kode   = ($node['kode'] ?? '') ?: '';
            $nama   = $indent . $node['nama'];

            $row = [$kode, $nama];
            // Nilai per periode (untuk group punya children = hanya tampil di subtotal nanti)
            // Tapi jika depth > 0 dan anak_akun, tampilkan di kolom jumlah saja
            foreach ($this->bulanList as $p) {
                $k    = $this->periodeKey($p);
                $val  = (float)($node['nilai_per_periode'][$k] ?? 0);
                // Qty tidak relevan di level ini
                $row[] = '';
                $row[] = '';
                $row[] = $hasChildren ? '' : ($val != 0 || $this->tampilkanSaldoNol ? $this->fmt($val) : '');
            }

            $styleKey = $depth === 0 ? 'group_header' : ($depth === 1 ? 'group_sub' : 'item');
            $this->styleMap[$currentRow] = $styleKey;
            $out[] = $row;
            $currentRow++;

            // Rekursi children
            foreach ($node['children'] as $child) {
                [$out, $currentRow] = $this->appendNode($out, $child, $depth + 1, $currentRow);
            }

            // Subtotal group (jika group level 0 atau anak_akun punya children)
            if ($isGroup && $depth === 0) {
                $row = ['', str_repeat('   ', $depth) . 'Total ' . $node['nama']];
                foreach ($this->bulanList as $p) {
                    $k   = $this->periodeKey($p);
                    $val = (float)($node['nilai_per_periode'][$k] ?? 0);
                    $row[] = '';
                    $row[] = '';
                    $row[] = $val != 0 || $this->tampilkanSaldoNol ? $this->fmt($val) : '';
                }
                $this->styleMap[$currentRow] = 'group_total';
                $out[] = $row;
                $currentRow++;
            } elseif ($isAnakAkun && $hasChildren) {
                // Tampilkan total anak_akun
                $row = [$node['kode'] ?? '', str_repeat('   ', $depth) . 'Total ' . $node['nama']];
                foreach ($this->bulanList as $p) {
                    $k   = $this->periodeKey($p);
                    $val = (float)($node['nilai_per_periode'][$k] ?? 0);
                    $row[] = '';
                    $row[] = $val != 0 || $this->tampilkanSaldoNol ? $this->fmt($val) : '';
                    $row[] = '';
                }
                $this->styleMap[$currentRow] = 'anak_total';
                $out[] = $row;
                $currentRow++;
            }

        } else {
            // Leaf node / sub_anak_akun — tampilkan baris item
            $indent = str_repeat('   ', $depth);
            $kode   = $node['kode'] ?? '';
            $nama   = $indent . $node['nama'];

            $row = [$kode, $nama];
            foreach ($this->bulanList as $p) {
                $k    = $this->periodeKey($p);
                $val  = (float)($node['nilai_per_periode'][$k] ?? 0);
                $qty  = isset($node['qty_per_periode'][$k]) && $node['qty_per_periode'][$k] !== null
                    ? number_format((float)$node['qty_per_periode'][$k], 0, ',', '.')
                    : '';

                if (!$this->tampilkanSaldoNol && $val == 0 && $qty === '') {
                    $row[] = ''; $row[] = ''; $row[] = '';
                } else {
                    $row[] = $qty;
                    $row[] = $val != 0 ? $this->fmt($val) : '';
                    $row[] = '';
                }
            }

            $this->styleMap[$currentRow] = 'item';
            $out[] = $row;
            $currentRow++;
        }

        return [$out, $currentRow];
    }

    protected function appendSubtotalRow(array $out, int $currentRow, string $label, string $ringkasanKey, string $styleKey): array
    {
        $row = ['', $label];
        foreach ($this->bulanList as $p) {
            $k   = $this->periodeKey($p);
            $val = (float)($this->ringkasanPerBulan[$k][$ringkasanKey] ?? 0);
            $row[] = '';
            $row[] = '';
            $row[] = $this->fmt($val);
        }
        $this->styleMap[$currentRow] = $styleKey;
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

        $lastCol    = $this->colLetter(2 + $this->periodCount * 3);
        $lastRow    = $this->lastDataRow ?: $sheet->getHighestRow();
        $totalCols  = 2 + $this->periodCount * 3;

        // Merge judul
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->mergeCells('A2:' . $lastCol . '2');

        // Merge header periode (row 3): setiap 3 kolom
        for ($p = 0; $p < $this->periodCount; $p++) {
            $startLetter = $this->colLetter($this->qtyCol($p));
            $endLetter   = $this->colLetter($this->jumlahCol($p));
            $sheet->mergeCells("{$startLetter}3:{$endLetter}3");
        }

        // Freeze panes setelah header
        $sheet->freezePane('C5');

        // Row styles
        foreach ($this->styleMap as $rowIdx => $styleKey) {
            $range = "A{$rowIdx}:{$lastCol}{$rowIdx}";
            $this->applyStyle($sheet, $rowIdx, $styleKey, $lastCol);
        }

        // Border tipis seluruh data
        if ($lastRow >= 3) {
            $dataRange = "A3:{$lastCol}{$lastRow}";
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->getColor()->setARGB('FFD1D5DB');
            $sheet->getStyle($dataRange)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $sheet->getStyle($dataRange)->getBorders()->getOutline()->getColor()->setARGB('FF374151');
        }

        // Alignment kolom angka (right)
        for ($p = 0; $p < $this->periodCount; $p++) {
            foreach ([$this->qtyCol($p), $this->detailCol($p), $this->jumlahCol($p)] as $col) {
                $sheet->getStyle($this->colLetter($col) . '1:' . $this->colLetter($col) . $lastRow)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }

        // Wrap text kolom B
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