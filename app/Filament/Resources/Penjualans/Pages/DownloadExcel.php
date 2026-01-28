<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Exports\LaporanKeranjangPenjualanExport;
use App\Exports\LaporanPenjualanDetailExport;
use App\Exports\LaporanPenjualanExport;
use App\Filament\Resources\Penjualans\PenjualanResource;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Filament\Resources\Pages\Page;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DownloadExcel extends Page
{
    protected static string $resource = PenjualanResource::class;

    // Kita tetap butuh view kosong, meskipun jarang ter-render
    protected string $view = 'filament.resources.penjualans.pages.download-excel';

    public function mount(Request $request)
    {
        $type = $request->query('type', 'main');
        $startDate = $request->query('dari_tanggal');
        $endDate = $request->query('sampai_tanggal');
        
        // 1. Ambil Data (Sama seperti logika di preview agar akurat)
        $laporanGabungan = $this->fetchData($type, $startDate, $endDate);
        
        if (empty($laporanGabungan)) {
            // Jika kosong, kembali ke halaman sebelumnya dengan pesan
            return redirect()
                ->to(PenjualanResource::getUrl('preview-export', [
                    'dari_tanggal' => $startDate,
                    'sampai_tanggal' => $endDate
                ]))
                ->with('warning', 'Tidak ada data untuk diexport.');
        }

        // 2. Jalankan Proses Download
        // return $this->handleDownload($type, $startDate, $endDate, $laporanGabungan);
    try {

        $fileName = "Laporan-{$type}-{$startDate}-to-{$endDate}.xlsx";

        $export = match($type) {
            'main'   => new LaporanPenjualanExport($laporanGabungan),
            'detail' => new LaporanKeranjangPenjualanExport($laporanGabungan),
            'full'   => new LaporanPenjualanDetailExport($laporanGabungan),
            default  => new LaporanPenjualanExport($laporanGabungan),
        };
        
        // Gunakan cara download langsung dari Laravel Excel
        return Excel::download($export, $fileName);

    } catch (\Exception $e) {
        dd("Export Error: " . $e->getMessage());
    }
}
    protected function fetchData($type, $startDate, $endDate)
    {
        $query = Penjualan::query()
            ->whereNotNull('validated_by')
            ->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ])
            ->with(['user', 'validator'])
            ->latest()
            ->get();

        return $query->map(function ($p) use ($type) {
            $withDetail = in_array($type, ['detail', 'full']);
            
            return [
                'no_nota' => $p->no_nota,
                'tanggal' => $p->tanggal,
                'nama_customer' => $p->nama_customer,
                'member' => $p->is_member ? 'MEMBER' : 'REGULAR',
                'alamat' => $p->alamat,
                'metode_pembayaran' => $p->metode_pembayaran,
                'total' => $p->total,
                'bayar' => $p->bayar,
                'kembalian' => $p->kembalian,
                'kasir' => $p->user?->name,
                'validator' => $p->validator?->name,
                'bank' => $p->bank ?? '-',
                'no_rekening' => $p->no_rekening ?? '-',
                'kendaraan' => $p->kendaraan ?? 'ANTAR SENDIRI',
                'plat_kendaraan' => $p->plat_kendaraan ?? '-',
                'nama_sopir' => $p->nama_sopir,
                'status_transaksi' => $p->status_transaksi,
                'data_penjualan_detail' => $withDetail ? $this->getDetail($p->id) : [],
            ];
        })->toArray();
    }

    private function getDetail($id)
    {
        return DetailPenjualan::where('penjualan_id', $id)->get()->map(fn($d) => [
            'nama_barang' => $d->nama_barang,
            'harga_awal' => $d->harga_awal,
            'harga_jual' => $d->harga_jual,
            'diskon' => $d->potongan ?? 0,
            'jumlah' => $d->qty . ' ' . $d->satuan,
            'total_diskon' => ($d->potongan * $d->qty),
            'subtotal' => $d->subtotal,
        ])->toArray();
    }

public function handleDownload($type, $startDate, $endDate, $laporanGabungan)
{
    try {

        $fileName = "Laporan-{$type}-{$startDate}-to-{$endDate}.xlsx";

        $export = match($type) {
            'main'   => new LaporanPenjualanExport($laporanGabungan),
            'detail' => new LaporanKeranjangPenjualanExport($laporanGabungan),
            'full'   => new LaporanPenjualanDetailExport($laporanGabungan),
            default  => new LaporanPenjualanExport($laporanGabungan),
        };
        
        // Gunakan cara download langsung dari Laravel Excel
        dd($laporanGabungan);
        return Excel::download($export, $fileName);

    } catch (\Exception $e) {
        dd("Export Error: " . $e->getMessage());
    }
}}