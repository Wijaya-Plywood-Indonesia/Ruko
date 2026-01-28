<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class LaporanKeranjangPenjualanExport implements WithEvents, 
    WithColumnWidths,
    WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->renderSheet($event->sheet);
            },
        ];
    }

    protected function renderSheet($sheet)
    {
        $row = 1;

        // =========================
        // HEADER PENJUALAN
        // =========================
        $headers = [
        'No Nota',
        'Tanggal',
        'Nama Pelanggan',
        'Nama Barang',
        'Harga Awal',
        'Harga Jual',
        'Diskon',
        'Jumlah',
        'Total Diskon',
        'Subtotal',
        'Kasir',
        'Status',
        ];

        $sheet->getDelegate()->fromArray($headers, null, "A{$row}");
        $sheet->getStyle("A{$row}:L{$row}")
            ->applyFromArray($this->styleHeaderPenjualan());
        $row++;

        foreach ($this->data as $penjualan) {

            // =========================
            // DATA DETAIL (LOOP DALAM LOOP)
            // =========================
            foreach ($penjualan['data_penjualan_detail'] as $detail) {
                $sheet->getDelegate()->fromArray([
                    [
                        $penjualan['no_nota'],
                        $penjualan['tanggal'],
                        $penjualan['nama_customer'],
                        $detail['nama_barang'],
                        $detail['harga_awal'],
                        $detail['harga_jual'],
                        $detail['diskon'] ?? 0,
                        $detail['jumlah'],
                        $detail['total_diskon'] ?? 0,
                        $detail['subtotal'],
                        $penjualan['kasir'],
                        $penjualan['status_transaksi'],
                    ]
                ], null, "A{$row}");

                $sheet->getStyle("E{$row}:J{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // No Nota
                $sheet->getStyle("C{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("E{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("I{$row}:J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("I{$row}")->applyFromArray($this->styleDiskon());
                $sheet->getStyle("J{$row}")->applyFromArray($this->styleSubtotal());
                $row++;
            }
        }
    }

    public function array(): array
    {
        return collect($this->data)->map(function ($row) {
            return [
                $row['no_nota'],
                $row['tanggal'],
                $row['nama_customer'],
                $row['user'],
                $row['status_transaksi'],
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'No Nota',
            'Tanggal',
            'Customer',
            'Nama Barang',
            'Harga Awal',
            'Harga Jual',
            'Diskon',
            'Jumlah',
            'Total Diskon',
            'Subtotal',
            'Kasir',
            'Status',
        ];
    }

    protected function styleHeaderPenjualan(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4ED8'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
    }

    protected function styleDetailTitle(): array
    {
        return [
            'font' => ['bold' => true, 'italic' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
    }

    protected function styleDetailHeader(): array
    {
        return [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            ],

        ];
    }

    protected function styleSubtotal(): array
    {
        return [
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '16A34A'],
            ],
            'numberFormat' => [
                'formatCode' => '#,##0.00',
            ],
        ];
    }

    protected function styleDiskon(): array
    {
        return [
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '991B1B'],
            ],
            'numberFormat' => [
                'formatCode' => '#,##0.00',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22, // No Nota (INV-20260123065015)
            'B' => 20, // Tanggal
            'C' => 25, // Customer
            'D' => 25, // Nama Barang
            'E' => 25, // Harga Awal
            'F' => 25, // Harga Jual
            'G' => 16, // Diskon
            'H' => 16, // Jumlah
            'I' => 30, // Total Diskon
            'J' => 30, // Subtotal
            'K' => 25, // Kasir
            'L' => 10, // Status
        ];
        
    }

    public function title(): string
    {
        return 'LAPORAN PENJUALAN';
    }
}
