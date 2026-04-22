<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use App\Models\IdentitasToko;
use App\Models\StokBarangToko;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use UnitEnum;

class PenyesuaianStok extends Page implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationLabel = 'Penyesuaian Stok';
    protected static string|UnitEnum|null $navigationGroup = 'Stock Barang';
    protected string $view = 'filament.pages.penyesuaian-stok';

    public ?array $data = [];

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    public function mount(): void
    {
        $toko = IdentitasToko::first();
        $barang = Barang::first();

        $this->form->fill([
            'toko_id' => $toko?->id,
            'barang_id' => $barang?->id,
        ]);

        $this->data['stok_sistem'] = $this->ambilStok(
            $barang?->id,
            $toko?->id
        );

        $this->data['selisih'] = 0;
    }

    private function ambilStok($barangId, $tokoId)
    {
        if (!$barangId || !$tokoId) {
            return 0;
        }

        return StokBarangToko::where('barang_id', $barangId)
            ->where('toko_id', $tokoId)
            ->value('stok') ?? 0;
    }

    protected function getFormSchema(): array
    {
        return [

            Select::make('toko_id')
                ->label('Toko')
                ->options(IdentitasToko::pluck('nama_toko', 'id'))
                ->reactive()
                ->afterStateUpdated(function ($set, $get) {

                    $stok = $this->ambilStok(
                        $get('barang_id'),
                        $get('toko_id')
                    );

                    $set('stok_sistem', $stok);

                    $set(
                        'selisih',
                        ($get('stok_fisik') ?? 0) - $stok
                    );
                })
                ->required(),

            Select::make('barang_id')
                ->label('Barang')
                ->options(Barang::pluck('nama_barang', 'id'))
                ->searchable()
                ->reactive()
                ->afterStateUpdated(function ($set, $get) {

                    $stok = $this->ambilStok(
                        $get('barang_id'),
                        $get('toko_id')
                    );

                    $set('stok_sistem', $stok);

                    $set(
                        'selisih',
                        ($get('stok_fisik') ?? 0) - $stok
                    );
                })
                ->required(),

            TextInput::make('stok_sistem')
                ->label('Stok Sistem')
                ->disabled()
                ->dehydrated(false),

            TextInput::make('stok_fisik')
                ->label('Stok Fisik')
                ->numeric()
                ->reactive()
                ->afterStateUpdated(function ($set, $get) {

                    $set(
                        'selisih',
                        ($get('stok_fisik') ?? 0) - ($get('stok_sistem') ?? 0)
                    );
                })
                ->required(),

            TextInput::make('selisih')
                ->label('Selisih')
                ->disabled()
                ->dehydrated(false),

            Textarea::make('catatan')
                ->label('Catatan Tambahan')
            ,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('simpan')
                ->label('Sesuaikan Stok')
                ->requiresConfirmation()
                ->action(function (array $data) {
                    dd($data);
                }),
        ];
    }
}