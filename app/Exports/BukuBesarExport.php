<?php

namespace App\Exports;

use App\Models\IndukAkun;
use App\Models\JurnalUmum;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class BukuBesarExport implements FromView, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    public $filterBulan;
    public $saldoMap = [];
    public $saldoAwalMap = [];

    public function __construct($filterBulan)
    {
        $this->filterBulan = $filterBulan;
        $this->preloadSaldoAwal();
        $this->preloadSaldo();
    }

    private function preloadSaldoAwal(): void
    {
        $date = Carbon::parse($this->filterBulan)->subMonth();
        $this->saldoAwalMap = DB::table('buku_besar')
            ->where('tahun', $date->year)
            ->where('bulan', $date->month)
            ->pluck('saldo', 'no_akun')
            ->toArray();
    }

    private function preloadSaldo(): void
    {
        $start = Carbon::parse($this->filterBulan)->startOfMonth();
        $end   = Carbon::parse($this->filterBulan)->endOfMonth();

        $rows = JurnalUmum::whereBetween('tgl', [$start, $end])
            ->selectRaw("
                no_akun,
                SUM(CASE WHEN LOWER(map) = 'd' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_debit,
                SUM(CASE WHEN LOWER(map) = 'k' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_kredit
            ")
            ->groupBy('no_akun')
            ->get();

        foreach ($rows as $row) {
            $this->saldoMap[$row->no_akun] = [
                'd' => (float) $row->total_debit,
                'k' => (float) $row->total_kredit,
            ];
        }
    }

    public function getSaldoAwal(string $kode): float
    {
        return (float) ($this->saldoAwalMap[$kode] ?? 0);
    }

    public function getTransaksiByKode(string $kode)
    {
        $start = Carbon::parse($this->filterBulan)->startOfMonth();
        $end   = Carbon::parse($this->filterBulan)->endOfMonth();

        return JurnalUmum::where('no_akun', $kode)
            ->whereBetween('tgl', [$start, $end])
            ->orderBy('tgl', 'asc')
            ->orderBy('jurnal', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getTotalRecursive($akun): float
    {
        $total = 0.0;
        $kode = $akun->kode_anak_akun ?? $akun->kode_sub_anak_akun ?? null;

        if ($kode && isset($this->saldoMap[$kode])) {
            $saldoAwal   = (float) ($this->saldoAwalMap[$kode] ?? 0);
            $debit       = (float) ($this->saldoMap[$kode]['d'] ?? 0);
            $kredit      = (float) ($this->saldoMap[$kode]['k'] ?? 0);

            $saldoNormal = strtolower($akun->saldo_normal ?? 'debit');
            $isKredit    = in_array($saldoNormal, ['kredit', 'credit', 'k']);

            if ($isKredit) {
                $total += $saldoAwal + $kredit - $debit;
            } else {
                $total += $saldoAwal + $debit - $kredit;
            }
        } elseif ($kode) {
            $total += (float) ($this->saldoAwalMap[$kode] ?? 0);
        }

        if (isset($akun->children)) {
            foreach ($akun->children as $child) {
                $total += $this->getTotalRecursive($child);
            }
        }

        if (isset($akun->subAnakAkuns)) {
            foreach ($akun->subAnakAkuns as $sub) {
                $total += $this->getTotalRecursive($sub);
            }
        }

        return $total;
    }

    public function view(): View
    {
        $indukAkuns = IndukAkun::with([
            'anakAkuns' => function ($query) {
                $query->whereNull('parent')
                    ->with([
                        'subAnakAkuns',
                        'children' => function ($q) {
                            $q->with([
                                'subAnakAkuns',
                                'children' => function ($q2) {
                                    $q2->with(['subAnakAkuns']);
                                },
                            ]);
                        },
                    ]);
            },
        ])->get();

        return view('exports.buku-besar', [
            'indukAkuns' => $indukAkuns,
            'filterBulan' => $this->filterBulan,
            'export' => $this,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle($sheet->calculateWorksheetDimension())
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF000000'));

        return [
            1    => ['font' => ['bold' => true, 'size' => 16], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE]]],
            2    => ['font' => ['bold' => true, 'size' => 12], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE]]],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => '#,##0.####', // Qty
            'F' => '"Rp" #,##0', // Harga
            'G' => '"Rp" #,##0', // Debit
            'H' => '"Rp" #,##0', // Kredit
            'I' => '"Rp" #,##0', // Saldo
        ];
    }
}
