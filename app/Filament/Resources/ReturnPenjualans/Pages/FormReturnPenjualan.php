<?php

namespace App\Filament\Resources\ReturnPenjualans\Pages;

use Filament\Schemas\Concerns\InteractsWithSchemas; // SESUAI DOKU 4.X
use Filament\Schemas\Contracts\HasSchemas;         // SESUAI DOKU 4.X
use App\Filament\Resources\ReturnPenjualans\ReturnPenjualanResource;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists; // Tambahkan ini
use Filament\Infolists\Contracts\HasInfolists; // Tambahkan ini
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\FontWeight;

// use Filament\Schemas\Infolist;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable; // PENTING: Harus di-implements
use Filament\Tables\Table;


// use Filament\Schemas\Schema;
// use Filament\Schemas\Components\Section;
// use Filament\Infolists\Components\TextEntry;

class FormReturnPenjualan extends Page implements HasForms, HasInfolists, HasTable, HasActions, HasSchemas
{
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
    protected string $view = 'filament.resources.return-penjualans.pages.form-return-penjualan';

    public ?array $data = [];
    public $dataDetails = null;
    public $penjualanTerpilih = null;
    public array $barangReturSementaras = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    // --- FORM UNTUK PENCARIAN ---
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormSection::make('Pencarian Data')
                    ->components([
                        TextInput::make('nomor_nota')
                            ->label('Cari Nomor Nota')
                            ->placeholder('Ketik minimal 3 karakter...')
                            ->datalist(function ($get) {
                                $search = $get('nomor_nota');

                                if (strlen($search) < 3) {
                                    return [];
                                }

                                // Ambil daftar nota untuk saran autocomplete
                                return Penjualan::where('no_nota', 'like', "%{$search}%")
                                    ->whereNotNull("validated_by")
                                    ->whereIn('status_transaksi', ['LUNAS', 'COD'])
                                    ->limit(10)
                                    ->pluck('no_nota')
                                    ->toArray();
                            })
                            ->extraInputAttributes(['class' => 'hide-datalist-arrow'])
                            ->live()
                            ->afterStateUpdated(fn($state) => $this->pilihNota($state))
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    // --- INFOLIST UNTUK MENAMPILKAN DATA ---
    public function infoNota(Schema $scheme): Schema
    {
        return $scheme
            ->record($this->penjualanTerpilih) // 🔥 HUBUNGKAN DATA DISINI
            ->schema([
                Section::make('Detail Penjualan')
                    ->description('Informasi lengkap mengenai transaksi')
                    ->icon('heroicon-m-information-circle')
                    ->iconColor('info')
                    // ->collapsed()
                    ->components([
                        // --- SUB SECTION 1: INFORMASI NOTA ---
                        Section::make('Informasi Nota')
                            ->columns(2)
                            ->compact() // Membuat padding lebih tipis agar tidak terlalu besar
                            ->components([
                                TextEntry::make('no_nota')
                                    ->label('No Nota')
                                    ->weight(FontWeight::Bold)
                                    ->copyable(),
                                TextEntry::make('tanggal')
                                    ->label('Tanggal')
                                    ->dateTime('d M Y H:i'),
                                TextEntry::make('nama_customer')
                                    ->label('Customer'),
                                TextEntry::make('is_member')
                                    ->label('Status Pelanggan')
                                    ->formatStateUsing(fn(bool $state) => $state ? 'Dia Member' : 'Reguler'),
                                TextEntry::make('keterangan')
                                    ->placeholder('Tidak Ada Catatan')
                                    ->label('Keterangan Nota')
                                    ->columnSpanFull(),
                            ]),

                        // Gunakan Grid untuk membagi baris jika ingin Pembayaran & Pengiriman berdampingan
                        Grid::make(2)
                            // Tambahkan ini agar semua item di dalamnya ditarik sama tinggi
                            ->extraAttributes(['class' => 'items-stretch'])
                            ->components([
                                // --- SUB SECTION 2: PEMBAYARAN ---
                                Section::make('Pembayaran')
                                    // Tambahkan h-full agar section mengikuti tinggi grid
                                    ->extraAttributes(['class' => 'h-full'])
                                    ->columns(2)
                                    ->components([
                                        TextEntry::make('metode_pembayaran')
                                            ->label('Metode')
                                            ->badge()
                                            ->color(fn($state) => $state === 'TUNAI' ? 'success' : 'warning'),
                                        TextEntry::make('status_transaksi')
                                            ->label('STATUS'),
                                        TextEntry::make('total')
                                            ->money('IDR', locale: 'id_ID')
                                            ->weight(FontWeight::Bold),
                                        TextEntry::make('bayar')
                                            ->money('IDR', locale: 'id_ID'),
                                        TextEntry::make('kembalian')
                                            ->money('IDR', locale: 'id_ID')
                                            ->color(fn($state) => $state < 0 ? 'danger' : 'success'),
                                    ]),

                                // --- SUB SECTION 3: PENGIRIMAN ---
                                Section::make('Pengiriman')
                                    // Tambahkan h-full juga di sini
                                    ->extraAttributes(['class' => 'h-full'])
                                    ->columns(1)
                                    ->components([
                                        TextEntry::make('kendaraan')
                                            ->label('Kendaraan'),
                                        TextEntry::make('nama_sopir')
                                            ->label('Nama Sopir'),
                                        TextEntry::make('plat_kendaraan')
                                            ->placeholder('Belum Input NoPol')
                                            ->label('No. Polisi'),
                                    ]),
                            ]),
                        // --- SUB SECTION 4: METADATA ---
                        Section::make('Metadata')
                            ->columns(2)
                            // ->collapsed() // Tetap bisa di-collapse meskipun di dalam section
                            ->components([
                                TextEntry::make('user.name')->label('Kasir'),
                                // ->value($this->penjualanTerpilih->user?->name),
                                TextEntry::make('validator.name')->label('Validasi')->placeholder('Belum Divalidasi'),
                                TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                                TextEntry::make('updated_at')->label('Diubah')->dateTime('d M Y H:i'),
                            ]),
                    ]),
            ]);
    }
    public function submit(): void
    {
        // Untuk saat ini kita biarkan kosong atau berikan notifikasi
        // Fungsi ini wajib ada karena di blade ada wire:submit="submit"
        if (!$this->penjualanTerpilih) {
            $this->addError('data.nomor_nota', 'Silakan pilih nota yang valid terlebih dahulu.');
        }
    }
    public function pilihNota($nota)
    {
        $this->resetErrorBag('data.nomor_nota');
        $this->penjualanTerpilih = null;
        $this->dataDetails = null;
        $this->barangReturSementaras = []; // Reset retur jika ganti nota
        $this->resetTable();

        if (strlen($nota) < 3)
            return;

        $penjualan = Penjualan::where('no_nota', $nota)
            ->whereNotNull("validated_by")
            ->whereIn('status_transaksi', ['LUNAS', 'COD'])
            ->first();

        if ($penjualan) {
            $this->penjualanTerpilih = $penjualan;
            $this->resetTable();
        } else {
            $this->addError('data.nomor_nota', 'Silakan pilih nota yang valid terlebih dahulu.');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->queryStringIdentifier('nota_items')
            ->header(
                // Kita gunakan view sederhana untuk judul
                fn() => view('filament.components.table-header', [
                    'title' => 'Detail Penjualan',
                    'description' => 'Berikut ini merupakan barang yang kamu pesan.',
                ])
            )
            ->query(function () {
                if (!$this->penjualanTerpilih) {
                    return DetailPenjualan::query()->whereRaw('1 = 0');
                }
                return DetailPenjualan::query()
                    ->where('penjualan_id', $this->penjualanTerpilih->id);
            })
            ->columns([

                TextColumn::make('barang.nama_barang')
                    ->label('Barang')
                    // ->searchable()
                    ->sortable(),

                TextColumn::make('qty')
                    ->label('Qty')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('satuan')
                    ->label('Satuan')
                    ->alignCenter(),


                TextColumn::make('harga_awal')
                    ->label('Harga Awal')
                    ->money('IDR', locale: 'id')
                    ->alignRight(),

                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->money('IDR', locale: 'id')
                    ->alignRight(),

                TextColumn::make('potongan')
                    ->label('Potongan')
                    ->money('IDR', locale: 'id')
                    ->placeholder('0')
                    ->alignRight(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR', locale: 'id')
                    ->alignRight()
                    ->weight('bold'),

            ])
            ->actions([
                Action::make('tambahKeRetur')
                    ->label('Retur')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    // 🔥 LOGIKA DISABLE: Jika ID ada di array, maka tombol mati
                    ->disabled(fn(DetailPenjualan $record) => $this->isSudahAdaDiRetur($record->id))
                    ->modalHeading('Input Detail Retur Barang')
                    ->modalWidth('2xl') // Kita buat agak lebar karena fieldnya banyak
                    ->form(fn(DetailPenjualan $record) => [
                        // --- INFORMASI BARANG (DISABLED & DEHYDRATED) ---
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

                                TextInput::make('harga_jual')
                                    ->label('Harga Jual')
                                    ->default(number_format($record->harga_jual, 0, ',', '.'))
                                    ->prefix('IDR')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('qty_beli')
                                    ->label('Jumlah Beli (Maksimal)')
                                    ->default($record->qty)
                                    ->suffix($record->satuan)
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('subtotal')
                                    ->label('Total Bayar Item')
                                    ->default(number_format($record->subtotal, 0, ',', '.'))
                                    ->prefix('IDR')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('potongan')
                                    ->label('Potongan Harga')
                                    ->default(number_format($record->potongan ?? 0, 0, ',', '.'))
                                    ->disabled()
                                    ->dehydrated(),
                            ]),

                        Section::make('Input Data Retur')
                            ->description('Tentukan jumlah dan alasan pengembalian barang')
                            ->schema([
                                TextInput::make('qty_retur')
                                    ->label('Jumlah Yang Diretur')
                                    ->numeric()
                                    ->default(1)
                                    ->maxValue($record->qty) // Validasi tidak boleh lebih dari beli
                                    ->minValue(1)
                                    ->required()
                                    ->reactive()
                                    ->hint(fn($state) => "Sisa barang: " . ($record->qty - $state)),

                                Textarea::make('keterangan_retur')
                                    ->label('Alasan Retur (Reason)')
                                    ->placeholder('Contoh: Barang cacat produksi / expired')
                                    ->required() // Biasanya retur wajib ada alasan
                                    ->rows(3),
                            ]),
                    ])
                    ->action(function (array $data, DetailPenjualan $record) {
                        $idUnik = $record->id;

                        // Simpan ke state array
                    // 1. Simpan ke state lokal agar tombol langsung ter-disable
                        $this->barangReturSementaras[$idUnik] = true;
                        // Kirim event ke tabel sementara (TemporaryReturnCart)
                        // Di file Parent (FormReturnPenjualan / Resource)
                        $this->dispatch(
                            'tambah-ke-keranjang-retur',
                            id: $record->id,
                            qty: $data['qty_retur'],
                            keterangan_retur: $data['keterangan_retur'],
                            nama_barang: $record->barang->nama_barang,
                            satuan: $record->satuan,
                            harga_jual: $record->harga_jual,
                            subtotal: $record->subtotal,
                            potongan: $record->potongan ?? 0,
                            qty_beli: $record->qty
                        );

                        Notification::make()
                            ->title('Berhasil ditambahkan')
                            ->body("{$record->barang->nama_barang} sebanyak {$data['qty_retur']} unit masuk daftar retur.")
                            ->success()
                            ->send();

                        $this->resetTable();
                    })
            ]);
    }

    public function isSudahAdaDiRetur($id): bool
    {
        return array_key_exists($id, $this->barangReturSementaras);
    }


    protected $listeners = [
    'hapus-dari-keranjang-parent' => 'handleBarangDihapus'
];

public function handleBarangDihapus($id)
{
    if (isset($this->barangReturSementaras[$id])) {
        unset($this->barangReturSementaras[$id]);
    }
}
}
