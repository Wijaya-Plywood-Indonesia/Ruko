<?php

namespace App\Filament\Resources\Pembelians\Schemas;

use App\Models\Pembelian;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PembeliansForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pembelian')
                    ->schema([
                        TextInput::make('nomor_nota')
                            ->label('Nomor Nota')
                            ->required()
                            ->maxLength(255),
                        Select::make('created_by')
                            ->label('Dibuat Oleh')
                            ->relationship('createdBy', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->default(auth()->id()),

                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required(),

                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'nama')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $supplier = Supplier::find($state);

                                if (!$supplier) {
                                    return;
                                }

                                $set('supplier_name', $supplier->nama);
                                $set('supplier_phone', $supplier->telepon);
                                $set('supplier_address', $supplier->alamat);
                                $set('supplier_npwp', $supplier->npwp);
                            }),

                        TextInput::make('supplier_name')
                            ->label('Nama Supplier')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('supplier_phone')
                            ->label('Telepon Supplier')
                            ->disabled()
                            ->dehydrated(),

                        Textarea::make('supplier_address')
                            ->label('Alamat Supplier')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),

                        TextInput::make('supplier_npwp')
                            ->label('NPWP Supplier')
                            ->disabled()
                            ->dehydrated(),

                        Select::make('status')
                            ->label('Status')
                            ->options(Pembelian::labelStatus())
                            ->required()
                            ->default(Pembelian::STATUS_DRAFT),

                        FileUpload::make('foto')
                            ->label('Foto Nota')
                            ->multiple()
                            ->image()
                            ->directory('pembelian'),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Nominal')
                    ->schema([
                        TextInput::make('sub_total')
                            ->label('Sub Total')
                            ->numeric()
                            ->default(0)
                            ->live(),

                        TextInput::make('total_diskon')
                            ->label('Total Diskon')
                            ->numeric()
                            ->default(0)
                            ->live(),

                        TextInput::make('total_ppn')
                            ->label('Total PPN')
                            ->numeric()
                            ->default(0)
                            ->live(),

                        TextInput::make('ongkir')
                            ->label('Ongkir')
                            ->numeric()
                            ->default(0)
                            ->live(),

                        TextInput::make('biaya_lain')
                            ->label('Biaya Lain')
                            ->numeric()
                            ->default(0)
                            ->live(),

                        TextInput::make('grand_total')
                            ->label('Grand Total')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->formatStateUsing(function (Get $get) {
                                return
                                    ((float) $get('sub_total'))
                                    - ((float) $get('total_diskon'))
                                    + ((float) $get('total_ppn'))
                                    + ((float) $get('ongkir'))
                                    + ((float) $get('biaya_lain'));
                            }),
                    ])
                    ->columns(3),
            ]);
    }
}
