<?php

namespace App\Livewire;

use App\Models\ReturnPenjualan;
use App\Models\ReturnPenjualanDetail;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Livewire\Attributes\On;

class PastReturnPenjualan extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithForms, InteractsWithTable, InteractsWithActions;
    public ?string $no_nota = null;
    public bool $isNotaAvailable = false;

    public function isReadOnly(): bool
    {
        return true; // Pastikan tabel ini hanya untuk read-only
    }

    #[On('past-return-penjualan-updated')]
    public function mountNota($no_nota = null)
    {
        $this->no_nota = $no_nota;

        $returnIds = ReturnPenjualan::where('no_nota', $this->no_nota)->pluck('id');

        // Jika nota kosong atau tidak ada retur, kembalikan query kosong yang valid
        if ($returnIds->isEmpty()) {
            $this->isNotaAvailable = false;
            return ReturnPenjualanDetail::query()->whereRaw('1 = 0');
        }else{
            $this->isNotaAvailable = true;
        }

        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // Ambil ID semua retur berdasarkan nota
                $returnIds = ReturnPenjualan::where('no_nota', $this->no_nota)->pluck('id');

                // Jika nota kosong atau tidak ada retur, kembalikan query kosong yang valid
                if ($returnIds->isEmpty()) {
                    $this->isNotaAvailable = false;
                    return ReturnPenjualanDetail::query()->whereRaw('1 = 0');
                }

                $this->isNotaAvailable = true;
                return ReturnPenjualanDetail::query()->whereIn('id_return', $returnIds);
            })
            ->header(
                // Kita gunakan view sederhana untuk judul
                fn() => view('filament.components.table-header', [
                    'title' => 'Detail Return sebelumnya',
                    'description' => 'Berikut ini merupakan barang yang sudah diretur sebelumnya.',
                ])
            )
            ->columns([
                TextColumn::make('nama_barang')
                    ->label('Barang Retur')
                    ->description(fn($record) => "Satuan: {$record->satuan}"),

                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->money('IDR', locale: 'id'),

                TextColumn::make('qty')
                    ->label('Qty Retur')
                    // Langsung mengambil dari kolom 'qty' di tabel return_penjualan_details
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                TextColumn::make('total_retur')
                    ->label('Total Refund')
                    ->getStateUsing(function ($record) {
                        // Kalkulasi langsung dari data di database
                        return $record->harga_jual * $record->qty;
                    })
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('keterangan')
                    ->label('Alasan / Keterangan')
                    ->wrap()
                    ->description('Keterangan dari database'),
            ]);
        ;
    }
    public function render()
    {
        return view('livewire.past-return-penjualan');
    }
}
