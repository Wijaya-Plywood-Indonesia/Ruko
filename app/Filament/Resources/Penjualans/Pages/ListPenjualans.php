<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Exports\LaporanPenjualanExport;
use App\Filament\Resources\Penjualans\PenjualanResource;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListPenjualans extends ListRecords
{
    public array $laporanGabungan = [];

public function mount(): void
{
    $this->loadLaporan();
}

public function data_detail($penjualan_id)
{
    return DetailPenjualan::where('penjualan_id', '=', $penjualan_id, true)
    ->map(function ($detail) {
        return [
            'nama_barang'       => $detail->nama_barang,
            'harga_awal'        => $detail->harga_awal,
            'harga_jual'        => $detail->harga_jual,
            'diskon'            => $detail->diskon,
            'jumlah'            => (string)$detail->qty . " " . $detail->satuan,
            'subtotal'          => $detail->subtotal,
        ];
    })
    ->toArray();
}
public function loadLaporan(): void
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
                'member'                    => $p->is_member ? 'MEMBER' : 'REGULAR',
                'alamat'                    => $p->alamat,
                'metode_pembayaran'         => $p->metode_pembayaran,
                'total'                     => $p->total,
                'bayar'                     => $p->bayar,
                'kembalian'                 => $p->kembalian,
                'kasir'                     => $p->user?->name,
                'validator'                 => $p->validator?->name,
                'bank'                      => $p->bank,
                'no_rekening'               => $p->no_rekening,
                'kendaraan'                 => $p->kendaraan,
                'plat_kendaraan'            => $p->plat_kendaraan,
                'nama_sopir'                => $p->nama_sopir,
                'status_transaksi'          => $p->status_transaksi,
                // 'data_penjualan_detail'     => $this->data_detail($p->id),
            ];
        })
        ->toArray();
}


public function exportExcel()
{
    if (empty($this->laporanGabungan)) {
            $this->loadLaporan();
        }
    $is_success = true;
    try {
        return Excel::download(
            new LaporanPenjualanExport($this->laporanGabungan),
            'Laporan-Penjualan-' . now()->format('Y-m-d') . '.xlsx'
        );
    } catch (\Exception $e) {
        // Handle exception if needed
        $is_success = false;
        Notification::make()
            ->title('Gagal untuk mengunduh data: ')
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
            ->label('Download Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            // ->visible(fn () =>
            //     Penjualan::whereNotNull('validated_by')->exists()
            // )
            ->action(fn ($livewire) => $livewire->exportExcel()),
            


        ];
    }
}
