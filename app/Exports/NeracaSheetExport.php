<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class NeracaSheetExport implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected array $rows = [];
    protected array $styleMap = []; // rowIndex => styleType
    protected int $dataStartRow = 4;
    protected int $totalRow = 0;

    public function __construct(
        protected array  $neraca,
        protected bool   $tampilkanSaldoNol = false,
    ) {}

    // ── Sheet title ─────────────────────────────────────────────────
    public function title(): string
    {
        // Batasi 31 karakter (limit Excel)
        return mb_substr($this->neraca['label'], 0, 31);
    }

    // ── Build array data ────────────────────────────────────────────
    public function array(): array
    {
        $fmt    = fn(?float $v): string => $v ? number_format($v, 0, ',', '.') : '-';
        $fmtQty = fn(?float $v): ?string => $v ? number_format($v, 0, ',', '.') : null;

        // Flatten sections → rows
        $flattenSections = null;
        $flattenSections = function (array $sections, int $depth = 0) use (&$flattenSections, $fmt, $fmtQty): array {
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
                        'nilai' => $section['total'],
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
                            'nilai' => $item['nilai'],
                            'qty'   => $item['qty'] ?? null,
                            'depth' => $depth,
                        ];
                    }
                    $rows[] = [
                        'type'  => 'subtotal',
                        'label' => 'Total ' . $section['group'],
                        'kode'  => null,
                        'nilai' => $section['total'],
                        'qty'   => null,
                        'depth' => $depth,
                    ];
                }
            }
            return $rows;
        };

        $filterRows = function (array $rows): array {
            if ($this->tampilkanSaldoNol) return $rows;
            return array_values(array_filter($rows, function ($row) {
                return !($row['type'] === 'item' && ($row['nilai'] ?? 0) == 0);
            }));
        };

        $aktivaRows = $filterRows($flattenSections($this->neraca['aktiva']['sections']));
        $pasivaRows = $filterRows($flattenSections($this->neraca['pasiva']['sections']));
        $maxRows    = max(count($aktivaRows), count($pasivaRows), 1);

        // ── Build Excel rows ────────────────────────────────────────
        $out = [];

        // Row 1: Judul perusahaan
        $out[] = ['INA TELUR', '', '', '', '', ''];
        $this->styleMap[1] = 'company';

        // Row 2: Judul neraca
        $out[] = ['Neraca — ' . $this->neraca['label'], '', '', '', '', ''];
        $this->styleMap[2] = 'title';

        // Row 3: Header kolom
        $out[] = ['AKTIVA', '', '', 'PASIVA', '', ''];
        $this->styleMap[3] = 'colheader';

        // Row 4 sub-header (Akun / Qty / Nilai | Akun / Qty / Nilai)
        $out[] = ['Akun', 'Qty', 'Nilai (Rp)', 'Akun', 'Qty', 'Nilai (Rp)'];
        $this->styleMap[4] = 'subcolheader';

        $this->dataStartRow = 5;

        // Data rows
        for ($i = 0; $i < $maxRows; $i++) {
            $aRow = $aktivaRows[$i] ?? null;
            $pRow = $pasivaRows[$i] ?? null;

            $row = [
                $aRow ? $this->labelWithIndent($aRow) : '',
                $aRow && $aRow['type'] === 'item' && $aRow['qty'] !== null ? $fmtQty((float)$aRow['qty']) : '',
                $aRow && isset($aRow['nilai']) && !in_array($aRow['type'], ['header', 'subheader']) ? $fmt((float)$aRow['nilai']) : '',
                $pRow ? $this->labelWithIndent($pRow) : '',
                $pRow && $pRow['type'] === 'item' && $pRow['qty'] !== null ? $fmtQty((float)$pRow['qty']) : '',
                $pRow && isset($pRow['nilai']) && !in_array($pRow['type'], ['header', 'subheader']) ? $fmt((float)$pRow['nilai']) : '',
            ];

            $excelRow = $this->dataStartRow + $i;

            // Tentukan style berdasarkan tipe dominan
            $dominantType = $aRow['type'] ?? ($pRow['type'] ?? 'item');
            $this->styleMap[$excelRow] = $dominantType;

            $out[] = $row;
        }

        // Grand Total row
        $totalRow = $this->dataStartRow + $maxRows;
        $this->totalRow = $totalRow;
        $fmt2 = fn(?float $v): string => $v ? number_format($v, 0, ',', '.') : '0';
        $out[] = [
            'TOTAL AKTIVA', '', $fmt2($this->neraca['totalAktiva']),
            'TOTAL PASIVA', '', $fmt2($this->neraca['totalPasiva']),
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

    // ── Column widths ────────────────────────────────────────────────
    public function columnWidths(): array
    {
        return [
            'A' => 38,
            'B' => 12,
            'C' => 20,
            'D' => 38,
            'E' => 12,
            'F' => 20,
        ];
    }

    // ── Styles ───────────────────────────────────────────────────────
    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->totalRow ?: ($sheet->getHighestRow());

        // Default font seluruh sheet
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        // Merge judul
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:C3'); // Header AKTIVA
        $sheet->mergeCells('D3:F3'); // Header PASIVA

        // Freeze header
        $sheet->freezePane('A5');

        // Apply row styles
        foreach ($this->styleMap as $rowIdx => $type) {
            $this->applyRowStyle($sheet, $rowIdx, $type);
        }

        // Border seluruh data
        $dataRange = 'A3:F' . $lastRow;
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->getColor()->setARGB('FFD1D5DB');

        // Border luar lebih tebal
        $sheet->getStyle($dataRange)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($dataRange)->getBorders()->getOutline()->getColor()->setARGB('FF374151');

        // Border separator tengah (antara aktiva & pasiva) — kolom D batas kiri
        $sheet->getStyle('D3:D' . $lastRow)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle('D3:D' . $lastRow)->getBorders()->getLeft()->getColor()->setARGB('FF374151');

        // Kolom nilai (C, F) right-align
        $sheet->getStyle('C1:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F1:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B1:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E1:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Kolom label wrap text
        $sheet->getStyle('A1:A' . $lastRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('D1:D' . $lastRow)->getAlignment()->setWrapText(true);

        return [];
    }

    protected function applyRowStyle(Worksheet $sheet, int $row, string $type): void
    {
        $range = "A{$row}:F{$row}";

        match ($type) {
            'company' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF6B7280']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]),

            'title' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF111827']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]),

            'colheader' => (function () use ($sheet, $row) {
                // AKTIVA header (A-C) — biru
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['argb' => 'FF1D4ED8']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEFF6FF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                // PASIVA header (D-F) — hijau
                $sheet->getStyle("D{$row}:F{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['argb' => 'FF15803D']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FDF4']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            })(),

            'subcolheader' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF6B7280']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9FAFB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]),

            'header' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF374151']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]),

            'subheader' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF6B7280']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFAFAFA']],
            ]),

            'subtotal' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF1F2937']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
            ]),

            'grandtotal' => $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F2937']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]),

            default => null,
        };
    }
}