<?php

namespace App\Filament\Resources\SuratJalans\Pages;

use App\Filament\Resources\SuratJalans\SuratJalanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSuratJalan extends CreateRecord
{
    protected static string $resource = SuratJalanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Hapus no_surat_jalan dari form data agar boot() di model
        // yang generate — dijamin unik pakai timestamp mikrodetik
        unset($data['no_surat_jalan']);

        return $data;
    }
}