<?php

use App\Exports\LaporanKeranjangPenjualanExport;
use App\Exports\LaporanPenjualanDetailExport;
use App\Exports\LaporanPenjualanExport;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;


class Download extends Page
{
    protected static string $name = "download";

    public function handleDownload($type)
    {
        try{
            dd('okjess');
            if (empty($this->laporanGabungan)) {
                return redirect()->back()->with('warning', 'Tidak ada data untuk diexport pada rentang tanggal tersebut.');
            }
            $fileName = "Laporan-{$type}-{$this->startDate}-to-{$this->endDate}.xlsx";
            if ($type === 'main') {
                return Excel::download(new LaporanPenjualanExport($this->laporanGabungan), $fileName);
            } elseif ($type === 'detail') {
                return Excel::download(new LaporanKeranjangPenjualanExport($this->laporanGabungan), $fileName);
            }
            return Excel::download(new LaporanPenjualanDetailExport($this->laporanGabungan), $fileName);
        }catch(\Exception $e){
            dd($e->getMessage());
        }
    }

}