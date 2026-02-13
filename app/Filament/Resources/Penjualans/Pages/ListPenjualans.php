<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Exports\LaporanPenjualanDetailExport;
use App\Exports\LaporanPenjualanExport;
use App\Exports\LaporanKeranjangPenjualanExport;
use App\Filament\Resources\Penjualans\PenjualanResource;
use App\Models\Penjualan;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListPenjualans extends ListRecords
{
    public function exportExcel($method = 'main')
    {
        if (empty($this->laporanGabungan)) {
            $this->loadLaporan();
        }
        $is_success = true;

        try {
            if ($method === 'full') {
                $this->with_detail = true;
                $this->loadLaporan();
                return Excel::download(
                    new LaporanPenjualanDetailExport($this->laporanGabungan),
                    'Laporan-Penjualan-' . now()->format('Y-m-d') . '.xlsx'
                );
            } else if ($method === 'detail') {
                $this->with_detail = true;
                $this->loadLaporanDetail();
                return Excel::download(
                    new LaporanKeranjangPenjualanExport($this->laporanGabungan),
                    'Laporan-Detail-Penjualan-' . now()->format('Y-m-d') . '.xlsx'
                );
            } else {
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
        } finally {
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
            Action::make('preview')
                ->label('Preview Penjualan')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(PenjualanResource::getUrl('preview'))
                ->openUrlInNewTab(true),

            Action::make('pos')
                ->label('POS')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->url(PenjualanResource::getUrl('pos'))
                ->openUrlInNewTab(false)
            ,
        ];
    }
}
