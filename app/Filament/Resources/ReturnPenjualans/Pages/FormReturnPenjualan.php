<?php

namespace App\Filament\Resources\ReturnPenjualans\Pages;

use App\Models\ReturnPenjualan;
use App\Models\ReturnPenjualanDetail;
use Filament\Schemas\Components\Actions;
use Illuminate\Support\Facades\DB;

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
    public ?Penjualan $penjualanTerpilih = null;
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
                            ->extraInputAttributes([
                                'class' => 'hide-datalist-arrow'

                            ])
                            ->live(debounce: 200)

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
            // Memastikan nota ini belum ada di tabel penjualan_return
            // ? ->whereDoesntHave('returns')
            ->first();

        if ($penjualan) {
            $this->penjualanTerpilih = $penjualan;
            $this->resetTable();
            $this->getSchema('infoNota')->record($penjualan);
            // Contoh di Parent Component (FormReturnPenjualan)
            $this->dispatch('past-return-penjualan-updated', no_nota: $penjualan->no_nota);
        } else {
            $this->addError('data.nomor_nota', 'Silakan pilih nota yang valid terlebih dahulu.');
            $this->barangReturSementaras = [];
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
                    ->disabled(function (DetailPenjualan $record) {
                        // 1. Ambil ID Return berdasarkan nota
                        $returnIds = ReturnPenjualan::where('no_nota', $this->data['nomor_nota'])->pluck('id');

                        // 2. Hitung total qty yang sudah diretur untuk barang ini secara spesifik
                        $totalTeretur = ReturnPenjualanDetail::whereIn('id_return', $returnIds)
                            ->where('id_barang', $record->barang_id) // Sesuaikan: barang_id sesuai Model Anda
                            ->sum('qty'); // Langsung sum lebih aman daripada get()->first()
            
                        // 3. Logika: Disable jika (Total teretur >= Qty beli) ATAU sudah masuk list sementara
                        $isLunasRetur = $totalTeretur >= $record->qty;
                        $isDalamKeranjang = $this->isSudahAdaDiRetur($record->id);

                        return $isLunasRetur || $isDalamKeranjang;
                    })
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

                        Section::make('Input Data Retur')
                            ->description('Tentukan jumlah dan alasan pengembalian barang')
                            ->schema([
                                TextInput::make('qty_retur')
                                    ->label('Jumlah Yang Diretur')
                                    ->numeric()
                                    ->default(1)
                                    ->maxValue(
                                        function ($get) use ($record) {
                                            // 1. Ambil ID Return berdasarkan nota
                                            $returnIds = ReturnPenjualan::where('no_nota', $this->data['nomor_nota'])->pluck('id');

                                            // 2. Hitung total qty yang sudah diretur untuk barang ini secara spesifik
                                            $totalTeretur = (int) ReturnPenjualanDetail::whereIn('id_return', $returnIds)
                                                ->where('id_barang', $record->barang_id) // Sesuaikan: barang_id sesuai Model Anda
                                                ->sum('qty'); // Langsung sum lebih aman daripada get()->first()
                                
                                            // 3. Hitung sisa maksimal yang bisa diretur
                                            $sisaBisaDiretur = $record->qty - ($totalTeretur ?? 0);
                                            return $sisaBisaDiretur;
                                        }
                                    ) // Validasi tidak boleh lebih dari beli
                                    ->minValue(1)
                                    ->required()
                                    ->reactive()
                                    // ->afterStateUpdated(function ($state) use ($record) {
                                    //     $this->resetErrorBag('qty_retur');
                                    //     $returnIds = ReturnPenjualan::where('no_nota', $this->data['nomor_nota'])->pluck('id');

                                    //     // 2. Hitung total qty yang sudah diretur untuk barang ini secara spesifik
                                    //     $totalTeretur = (int) ReturnPenjualanDetail::whereIn('id_return', $returnIds)
                                    //         ->where('id_barang', $record->barang_id) // Sesuaikan: barang_id sesuai Model Anda
                                    //         ->sum('qty'); // Langsung sum lebih aman daripada get()->first()


                                    //     if ($state == 0) {
                                    //         $this->addError('data.qty_retur', 'Jumlah retur tidak boleh nol.');
                                    //     } else if ($state > ($record->qty - $totalTeretur - $state)) {
                                    //         $this->addError('data.qty_retur', 'Jumlah retur tidak boleh melebihi jumlah beli yang tersisa.');
                                    //     }
                                    // })

                                    ->hint(function ($state) use ($record) {
                                        $returnIds = ReturnPenjualan::where('no_nota', $this->data['nomor_nota'])->pluck('id');
                                        $totalTeretur = (int) ReturnPenjualanDetail::whereIn('id_return', $returnIds)
                                            ->where('id_barang', $record->barang_id)
                                            ->sum('qty');

                                        // dd($state, $record->barang_id, $record->qty, $totalTeretur, $returnIds->toArray());
                            
                                        $sisaBisaDiretur = $record->qty - ($totalTeretur ?? 0) - ($state ?? 0);
                                        return "Sisa bisa diretur: {$sisaBisaDiretur} {$record->satuan}";
                                    }),
                                Textarea::make('keterangan_retur')
                                    ->label('Alasan Retur (Reason)')
                                    ->placeholder('Contoh: Barang cacat produksi / expired')
                                    ->maxLength(255)
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
                            barang_id: $record->barang_id,
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
        'hapus-dari-keranjang-parent' => 'handleBarangDihapus',
        'proses-submit-final' => 'submitRetur' // Menghubungkan event ke method submitRetur
    ];
    public function handleBarangDihapus($id)
    {
        if (isset($this->barangReturSementaras[$id])) {
            unset($this->barangReturSementaras[$id]);
        }
    }
    public function booted()
    {
        // Memastikan setiap request Livewire tahu record mana yang dipakai Schema
        if ($this->penjualanTerpilih) {
            $this->getSchema('infoNota')->record($this->penjualanTerpilih);
        }
    }

    public function resetKeranjangOnly()
    {
        $this->barangReturSementaras = []; // Menghapus tanda 'disabled' di tabel atas
        $this->dispatch('reset-keranjang'); // Mengosongkan tabel bawah
        Notification::make()->title('Keranjang dikosongkan')->info()->send();
    }
    public function submitRetur($keranjangItems)
    {
        try {
            if (empty($keranjangItems)) {
                Notification::make()->title('Keranjang Kosong')->danger()->send();
                return;
            }

            Notification::make()->title('Proses Menyimpan Retur')
            ->body('Sedang menyimpan data retur, mohon tunggu sebentar...')
            ->warning()
            ->send();



            DB::transaction(function () use ($keranjangItems) {
                $returnHeader = ReturnPenjualan::create([
                    // 'penjualan_id' => $this->penjualanTerpilih->id,
                    'no_nota' => $this->penjualanTerpilih->no_nota,
                    'nama_customer' => $this->penjualanTerpilih->nama_customer ?? 'Tidak Diketahui',
                    'tanggal' => now(),
                    'is_member' => $this->penjualanTerpilih->is_member ?? false,
                    'alamat' => $this->penjualanTerpilih->alamat ?? null,
                    'metode_pembayaran' => $this->penjualanTerpilih->metode_pembayaran ?? 'TUNAI',
                    'bank' => $this->penjualanTerpilih->bank ?? null,
                    'no_rekening' => $this->penjualanTerpilih->no_rekening ?? null,
                    'kendaraan' => $this->penjualanTerpilih->kendaraan ?? null,
                    'plat_kendaraan' => $this->penjualanTerpilih->plat_kendaraan ?? null,
                    'nama_sopir' => $this->penjualanTerpilih->nama_sopir ?? null,
                    'total' => collect($keranjangItems)->sum(fn($item) => $item['harga_jual'] * $item['qty']),
                    'bayar' => collect($keranjangItems)->sum(fn($item) => $item['harga_jual'] * $item['qty']),
                    'kembalian' => 0,
                    'created_by' => auth()->id(),
                    'validate_by' => null,
                    'status_return' => 'DIPROSES',
                ]);

                foreach ($keranjangItems as $item) {
                    ReturnPenjualanDetail::create([
                        'id_return' => $returnHeader->id,
                        'id_barang' => $item['barang_id'] ?? null,
                        'nama_barang' => $item['nama_barang'],
                        'satuan' => $item['satuan'],
                        'harga_awal' => $item['harga_awal'] ?? 0,
                        'harga_jual' => $item['harga_jual'],
                        'potongan' => $item['potongan'] ?? 0,
                        'qty' => $item['qty'],
                        'subtotal' => $item['harga_jual'] * $item['qty'],
                        'keterangan' => $item['keterangan'],
                    ]);
                }
            });
            Notification::make()->title('Retur Berhasil Disimpan')
            ->body("Retur untuk nota {$this->penjualanTerpilih->no_nota} berhasil disimpan.")
            ->success()->send();
            return redirect()->to(ReturnPenjualanResource::getUrl('index'));
        } catch (\Throwable $th) {
            // dd($th);
            Notification::make()->title('Retur Gagal Disimpan')->
                body("Terjadi kesalahan saat menyimpan retur. Silahkan Hubungi Tim  IT")->
                danger()->send();
            // return redirect()->to(ReturnPenjualanResource::getUrl('index'));
            //throw $th;
        }

    }

    public function resetNota()
    {
        $this->data['nomor_nota'] = '';
        $this->penjualanTerpilih = null;
        $this->barangReturSementaras = [];
        $this->dispatch('reset-keranjang');
        $this->dispatch('scroll-to-top');
    }

    public function footerActions(Schema $schema): Schema
    {
        return $schema
            ->components([
                Actions::make([
                    Action::make('kembali')
                        ->label('Kembali')
                        ->color('gray')
                        ->icon('heroicon-m-arrow-left')
                        ->requiresConfirmation()
                        ->url(fn() => ReturnPenjualanResource::getUrl('index')),

                    Action::make('resetNota')
                        ->label('Reset Nota')
                        ->color('danger')
                        ->icon('heroicon-m-no-symbol')
                        ->outlined()
                        ->requiresConfirmation()
                        ->action(fn() => $this->resetNota()),

                    Action::make('resetKeranjang')
                        ->label('Reset Keranjang')
                        ->color('warning')
                        ->icon('heroicon-m-trash')
                        ->outlined()
                        ->requiresConfirmation()
                        ->action(fn() => $this->resetKeranjangOnly()),

                    Action::make('submitRetur')
                        ->label('Simpan Return Penjualan')
                        ->color('success')
                        ->icon('heroicon-m-check-circle')
                        ->requiresConfirmation()
                        ->action(fn() => $this->dispatch('trigger-submit-pengembalian')->to('temporary-return-cart')),
                ])
                    ->extraAttributes([
                        'class' => 'flex flex-wrap items-center justify-end gap-3 w-full',
                    ])
            ]);
    }
}

