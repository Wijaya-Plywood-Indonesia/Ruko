<?php

namespace App\Filament\Resources\Pegawais\Schemas;
use App\Models\IdentitasToko;
use Spatie\Permission\Models\Role;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PegawaiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //

                // =============================
                //  IDENTITAS PEGAWAI
                // =============================
                Section::make('Identitas Pegawai')
                    ->description('Isi data dasar pegawai dengan lengkap.')
                    ->schema([
                        // Select::make('id_toko')
                        //     ->label('Toko')
                        //     ->options(IdentitasToko::pluck('nama_toko', 'id'))
                        //     ->required()
                        //     ->dehydrated(true) // ubah ke true!
                        //     ->afterStateHydrated(null), // biar tidak masuk model
                        Select::make('id_toko')
                            ->label('Toko')
                            ->options(IdentitasToko::pluck('nama_toko', 'id'))
                            ->required()
                            ->dehydrateStateUsing(fn($state) => $state)  // paksa dikembalikan ke $data
                            ->dehydrated(),

                        TextInput::make('nik')
                            ->label('NIK')
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                        ,

                        TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('nama_panggilan')
                            ->label('Nama Panggilan')
                            //->required()
                            ->maxLength(100),

                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->required()
                            ->default('L')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->required(),

                        DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->closeOnDateSelection(),

                        DatePicker::make('tanggal_masuk')
                            ->label('Tanggal Masuk')
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->closeOnDateSelection(),
                    ])
                    ->columns(2),

                // =============================
                //  KONTAK & ALAMAT
                // =============================
                Section::make('Kontak & Alamat')
                    ->schema([
                        TextInput::make('telepon')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(15),

                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('opsional'),

                        Textarea::make('alamat')
                            ->label('Alamat Lengkap')
                            ->rows(3),
                    ])
                    ->columns(2),

                // =============================
                //  DOKUMEN (FOTO)
                // =============================
                Section::make('Dokumen')
                    ->description('Upload foto pegawai dan foto KTP.')
                    ->schema([
                        FileUpload::make('foto_pegawai')
                            ->label('Foto Pegawai')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('pegawai/foto')
                            ->openable()
                            ->downloadable()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('600')
                            ->imageResizeTargetHeight('600')
                            ->columnSpanFull(),

                        FileUpload::make('foto_ktp')
                            ->label('Foto KTP')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('pegawai/ktp')
                            ->openable()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('600')
                            ->imageResizeTargetHeight('600')
                            ->downloadable()
                            ->columnSpanFull(),

                        Select::make('status')
                            ->label('Status Pegawai')
                            ->options([
                                'AKTIF' => 'Aktif',
                                'NONAKTIF' => 'Nonaktif',
                            ])
                            ->default('AKTIF'),
                    ])
                    ->columns(2),
                Section::make('Akun Login Sistem')
                    ->description('Opsional. Aktifkan jika pegawai membutuhkan akses ke sistem.')
                    ->schema([

                        Toggle::make('buat_akun')
                            ->label('Buat Akun Login')
                            ->live(),

                        TextInput::make('akun_username')
                            ->label('Username Login')
                            ->maxLength(100)
                            ->visible(fn($get) => $get('buat_akun'))
                            ->required(fn($get) => $get('buat_akun')),

                        Select::make('akun_role')
                            ->label('Role Akses')
                            ->options(fn() => Role::pluck('name', 'name'))
                            ->searchable()
                            ->preload()
                            ->visible(fn($get) => $get('buat_akun'))
                            ->required(fn($get) => $get('buat_akun')),

                    ])
                    ->columns(2),

            ]);
    }
}
