<?php

namespace App\Exports;


use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanPenjualanExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return collect($this->data)->map(function ($row) {
            return [
                $row['no_nota'],
                $row['tanggal'],
                $row['nama_customer'],
                ($row['member'] === 'MEMBER' || $row['member'] === true || $row['member'] === 1) ? 'MEMBER' : 'REGULAR',
                $row['alamat'],
                $row['metode_pembayaran'],
                (float)$row['total'],
                (float)$row['bayar'],
                (float)$row['kembalian'],
                $row['status_transaksi'],
                $row['kasir'],
                $row['validator'],
                $row['bank'] ?? '-',
                $row['no_rekening'] ?? '-',
                $row['kendaraan'] ?? 'ANGKUT SENDIRI',
                $row['plat_kendaraan'] ?? '-',
                $row['nama_sopir'] ?? '-',
                $row['keterangan'] ?? '-',
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'No Nota',
            'Tanggal',
            'Customer',
            'Tipe',
            'Alamat',
            'Metode Bayar',
            'Total',
            'Bayar',
            'Kembalian',
            'Status',
            'Kasir',
            'Validator',
            'Bank',
            'No Rekening',
            'Kendaraan',
            'Plat Kendaraan',
            'Nama Sopir',
            'Keterangan',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->data) + 1;

        // Header
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4ED8'], // biru profesional
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Data
        $sheet->getStyle("A2:R{$lastRow}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        // Alignment khusus
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // No Nota
        $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Tanggal
        $sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Tipe
        $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Metode Bayar
        $sheet->getStyle("G2:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("J2:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Status
        $sheet->getStyle("O2:O{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Kendaraan
        $sheet->getStyle("P2:P{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Plat
        $sheet->getStyle("R2:R{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Keterangan

        // Format uang
        $sheet->getStyle("L2:N{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');

        // Freeze header
        $sheet->freezePane('A2');

        // Filter
        $sheet->setAutoFilter("A1:R{$lastRow}");

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22, // No Nota (INV-20260123065015)
            'B' => 20, // Tanggal
            'C' => 25, // Customer
            'D' => 12, // Member
            'E' => 30, // Alamat
            'F' => 16, // Metode
            'G' => 18, // Total
            'H' => 18, // Bayar
            'I' => 18, // Kembalian
            'J' => 16, // Status
            'K' => 22, // User
            'L' => 22, // Validator
            'M' => 18, // Bank
            'N' => 22, // Rekening
            'O' => 18, // Kendaraan
            'P' => 18, // Plat
            'Q' => 22, // Sopir
            'R' => 30, // Keterangan
        ];
    }

    public function title(): string
    {
        return 'LAPORAN PENJUALAN';
    }
}
