<?php
namespace App\Filament\Pages;

use App\Models\Barang;
use App\Models\IdentitasToko;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Actions\Action;
use App\Services\StokPenyesuaianService;
use App\Models\StokBarangToko;
use UnitEnum;

class PenyesuaianStok extends Page implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationLabel = 'Penyesuaian Stok';
    //protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static string|UnitEnum|null $navigationGroup = 'Stock Barang';
    protected string $view = 'filament.pages.penyesuaian-stok';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('toko_id')
                ->label('Toko')
                ->options(
                    IdentitasToko::pluck('nama_toko', 'id')
                )
                ->required(),


            Select::make('barang_id')
                ->label('Barang')
                ->options(
                    Barang::pluck('nama_barang', 'id')
                )
                ->searchable()
                ->required(),

            TextInput::make('stok_sistem')
                ->disabled()
                ->dehydrated(false)
                ->reactive()
                ->afterStateHydrated(function ($set, $get) {
                    if ($get('barang_id') && $get('toko_id')) {
                        $stok = StokBarangToko::where(
                            'barang_id',
                            $get('barang_id')
                        )->where(
                                'toko_id',
                                $get('toko_id')
                            )->first();

                        $set('stok_sistem', $stok?->stok ?? 0);
                    }
                }),

            TextInput::make('stok_fisik')
                ->numeric()
                ->required()
                ->reactive(),

            Textarea::make('catatan')
                ->required(),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('simpan')
                ->label('Sesuaikan Stok')
                ->requiresConfirmation()
                ->action(function (array $data) {

                    // 🔥 INI PASTI MUNCUL
                    dd($data);

                }),
            // Action::make('simpan')
            //     ->label('Sesuaikan Stok')
            //     ->requiresConfirmation()
            //     ->action(function () {
            //         dd($this->form->getState());
            //         // ->action(function (StokPenyesuaianService $service) {

            //         //     $data = $this->form->getState();

            //         //     $service->sesuaikan(
            //         //         barangId: $data['barang_id'],
            //         //         tokoId: $data['toko_id'],
            //         //         stokFisik: (int) $data['stok_fisik'],
            //         //         userId: auth()->id(),
            //         //         catatan: $data['catatan'] ?? null,
            //         //     );

            //         //     $this->notify('success', 'Stok berhasil disesuaikan');
            //         //     $this->form->fill();
            //     }),
        ];
    }
}
