<?php

namespace App\Livewire;

use App\Models\DetailPenjualan;
use App\Models\ReturnPenjualan;
use App\Models\ReturnPenjualanDetail;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;


use Livewire\Component;
use Livewire\Attributes\On;

class TemporaryReturnCart extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithForms, InteractsWithTable, InteractsWithActions;

    /**
     * Array untuk menyimpan state sementara barang yang akan diretur.
     * Struktur: [id => ['qty' => 0, 'keterangan' => '...']]
     */
    public array $listIdRetur = [];
    // Tambahkan listener ini di dalam class TemporaryReturnCart
    #[On('trigger-submit-pengembalian')]
    public function triggerSubmit()
    {
        if (empty($this->listIdRetur)) {
            Notification::make()->title('Keranjang Kosong')->danger()->send();
            return;
        }

        // Kirim data keranjang ke parent (FormReturnPenjualan)
        $this->dispatch('proses-submit-final', keranjangItems: $this->listIdRetur);
    }


    /**
     * Listener untuk menangkap data dari dispatch parent (FormReturnPenjualan)
     */
    #[On('tambah-ke-keranjang-retur')]
    public function tambahBarang($id, $barang_id, $qty, $keterangan_retur, $nama_barang, $satuan, $harga_jual, $subtotal, $potongan, $qty_beli)
    {

        // Simpan semua parameter ke dalam array berdasarkan ID
        $this->listIdRetur[$id] = [
            'id' => $id,
            'barang_id' => $barang_id,
            'qty' => $qty,
            'keterangan' => $keterangan_retur,
            'nama_barang' => $nama_barang,
            'satuan' => $satuan,
            'harga_jual' => $harga_jual,
            'subtotal' => $subtotal,
            'potongan' => $potongan,
            'qty_beli' => $qty_beli, // Simpan qty asli untuk validasi max nanti
        ];

        $this->resetTable();
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $ids = array_keys($this->listIdRetur);
                // Return query kosong jika tidak ada data, agar tidak error
                return DetailPenjualan::query()->whereIn('id', count($ids) > 0 ? $ids : [0])
                ;
            })
            ->header(
                // Kita gunakan view sederhana untuk judul
                fn() => view('filament.components.table-header', [
                    'title' => 'Detail Return saat ini',
                    'description' => 'Berikut ini merupakan barang yang kamu retur saat ini.',
                ])
            )
            ->columns([
                TextColumn::make('barang.nama_barang')
                    ->label('Barang Retur')
                    ->description(fn($record) => "Satuan: {$record->satuan}"),
                // ->searchable(),

                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->money('IDR', locale: 'id'),

                TextColumn::make('qty_retur_count')
                    ->label('Qty Retur')
                    ->getStateUsing(fn($record) => $this->listIdRetur[$record->id]['qty'] ?? 0)
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                TextColumn::make('total_retur')
                    ->label('Total Refund')
                    ->getStateUsing(function ($record) {
                        $qty = $this->listIdRetur[$record->id]['qty'] ?? 0;
                        // Menghitung estimasi uang yang kembali (Harga Jual - (Potongan/Qty)) * Qty Retur
                        // Atau simpelnya: Harga Jual * Qty Retur
                        return $record->harga_jual * $qty;
                    })
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('alasan_retur')
                    ->label('Alasan / Keterangan')
                    ->getStateUsing(fn($record) => $this->listIdRetur[$record->id]['keterangan'] ?? '-')
                    ->wrap()
                    ->description('Keterangan dari form retur'),
            ])
            ->actions([
                // 🔥 ACTION EDIT (IDENTIK DENGAN FORM TAMBAH)
                Action::make('editRetur')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->modalHeading('Edit Detail Retur Barang')
                    ->modalWidth('2xl')
                    ->mountUsing(function ($form, $record) {
                        // Ambil data langsung dari array state
                        $state = $this->listIdRetur[$record->id];

                        $form->fill([
                            'qty_retur' => $state['qty'],
                            'keterangan_retur' => $state['keterangan'],
                            'barang_nama' => $state['nama_barang'],
                            'satuan' => $state['satuan'],
                            'harga_jual_display' => number_format($state['harga_jual'], 0, ',', '.'),
                            'qty_beli' => $state['qty_beli'],
                            'subtotal' => number_format($state['subtotal'], 0, ',', '.'),
                            'potongan' => number_format($state['potongan'], 0, ',', '.'),
                        ]);
                    })->form(fn(DetailPenjualan $record) => [
                        // --- INFORMASI BARANG (DISABLED & DEHYDRATED) ---
                        Grid::make(2)
                            ->schema([
                                TextInput::make('barang_nama')
                                    ->label('Nama Barang')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('satuan')
                                    ->label('Satuan')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('harga_jual_display')
                                    ->label('Harga Jual')
                                    ->prefix('IDR')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('qty_beli')
                                    ->label('Jumlah Beli (Maksimal)')
                                    ->suffix($this->listIdRetur[$record->id]['satuan'])
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('subtotal')
                                    ->label('Total Bayar Item')
                                    ->prefix('IDR')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('potongan')
                                    ->label('Potongan Harga')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),

                        Section::make('Koreksi Data Retur')
                            ->description('Silahkan ubah jumlah atau alasan retur')
                            ->schema([
                                TextInput::make('qty_retur')
                                    ->label('Jumlah Yang Diretur')
                                    ->numeric()
                                    ->required()
                                    ->maxValue(
                                        function ($get) use ($record) {
                                            // 1. Ambil ID Return berdasarkan nota
                                            $returnIds = ReturnPenjualan::where('no_nota', $record->penjualan?->no_nota)->pluck('id');
                                            // 2. Hitung total qty yang sudah diretur untuk barang ini secara spesifik
                                            $totalTeretur = (int) ReturnPenjualanDetail::whereIn('id_return', $returnIds)
                                                ->where('id_barang', $record->barang_id) // Sesuaikan: barang_id sesuai Model Anda
                                                ->sum('qty'); // Langsung sum lebih aman daripada get()->first()
                                
                                            // 3. Hitung sisa maksimal yang bisa diretur
                                            $sisaBisaDiretur = $record->qty - ($totalTeretur ?? 0);
                                            return $sisaBisaDiretur;
                                        }
                                    )->minValue(1)
                                    ->reactive()
                                    ->hint(function ($state) use ($record) {
                                        $returnIds = ReturnPenjualan::where('no_nota', $record->penjualan?->no_nota)->pluck('id');
                                        $totalTeretur = (int) ReturnPenjualanDetail::whereIn('id_return', $returnIds)
                                            ->where('id_barang', $record->barang_id)
                                            ->sum('qty');

                                        // dd($state, $record->barang_id, $record->qty, $totalTeretur, $returnIds->toArray());
                            
                                        $sisaBisaDiretur = $record->qty - ($totalTeretur ?? 0) - ($state ?? 0);
                                        return "Sisa bisa diretur: {$sisaBisaDiretur} {$record->satuan}";
                                    }),

                                Textarea::make('keterangan_retur')
                                    ->label('Alasan Retur (Reason)')
                                    ->maxLength(255)
                                    ->required()
                                    ->rows(3),
                            ]),
                    ])
                    ->action(function (array $data, $record) {
                        // Update state array
                        $this->listIdRetur[$record->id]['qty'] = $data['qty_retur'];
                        $this->listIdRetur[$record->id]['keterangan'] = $data['keterangan_retur'];

                        Notification::make()
                            ->title('Berhasil diubah')
                            ->body("Detail retur {$record->barang->nama_barang} telah diperbarui.")
                            ->success()
                            ->send();

                        $this->resetTable();
                    }),
                Action::make('hapus')
                    ->label('Batal')
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->tooltip('Hapus dari keranjang')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Retur?')
                    ->modalDescription('Item ini akan dihapus dari daftar sementara retur.')
                    ->action(function ($record) {
                        unset($this->listIdRetur[$record->id]);
                        // Jangan lupa reset table agar barisnya hilang
                        $this->resetTable();
                        $this->dispatch('hapus-dari-keranjang-parent', id: $record->id);
                        Notification::make()
                            ->title('Data retur dihapus')
                            ->success()
                            ->send();

                    }),
            ])
            ->emptyStateHeading('Keranjang retur masih kosong')
            ->emptyStateDescription('Pilih barang pada tabel daftar barang di atas untuk diretur.')
            ->emptyStateIcon('heroicon-o-shopping-cart');
    }

    public function render()
    {
        return view('livewire.temporary-return-cart');
    }
}