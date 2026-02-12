<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Filament\Resources\Penjualans\PenjualanResource;
use App\Services\Penjualans\SyncPenjualanService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewPenjualan extends ViewRecord
{
    protected static string $resource = PenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sinkronkan_data')
                ->label('Sinkronkan Data Penjualan')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->modalWidth('lg')
                ->mountUsing(fn($form, $record) => $form->fill([
                    'total_sebelum' => $record->total,
                    'total_saat_ini' => SyncPenjualanService::calculateCurrentTotal($record->id),
                    // 'bayar' => 0,
                    // 'kembalian' => SyncPenjualanService::calculateKembalian($record->id, (float) $record->total),
                    'bayar' => $record->bayar,
                    'kembalian' => $record->bayar - SyncPenjualanService::calculateCurrentTotal($record->id),
                    // 'metode_bayar' => $record->metode_bayar ?? 'tunai',
                    'keterangan' => $record->keterangan,
                ]))
                ->form([
                    TextInput::make('total_sebelum')
                        ->label('Total Sebelum')
                        ->numeric()
                        ->prefix('Rp')
                        ->dehydrated()
                        ->live(onBlur: true)
                        ->disabled(),

                        

                    TextInput::make('total_saat_ini')
                        ->label('Total Saat Ini')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated()
                        ->live(onBlur: true),

                    TextInput::make('bayar')
                        ->label('Bayar')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->live(onBlur: true)
                        // ->disabled(fn($get) => (float) $get('total_sebelum') >= (float) $get('total_saat_ini'))
                        ->dehydrated()
                        ->rules([
                            fn($get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                $totalSaatIni = (float) $get('total_saat_ini');
                                $totalSebelum = (float) $get('total_sebelum');
                                $bayar = (float) $value;

                                if ($totalSebelum < $totalSaatIni && $bayar < $totalSaatIni) {
                                    $fail("Nominal pembayaran kurang. Minimal pembayaran adalah Rp " . number_format($totalSaatIni, 0, ',', '.'));
                                }
                            },
                        ])
                        ->afterStateUpdated(function ($state, $get, $set) {
                            $total_si = (float) $get('total_saat_ini');
                            $total_sb = (float) $get('total_sebelum');
                            $bayar = (float) $state;

                            // if ($total_sb > $total_si) {
                            //     $set('kembalian', $total_sb - $total_si);
                            // } else if ($total_si > $total_sb) {
                            //     $set('kembalian', $bayar >= $total_si ? $bayar - $total_si : 0);
                            // } else {
                                // $set('kembalian', 0);
                            // }
                                
                            $set('kembalian', $bayar - $total_si);

                        }),

                    TextInput::make('kembalian')
                        ->label('Kembalian')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated()
                        ->formatStateUsing(fn($state) => $state)
                        ->extraInputAttributes(['class' => 'text-xl font-bold text-success-600']),

                    // Select::make('metode_bayar')
                    //     ->label('Metode Bayar')
                    //     ->options([
                    //         'tunai' => 'Tunai',
                    //         'transfer' => 'Transfer',
                    //     ])
                    //     ->required(),
                        

                    TextInput::make('keterangan')
                        ->label('Keterangan'),
                ])
                ->action(function (array $data, $record) {
                    try {
                        
                        $data['total'] = $data['total_saat_ini'];
                        $data['bayar'] = (float) ($data['bayar'] ?? 0) > 0 ? $data['bayar'] + $record->bayar : $record->bayar;
                        $data['kembalian'] = (float) ($data['kembalian'] ?? 0) > 0 ? $data['kembalian'] + $record->kembalian : $record->kembalian;

                        SyncPenjualanService::syncPenjualan($record->id, $data);

                        Notification::make()
                            ->title('Data Berhasil Disinkronkan')
                            ->success()
                            ->send();
                        
                        return redirect(request()->header('Referer')); // Refresh halaman setelah sinkron
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Sinkronisasi')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}