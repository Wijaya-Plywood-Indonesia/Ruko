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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class NeracaSheetExport implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected array $styleMap   = [];
    protected int   $dataStartRow = 5;
    protected int   $totalRow   = 0;

    // Kolom-kolom yang berisi angka Rupiah (nilai) → C=3, F=6
    protected array $rupiah_cols = [3, 6];
    // Kolom qty → B=2, E=5
    protected array $qty_cols    = [2, 5];

    public function __construct(
        protected array $neraca,
        protected bool  $tampilkanSaldoNol = false,
    ) {}

    public function title(): string
    {
        return mb_substr($this->neraca['label'], 0, 31);
    }

    // ── Nilai ke tipe native (bukan string) ──────────────────────────
    // Kita simpan float/int ke sel, lalu format cell pakai NumberFormat
    // agar Excel tampilkan separator ribuan dengan benar.

    private function toNum(?float $v): string|float|int|null
    {
        if ($v === null) return null;
        // Kembalikan sebagai float; nol dikembalikan 0 (bukan null)
        return $v;
    }

    private function toQty(?float $v): string|float|int|null
    {
        if ($v === null || $v == 0) return null;
        return $v;
    }

    // ── Build array ──────────────────────────────────────────────────
    public function array(): array
    {
        $flattenSections = null;
        $flattenSections = function (array $sections, int $depth = 0) use (&$flattenSections): array {
            $rows = [];
            foreach ($sections as $section) {
                $hasSub  = !empty($section['sub_sections']);
                $hasItem = !empty($section['items']);

                $rows[] = [
                    'type'  => $depth === 0 ? 'header' : 'subheader',
                    'label' => $section['group'],
                    'kode'  => null,
                    'nilai' => null,
                    'qty'   => null,
                    'depth' => $depth,
                ];

                if ($hasSub) {
                    $rows = array_merge($rows, $flattenSections($section['sub_sections'], $depth + 1));
                    $rows[] = [
                        'type'  => 'subtotal',
                        'label' => 'Total ' . $section['group'],
                        'kode'  => null,
                        'nilai' => (float) $section['total'],
                        'qty'   => null,
                        'depth' => $depth,
                    ];
                }

                if ($hasItem) {
                    foreach ($section['items'] as $item) {
                        $rows[] = [
                            'type'  => 'item',
                            'label' => $item['nama'],
                            'kode'  => $item['kode'] ?? null,
                            'nilai' => (float) ($item['nilai'] ?? 0),
                            'qty'   => isset($item['qty']) && $item['qty'] !== null ? (float) $item['qty'] : null,
                            'depth' => $depth,
                        ];
                    }
                    $rows[] = [
                        'type'  => 'subtotal',
                        'label' => 'Total ' . $section['group'],
                        'kode'  => null,
                        'nilai' => (float) $section['total'],
                        'qty'   => null,
                        'depth' => $depth,
                    ];
                }
            }
            return $rows;
        };

        $filterRows = function (array $rows): array {
            if ($this->tampilkanSaldoNol) return $rows;
            return array_values(array_filter($rows, fn($r) => !($r['type'] === 'item' && ($r['nilai'] ?? 0) == 0)));
        };

        $aktivaRows = $filterRows($flattenSections($this->neraca['aktiva']['sections']));
        $pasivaRows = $filterRows($flattenSections($this->neraca['pasiva']['sections']));
        $maxRows    = max(count($aktivaRows), count($pasivaRows), 1);

        $out = [];

        // Row 1: Perusahaan
        $out[] = ['INA TELUR', '', '', '', '', ''];
        $this->styleMap[1] = 'company';

        // Row 2: Judul
        $out[] = ['Neraca — ' . $this->neraca['label'], '', '', '', '', ''];
        $this->styleMap[2] = 'title';

        // Row 3: Header AKTIVA / PASIVA
        $out[] = ['AKTIVA', '', '', 'PASIVA', '', ''];
        $this->styleMap[3] = 'colheader';

        // Row 4: Sub-header
        $out[] = ['Akun', 'Qty', 'Nilai (Rp)', 'Akun', 'Qty', 'Nilai (Rp)'];
        $this->styleMap[4] = 'subcolheader';

        $this->dataStartRow = 5;

        for ($i = 0; $i < $maxRows; $i++) {
            $aRow = $aktivaRows[$i] ?? null;
            $pRow = $pasivaRows[$i] ?? null;

            $isHdrA = $aRow && in_array($aRow['type'], ['header', 'subheader']);
            $isHdrP = $pRow && in_array($pRow['type'], ['header', 'subheader']);
            $isTotA = $aRow && $aRow['type'] === 'subtotal';
            $isTotP = $pRow && $pRow['type'] === 'subtotal';

            $row = [
                // Akun A
                $aRow ? $this->labelWithIndent($aRow) : '',
                // Qty A — null jika bukan item atau qty kosong
                ($aRow && $aRow['type'] === 'item' && $aRow['qty'] !== null)
                    ? $this->toQty($aRow['qty'])
                    : null,
                // Nilai A — null jika header/subheader
                ($aRow && !$isHdrA && isset($aRow['nilai']))
                    ? $this->toNum($aRow['nilai'])
                    : null,
                // Akun P
                $pRow ? $this->labelWithIndent($pRow) : '',
                // Qty P
                ($pRow && $pRow['type'] === 'item' && $pRow['qty'] !== null)
                    ? $this->toQty($pRow['qty'])
                    : null,
                // Nilai P
                ($pRow && !$isHdrP && isset($pRow['nilai']))
                    ? $this->toNum($pRow['nilai'])
                    : null,
            ];

            $dominantType = $aRow['type'] ?? ($pRow['type'] ?? 'item');
            $this->styleMap[$this->dataStartRow + $i] = $dominantType;

            $out[] = $row;
        }

        // Grand Total
        $totalRow        = $this->dataStartRow + $maxRows;
        $this->totalRow  = $totalRow;
        $out[] = [
            'TOTAL AKTIVA', null, (float) $this->neraca['totalAktiva'],
            'TOTAL PASIVA', null, (float) $this->neraca['totalPasiva'],
        ];
        $this->styleMap[$totalRow] = 'grandtotal';

        return $out;
    }

    protected function labelWithIndent(array $row): string
    {
        $indent = str_repeat('  ', $row['depth'] ?? 0);
        $kode   = $row['kode'] ? '[' . $row['kode'] . '] ' : '';
        return $indent . $kode . $row['label'];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 38, 'B' => 12, 'C' => 20,
            'D' => 38, 'E' => 12, 'F' => 20,
        ];
    }

    // ── Styles ───────────────────────────────────────────────────────
    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->totalRow ?: $sheet->getHighestRow();

        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        // Merge
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:C3');
        $sheet->mergeCells('D3:F3');

        // Freeze
        $sheet->freezePane('A5');

        // ── FORMAT NUMBER: kolom nilai sebagai #.##0 (ribuan pakai titik) ──
        // Indonesian number format: ribuan pakai titik, desimal pakai koma
        // Excel menggunakan format locale, tapi kita paksa pakai '#,##0'
        // dan set locale di sisi Excel. Cara paling portable: '#,##0'
        $numberFmt = '#,##0';
        $qtyFmt    = '#,##0.##'; // qty bisa desimal

        // Terapkan ke seluruh kolom C dan F (nilai Rp)
        $sheet->getStyle('C5:C' . $lastRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sheet->getStyle('F5:F' . $lastRow)->getNumberFormat()->setFormatCode($numberFmt);

        // Terapkan ke kolom B dan E (qty)
        $sheet->getStyle('B5:B' . $lastRow)->getNumberFormat()->setFormatCode($qtyFmt);
        $sheet->getStyle('E5:E' . $lastRow)->getNumberFormat()->setFormatCode($qtyFmt);

        // Row styles
        foreach ($this->styleMap as $rowIdx => $type) {
            $this->applyRowStyle($sheet, $rowIdx, $type);
        }

        // Border
        $dataRange = 'A3:F' . $lastRow;
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFD1D5DB');
        $sheet->getStyle($dataRange)->getBorders()->getOutline()
            ->setBorderStyle(Border::BORDER_MEDIUM)
            ->getColor()->setARGB('FF374151');
        $sheet->getStyle('D3:D' . $lastRow)->getBorders()->getLeft()
            ->setBorderStyle(Border::BORDER_MEDIUM)
            ->getColor()->setARGB('FF374151');

        // Alignment
        $sheet->getStyle('C1:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F1:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B1:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E1:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A1:A' . $lastRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('D1:D' . $lastRow)->getAlignment()->setWrapText(true);

        return [];
    }

    protected function applyRowStyle(Worksheet $sheet, int $row, string $type): void
    {
        match ($type) {
            'company' => $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF6B7280']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]),
            'title' => $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF111827']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]),
            'colheader' => (function () use ($sheet, $row) {
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['argb' => 'FF1D4ED8']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEFF6FF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("D{$row}:F{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['argb' => 'FF15803D']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FDF4']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            })(),
            'subcolheader' => $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF6B7280']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9FAFB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]),
            'header' => $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF374151']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
            ]),
            'subheader' => $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF6B7280']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFAFAFA']],
            ]),
            'subtotal' => $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF1F2937']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
            ]),
            'grandtotal' => $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F2937']],
            ]),
            default => null,
        };
    }
}