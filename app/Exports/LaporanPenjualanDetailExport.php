<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class LaporanPenjualanDetailExport implements
    WithEvents,
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

        foreach ($this->data as $penjualan) {

            // =========================
            // HEADER PENJUALAN
            // =========================
            $headers = [
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
                'Plat',
                'Sopir',
                'Keterangan'
            ];

            $sheet->getDelegate()->fromArray($headers, null, "A{$row}");
            $sheet->getStyle("A{$row}:R{$row}")
                ->applyFromArray($this->styleHeaderPenjualan());
            $row++;

            // =========================
            // DATA PENJUALAN
            // =========================
            $sheet->getDelegate()->fromArray([
                [
                    $penjualan['no_nota'],
                    $penjualan['tanggal'],
                    $penjualan['nama_customer'],
                    $penjualan['member'],
                    $penjualan['alamat'],
                    $penjualan['metode_pembayaran'],
                    (float)$penjualan['total'],
                    (float)$penjualan['bayar'],
                    (float)$penjualan['kembalian'],
                    $penjualan['status_transaksi'],
                    $penjualan['kasir'],
                    $penjualan['validator'],
                    $penjualan['bank'] ?? '-',
                    $penjualan['no_rekening'] ?? '-',
                    $penjualan['kendaraan'] ?? 'ANGKUT SENDIRI',
                    $penjualan['plat_kendaraan'] ?? '-',
                    $penjualan['nama_sopir'] ?? '-',
                    $penjualan['keterangan'] ?? '-',
                ]
            ], null, "A{$row}");

            $sheet->getStyle("G{$row}:I{$row}")->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');

            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // No Nota
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Tanggal
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Tipe
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Metode Bayar
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Status
            $sheet->getStyle("M{$row}:P{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Status


            $row++;

            // =========================
            // TITLE DETAIL PENJUALAN
            // =========================
            $sheet->mergeCells("A{$row}:G{$row}");
            $sheet->setCellValue("A{$row}", "Detail Penjualan");
            $sheet->getStyle("A{$row}")->applyFromArray($this->styleDetailTitle());
            $row++;

            // =========================
            // HEADER DETAIL
            // =========================
            $detailHeaders = [
                'Nama Produk',
                'Harga Awal',
                'Harga Jual',
                'Diskon',
                'Jumlah',
                'Total Diskon',
                'Subtotal'
            ];

            $sheet->getDelegate()->fromArray($detailHeaders, null, "A{$row}");
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($this->styleDetailHeader());
            $row++;

            // =========================
            // DATA DETAIL (LOOP DALAM LOOP)
            // =========================
            foreach ($penjualan['data_penjualan_detail'] as $detail) {
                $sheet->getDelegate()->fromArray([
                    [
                        $detail['nama_barang'],
                        (float)$detail['harga_awal'],
                        (float)$detail['harga_jual'],
                        (float)$detail['diskon'] ?? 0,
                        $detail['jumlah'],
                        (float)$detail['total_diskon'] ?? 0,
                        (float)$detail['subtotal'],
                    ]
                ], null, "A{$row}");

                $sheet->getStyle("B{$row}:D{$row}")->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');
                $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F{$row}")->applyFromArray($this->styleDiskon());
                $sheet->getStyle("G{$row}")->applyFromArray($this->styleSubtotal());




                $row++;
            }

            // =========================
            // SPASI ANTAR PENJUALAN
            // =========================
            $row += 1;
        }
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
                $row['total'],
                $row['bayar'],
                $row['kembalian'],
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
                'formatCode' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
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
                'formatCode' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            ],
        ];
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
