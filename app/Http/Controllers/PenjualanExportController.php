<?php

namespace App\Http\Controllers;

use App\Exports\LaporanKeranjangPenjualanExport;
use App\Exports\LaporanPenjualanDetailExport;
use App\Exports\LaporanPenjualanExport;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PenjualanExportController extends Controller
{
    public array $laporanGabungan = [];

    public string $type = 'main';

    public string $dariTanggal = '';

    public string $sampaiTanggal = '';

    public function mount(): void
    {
        $this->with_detail;
        $this->loadLaporan();
    }

    public bool $with_detail = false;

    public function data_detail($penjualan_id)
    {
        return DetailPenjualan::where('penjualan_id', $penjualan_id)
            ->whereBetween('created_at', [$this->dariTanggal.' 00:00:00', $this->sampaiTanggal.' 23:59:59'])
            ->get()
            ->map(function ($detail) {

                return [
                    'nama_barang' => $detail->nama_barang,
                    'harga_awal' => $detail->harga_awal,
                    'harga_jual' => $detail->harga_jual,
                    'diskon' => (string) $detail->potongan ?? 0,
                    'jumlah' => (string) $detail->qty.' '.$detail->satuan,
                    'total_diskon' => (string) ($detail->potongan * $detail->qty),
                    'subtotal' => $detail->subtotal,
                    'keterangan' => $detail->keterangan ?? '-',
                ];
            })
            ->toArray();
    }

    public function loadLaporan()
    {
        $this->laporanGabungan = Penjualan::whereBetween('created_at', [$this->dariTanggal.' 00:00:00', $this->sampaiTanggal.' 23:59:59'])
            ->whereNotNull('validated_by')
            ->with(['user', 'validator'])
            ->get()
            ->map(function ($p) {
                $data_detail = $this->type !== 'main' ? $this->data_detail($p->id) : [];

                // dd($data_detail);
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
                    'keterangan' => $p->keterangan ?? '-',
                    'data_penjualan_detail' => $data_detail,
                ];
            })
            ->toArray();
    }

    public function loadLaporanDetail()
    {
        $this->laporanGabungan = Penjualan::whereBetween('created_at', [$this->dariTanggal.' 00:00:00', $this->sampaiTanggal.' 23:59:59'])
            ->whereNotNull('validated_by')
            ->with(['user', 'validator'])

            ->get()
            ->map(function ($p) {
                return [
                    'no_nota' => $p->no_nota,
                    'tanggal' => $p->tanggal,
                    'nama_customer' => $p->nama_customer,
                    'kasir' => $p->user?->name,
                    'status_transaksi' => $p->status_transaksi,
                    'keterangan' => $p->keterangan ?? '-',
                    'data_penjualan_detail' => $this->data_detail($p->id),
                ];
            })
            ->toArray();
    }

    public function download(Request $request)
    {
        if (!auth()->check()) {
                // Alihkan ke login filament dengan pesan error
                return redirect()->route('filament.admin.auth.login')
                    ->with('error', 'Silahkan login terlebih dahulu untuk mengakses fitur ini.');
            }


        $is_success = false;
        
        try {
            $is_success = true;

            // Validasi simpel agar aman
            $request->validate([
                'type' => 'required',
                'dari' => 'required|date',
                'sampai' => 'required|date',
            ]);

            $this->type = $request->type;
            $this->dariTanggal = $request->dari;
            $this->sampaiTanggal = $request->sampai;

            match ($this->type) {
                'detail' => $this->loadLaporanDetail(),
                'full' => $this->loadLaporan(),
                default => $this->loadLaporan(),
            };

            $fileName = "Laporan-{$this->type}-{$this->dariTanggal}-to-{$this->sampaiTanggal}.xlsx";

            // Tentukan class export
            $export = match ($this->type) {
                'detail' => new LaporanKeranjangPenjualanExport($this->laporanGabungan),
                'full' => new LaporanPenjualanDetailExport($this->laporanGabungan),
                default => new LaporanPenjualanExport($this->laporanGabungan),
            };

            return Excel::download($export, $fileName);
        } catch (Exception $e) {
            // Handle exception if needed
            $is_success = false;
            // Notification::make()
            //     ->title('Gagal untuk mengunduh data ')
            //     ->body('Silakan hubungi developer.')
            //     ->danger()
            //     ->send();
        } finally {
            if ($is_success) {
                // Notification::make()
                //     ->title('Download data berhasil.')
                //     ->body('File excel sudah tersedia di perangkat anda.')
                //     ->success()
                //     ->send();
            }
        }
    }
}
