<?php

namespace App\Filament\Resources\Pegawais\Pages;

use App\Filament\Resources\Pegawais\PegawaiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPegawai extends EditRecord
{
    protected static string $resource = PegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        unset(
            $data['buat_akun'],
            $data['akun_username'],
            $data['akun_role'],
            $data['id_toko'],
        );

        $record->update($data);

        return $record;
    }
}
