<?php

namespace App\Filament\Resources\Pembelians\Pages;

use App\Filament\Resources\Pembelians\PembeliansResource;
use App\Models\Pembelian;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPembelians extends ListRecords
{
    protected static string $resource = PembeliansResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Pembelian')
                ->visible(function () {
                    $user = filament()->auth()->user();

                    if ($user->hasRole('super_admin')) {
                        return true;
                    }

                    $adaNotaBelumValidasi = Pembelian::whereNull('validated_by')
                        ->where('status', '!=', Pembelian::STATUS_BATAL)
                        ->exists();
                    return !$adaNotaBelumValidasi;
                }),
        ];
    }
}
