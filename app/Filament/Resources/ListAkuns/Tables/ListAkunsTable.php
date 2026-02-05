<?php

namespace App\Filament\Resources\ListAkuns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

use Filament\Actions\Action;
use App\Filament\Resources\Pegawais\PegawaiResource;


use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;


class ListAkunsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pegawai.nama_lengkap')
                    ->label('Nama Pegawai')
                    ->searchable(),

                TextColumn::make('akun.email')
                    ->label('Akun')
                    ->searchable(),

                BadgeColumn::make('akun.roles.name')
                    ->label('Jabatan')
                    ->colors([
                        'primary',
                        'success' => 'super_admin',
                        'warning' => 'kasir',
                    ]),

                TextColumn::make('toko.nama_toko')
                    ->label('Penempatan Toko'),
            ])
            ->filters([
                //
            ])
            ->recordActions([

                Action::make('detailPegawai')
                    ->color('info')
                    ->label('Detail Pegawai')
                    ->icon('heroicon-o-user')
                    ->action(
                        fn($record) =>
                        redirect(
                            PegawaiResource::getUrl('view', [
                                'record' => $record->id_pegawai,
                            ])
                        )
                    )
                ,

                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
