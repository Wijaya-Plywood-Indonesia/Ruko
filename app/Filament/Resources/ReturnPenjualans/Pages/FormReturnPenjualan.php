<?php

namespace App\Filament\Resources\ReturnPenjualans\Pages;

use App\Filament\Resources\ReturnPenjualans\ReturnPenjualanResource;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists; // Tambahkan ini
use Filament\Infolists\Contracts\HasInfolists; // Tambahkan ini
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\FontWeight;

// use Filament\Schemas\Infolist;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;


// use Filament\Schemas\Schema;
// use Filament\Schemas\Components\Section;
// use Filament\Infolists\Components\TextEntry;

class FormReturnPenjualan extends Page implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists; // Gunakan trait ini

    protected static string $resource = ReturnPenjualanResource::class;
    protected string $view = 'filament.resources.return-penjualans.pages.form-return-penjualan';

    public ?array $data = [];
    public ?array $dataDetails = [];
    public $penjualanTerpilih = null;

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
                            ->components([
                                // --- SUB SECTION 2: PEMBAYARAN ---
                                Section::make('Pembayaran')
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
                            ->collapsed() // Tetap bisa di-collapse meskipun di dalam section
                            ->components([
                                TextEntry::make('user.name')->label('Kasir'),
                                TextEntry::make('validator.name')->label('Validasi')->placeholder('Belum Divalidasi'),
                                TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                                TextEntry::make('updated_at')->label('Diubah')->dateTime('d M Y H:i'),
                            ]),
                    ]),
            ]);
    }

    public function pilihNota($nota)
    {
        if (!$nota) {
            $this->penjualanTerpilih = null;
            return;
        }

        $penjualan = Penjualan::where('no_nota', $nota)->first();

        if ($penjualan) {
            $this->penjualanTerpilih = $penjualan;
            $this->dataDetails = DetailPenjualan::where('penjualan_id', $penjualan->id)->get()->toArray();
        } else {
            $this->penjualanTerpilih = null;
        }
    }
}