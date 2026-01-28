<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Exports\LaporanPenjualanDetailExport;
use App\Exports\LaporanPenjualanExport;
use App\Exports\LaporanKeranjangPenjualanExport;
use App\Filament\Resources\Penjualans\PenjualanResource;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListPenjualans extends ListRecords
{
    public array $laporanGabungan = [];

public function mount(): void
{
    $this->with_detail;
    $this->loadLaporan();
}
public bool $with_detail = false;

public function data_detail($penjualan_id)
{
    return DetailPenjualan::where('penjualan_id', $penjualan_id)
    ->get()
    ->map(function ($detail) {

        return [
            'nama_barang'       => $detail->nama_barang,
            'harga_awal'        => $detail->harga_awal,
            'harga_jual'        => $detail->harga_jual,
            'diskon'            => (string)$detail->potongan ?? 0,
            'jumlah'            => (string)$detail->qty . " " . $detail->satuan,
            'total_diskon'      => (string)($detail->potongan * $detail->qty),
            'subtotal'          => $detail->subtotal,
        ];
    })
    ->toArray();
}
public function loadLaporan()
{   
    $this->laporanGabungan = Penjualan::query()
        ->whereNotNull('validated_by')
        ->with(['user', 'validator'])
        ->get()
        ->map(function ($p) {
            $data_detail = $this->with_detail === true ? $this->data_detail($p->id) : [];
            // dd($data_detail);
            return [
                'no_nota'                   => $p->no_nota,
                'tanggal'                   => $p->tanggal,
                'nama_customer'             => $p->nama_customer,
                'member'                    => $p->is_member ? 'MEMBER' : 'REGULAR',
                'alamat'                    => $p->alamat,
                'metode_pembayaran'         => $p->metode_pembayaran,
                'total'                     => $p->total,
                'bayar'                     => $p->bayar,
                'kembalian'                 => $p->kembalian,
                'kasir'                     => $p->user?->name,
                'validator'                 => $p->validator?->name,
                'bank'                      => $p->bank ?? '-',
                'no_rekening'               => $p->no_rekening ?? '-',
                'kendaraan'                 => $p->kendaraan ?? 'ANTAR SENDIRI',
                'plat_kendaraan'            => $p->plat_kendaraan ?? '-',
                'nama_sopir'                => $p->nama_sopir,
                'status_transaksi'          => $p->status_transaksi,
                'data_penjualan_detail'     => $data_detail,
            ];
        })
        ->toArray();
}
public function loadLaporanDetail()
{   
    $this->laporanGabungan = Penjualan::query()
        ->whereNotNull('validated_by')
        ->with(['user', 'validator'])
        ->get()
        ->map(function ($p) {
            return [
                'no_nota'                   => $p->no_nota,
                'tanggal'                   => $p->tanggal,
                'nama_customer'             => $p->nama_customer,
                'kasir'                     => $p->user?->name,
                'status_transaksi'          => $p->status_transaksi,
                'data_penjualan_detail'     => $this->data_detail($p->id),
            ];
        })
        ->toArray();
}


public function exportExcel($method = 'main')
{
    if (empty($this->laporanGabungan)) {
        $this->loadLaporan();
    }
    $is_success = true;

    try {
        if($method === 'full') {
            $this->with_detail = true;
            $this->loadLaporan();
            return Excel::download(
                new LaporanPenjualanDetailExport($this->laporanGabungan),
                'Laporan-Penjualan-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        else if($method === 'detail'){
            $this->with_detail = true;
            $this->loadLaporanDetail();
            return Excel::download(
                new LaporanKeranjangPenjualanExport($this->laporanGabungan),
                'Laporan-Detail-Penjualan-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        else{
            $this->with_detail = false;
            $this->loadLaporan();
            return Excel::download(
                new LaporanPenjualanExport($this->laporanGabungan),
                'Laporan-Penjualan-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
    } catch (\Exception $e) {
        // Handle exception if needed
        $is_success = false;
        Notification::make()
            ->title('Gagal untuk mengunduh data ')
            ->body('Silakan hubungi developer.')
            ->danger()
            ->send();
    }finally {
        if ($is_success) {
            Notification::make()
                ->title('Download data berhasil.')
                ->body('File excel sudah tersedia di perangkat anda.')
                ->success()
                ->send();
        }
    }
}
    protected static string $resource = PenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pos')
                ->label('POS')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->url(PenjualanResource::getUrl('pos'))
                ->openUrlInNewTab(false)
            ,

            Action::make('export')
            ->label('Download Penjualan Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            // ->visible(fn () =>
            //     Penjualan::whereNotNull('validated_by')->exists()
            // )
            ->action(fn ($livewire) => $livewire->exportExcel('main')),
            
            Action::make('export-detail')
            ->label('Download Detail Penjualan Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            // ->visible(fn () =>
            //     Penjualan::whereNotNull('validated_by')->exists()
            // )
            ->action(fn ($livewire) => $livewire->exportExcel('detail')),
            
            Action::make('full-export')
            ->label('Download Data Full Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->action(fn ($livewire) => $livewire->exportExcel('full')),
            


        ];
    }
}
