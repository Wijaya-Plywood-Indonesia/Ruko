<?php

namespace App\Filament\Resources\ReturnPenjualans\Pages;

use App\Filament\Resources\ReturnPenjualans\ReturnPenjualanResource;
use App\Models\DetailPenjualan;
use App\Models\ReturnPenjualan;
use App\Models\ReturnPenjualanDetail;
use DB;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class EditReturnPenjualan extends EditRecord implements HasForms, HasInfolists, HasTable, HasActions, HasSchemas
{
    protected static ?string $title = 'Edit Detail Transaksi Retur';
    protected string $view = 'filament.resources.return-penjualans.pages.edit-return-penjualan';
    public array $listIdRetur = []; // State keranjang untuk sinkronisasi
    /**
     * 🔥 SOLUSI TOTAL BENTROK TRAIT
     * Kita harus memenangkan InteractsWithSchemas untuk semua method yang tumpang tindih 
     * karena di v4, Schemas adalah engine utamanya.
     */
    use InteractsWithForms, InteractsWithSchemas {
        InteractsWithSchemas::getCachedSchemas insteadof InteractsWithForms;
        InteractsWithSchemas::getDefaultTestingSchemaName insteadof InteractsWithForms;
        InteractsWithSchemas::getSchema insteadof InteractsWithForms;
    }

    use InteractsWithInfolists;
    use InteractsWithTable;
    use InteractsWithActions;

    protected static string $resource = ReturnPenjualanResource::class;


    // protected static string $title = "Detail Retur Penjualan";
    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Sinkronisasi data dari DB ke State Array saat pertama kali load
        ReturnPenjualanDetail::where('id_return', $this->getRecord()->id)
            ->get()
            ->mapWithKeys(function ($item) {
                $this->listIdRetur[$item->id] = [ // Gunakan detail_penjualan_id sebagai key utama
                    'id_detail_retur' => $item->id, // Untuk keperluan update DB nantinya
                    'qty' => $item->qty,
                    'keterangan' => $item->keterangan,
                    'nama_barang' => $item->nama_barang,
                    'satuan' => $item->satuan,
                    'harga_jual' => $item->harga_jual,
                    'qty_beli' => $item->detailPenjualan?->qty ?? $item->qty, // Ambil qty beli dari relasi DetailPenjualan jika ada
                    'subtotal' => $item->subtotal,
                    'potongan' => $item->potongan,
                ];
                return [
                    $item->detail_penjualan_id => [
                        'id_detail_retur' => $item->id, // Untuk keperluan update DB nantinya
                        'qty' => $item->qty,
                        'keterangan' => $item->keterangan,
                        'nama_barang' => $item->nama_barang,
                        'satuan' => $item->satuan,
                        'harga_jual' => $item->harga_jual,
                        'qty_beli' => $item->detailPenjualan?->qty ?? $item->qty,
                        'subtotal' => $item->subtotal,
                        'potongan' => $item->potongan,
                    ]
                ];
            })->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncData')
                ->label('Sinkronisasi Ke Database')
                ->icon('heroicon-m-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Simpan Perubahan Detail?')
                ->modalDescription('Semua perubahan pada tabel detail akan dipermanenkan ke database.')
                ->action(function () {
                    $totalActual = ReturnPenjualanDetail::where('id_return', $this->getRecord()->id)
                        ->sum(DB::raw('harga_jual * qty'));

                    $this->getRecord()->update([
                        'total' => $totalActual,
                    ]);

                    Notification::make()->title('Data Berhasil Disinkronisasi')->success()->send();
                })
                ->visible(fn() => auth()->user()?->hasRole("super_admin"))
            ,

            // Ganti DeleteAction menjadi Action biasa untuk menghindari error Type Hint Model
            Action::make('delete_retur')
                ->label('Hapus')
                ->color('danger')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation()
                ->modalHeading('Hapus Data Retur?')
                ->modalDescription('Tindakan ini tidak dapat dibatalkan.')
                ->visible(fn() => auth()->user()?->hasRole("super_admin"))
                ->action(function () {
                    $record = $this->getRecord();

                    // Hapus detail terlebih dahulu (jika tidak ada cascade delete di DB)
                    ReturnPenjualanDetail::where('id_return', $record->id)->delete();

                    $record->delete();

                    Notification::make()
                        ->title('Data Retur Penjualan Berhasil Dihapus')
                        ->success()
                        ->send();

                    return redirect()->to(ReturnPenjualanResource::getUrl('index'));
                })
            ,
        ];
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // Kita gunakan DetailPenjualan sebagai base model untuk tabel 
                // tapi difilter berdasarkan apa yang ada di listIdRetur
                // dd($this->listIdRetur); // Debug untuk memastikan state array sudah benar
    
                $query = ReturnPenjualanDetail::where('id_return', $this->getRecord()->id);
                return $query;
            })
            ->header(
                // Kita gunakan view sederhana untuk judul
                fn() => view('filament.components.table-header', [
                    'title' => 'Detail Return Penjualan',
                    'description' => 'Berikut ini merupakan barang yang kamu return.',
                ])
            )

            ->columns([
                TextColumn::make('nama_barang')
                    ->label('Barang Retur')
                    ->description(fn($record) => "Satuan: {$record->satuan}"),
                // ->searchable(),

                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->money('IDR', locale: 'id'),

                TextColumn::make('qty')
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
                        // Keamanan: Cek apakah data ada di state sebelum load
                        $state = $this->listIdRetur[$record->id] ?? null;
                        if (!$state) {
                            Notification::make()->title('Data tidak ditemukan')->danger()->send();
                            return;
                        }

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
                    })
                    ->form(fn(ReturnPenjualanDetail $record) => [
                        // ... grid informasi barang tetap sama (disabled) ...

                        Grid::make(2) // Bagi dua kolom agar tidak kepanjangan kebawah
                            ->schema([
                                TextInput::make('barang_nama')
                                    ->label('Nama Barang')
                                    ->default($record->barang->nama_barang)
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('satuan')
                                    ->label('Satuan')
                                    ->default($record->satuan)
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('harga_jual_display')
                                    ->label('Harga Jual')
                                    ->default(number_format((float) $record->harga_jual, 0, ',', '.'))
                                    ->prefix('IDR')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('qty_beli')
                                    ->label('Jumlah Beli (Maksimal Retur)')
                                    ->default($record->qty)
                                    ->suffix($record->satuan)
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('subtotal')
                                    ->label('Total Bayar Item')
                                    ->default(number_format((float) $record->subtotal, 0, ',', '.'))
                                    ->prefix('IDR')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('potongan')
                                    ->label('Potongan Harga')
                                    ->default(number_format($record->potongan ?? 0, 0, ',', '.'))
                                    ->disabled()
                                    ->dehydrated(),
                            ]),

                        Section::make('Koreksi Data Retur')
                            ->schema([
                                TextInput::make('qty_retur')
                                    ->label('Jumlah Yang Diretur')
                                    ->numeric()
                                    ->required()
                                    // Validasi Berlapis: Cek terhadap qty di database asli ($record->qty)
                                    ->maxValue(fn() => $record->qty)
                                    ->minValue(1)
                                    ->reactive()
                                    ->helperText(fn($state, $component) => "Maksimal yang bisa diretur: {$record->qty} {$record->satuan}"),

                                Textarea::make('keterangan_retur')
                                    ->label('Alasan Retur')
                                    ->required()
                                    ->maxLength(255) // Keamanan: Batasi panjang string
                                    ->rows(3),
                            ]),
                    ])
                    ->action(function (array $data, ReturnPenjualanDetail $record) {
                        // Validasi Akhir sebelum simpan ke state
                        if ($data['qty_retur'] > $record->qty) {
                            Notification::make()->title('Jumlah retur melebihi pembelian!')->danger()->send();
                            return;
                        }

                        // Update state array dengan sinkronisasi harga
                        $this->listIdRetur[$record->id] = array_merge($this->listIdRetur[$record->id], [
                            'qty' => $data['qty_retur'],
                            'keterangan' => $data['keterangan_retur'],
                        ]);

                        ReturnPenjualanDetail::where('id', $record->id)->update([
                            'qty' => $data['qty_retur'],
                            'keterangan' => $data['keterangan_retur'],
                            'subtotal' => $record->harga_jual * $data['qty_retur'], // Update subtotal sesuai qty baru
                        ]);

                        Notification::make()
                            ->title('Detail retur diperbarui')
                            ->success()
                            ->send();

                        $this->resetTable();

                    })
                    ->visible(fn() => auth()->user()?->hasRole("super_admin"))
                ,
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

                        ReturnPenjualanDetail::where('id', $record->id)->delete();
                        Notification::make()
                            ->title('Data retur dihapus')
                            ->success()
                            ->send();

                    })
                    ->visible(fn() => auth()->user()?->hasRole("super_admin"))
                ,
            ])
            // ->headerAc
            ->emptyStateHeading('Keranjang retur masih kosong')
            ->emptyStateDescription('Pilih barang pada tabel daftar barang di atas untuk diretur.')
            ->emptyStateIcon('heroicon-o-shopping-cart');
    }

}
