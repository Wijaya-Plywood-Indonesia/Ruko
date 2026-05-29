<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Filament\Resources\Penjualans\PenjualanResource;
use App\Models\Penjualan;
use App\Services\Penjualans\SyncPenjualanService;
use App\Services\JurnalBalikService;
use App\Services\JurnalPenjualanTelurService;
use App\Services\StokPenyesuaianService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;

class ViewPenjualan extends ViewRecord
{
    protected static string $resource = PenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ✅ ACTION: VALIDASI TRANSAKSI
            Action::make('validasi_transaksi')
                ->label('Validasi Transaksi')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(
                    fn($record) => empty($record->validated_by)
                    && !in_array($record->status_transaksi, ['LUNAS', 'COD', 'DIBATALKAN'])
                )
                ->disabled(
                    fn($record) =>
                    $record->user_id === filament()->auth()->id()
                    && !filament()->auth()->user()->hasRole('super_admin')
                )
                ->modalHeading('Validasi Transaksi')
                ->modalSubmitActionLabel('Simpan Validasi')
                ->form([
                    TextInput::make('validator_name')
                        ->label('Validator')
                        ->default(fn() => filament()->auth()->user()->name)
                        ->disabled()
                        ->dehydrated(false),

                    Select::make('status_transaksi')
                        ->label('Status Transaksi')
                        ->options([
                            'LUNAS' => 'LUNAS',
                            'COD' => 'COD',
                            'PENDING' => 'PENDING',
                            'DIBATALKAN' => 'DIBATALKAN',
                        ])
                        ->required(),
                ])
                ->action(function ($record, array $data) {
                    if (
                        $record->user_id === filament()->auth()->id()
                        && !filament()->auth()->user()->hasRole('super_admin')
                    ) {
                        Notification::make()
                            ->title('Tidak boleh validasi transaksi sendiri')
                            ->danger()
                            ->send();
                        return;
                    }

                    if (!empty($record->validated_by)) {
                        Notification::make()
                            ->title('Transaksi sudah divalidasi')
                            ->warning()
                            ->send();
                        return;
                    }

                    $statusBaru  = $data['status_transaksi'];
                    $validatorId = filament()->auth()->id();

                    DB::transaction(function () use ($record, $statusBaru, $validatorId) {
                        if ($statusBaru === 'LUNAS') {
                            // Penyesuaian stok
                            app(StokPenyesuaianService::class)
                                ->lunas($record->id);

                            // Buat jurnal pembantu otomatis
                            app(JurnalPenjualanTelurService::class)
                                ->buatJurnalDariPenjualan($record, $validatorId);
                        }

                        $record->update([
                            'validated_by'     => $validatorId,
                            'status_transaksi' => $statusBaru,
                        ]);
                    });

                    Notification::make()
                        ->title('Transaksi berhasil divalidasi')
                        ->success()
                        ->send();
                }),

            // ❌ ACTION: BATAL VALIDASI (Termasuk Logika Auto-Post Jurnal Asli)
            Action::make('batal_validasi')
                ->label('Batal Validasi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(
                    fn($record) =>
                    !empty($record->validated_by)
                    && filament()->auth()->user()->hasRole('super_admin')
                )
                ->action(function ($record) {
                    if (empty($record->validated_by)) {
                        Notification::make()
                            ->title('Transaksi belum divalidasi')
                            ->warning()
                            ->send();
                        return;
                    }

                    $userId = filament()->auth()->id();
                    $pesanNotif = 'Validasi telah dibatalkan.';

                    DB::transaction(function () use ($record, $userId, &$pesanNotif) {
                        if ($record->status_transaksi === 'LUNAS') {
                            // 1. Balik stok
                            app(StokPenyesuaianService::class)
                                ->batalLunas($record->id);

                            // 2. Logika Penyelamatan Jurnal Asli Jika Masih Draft
                            $headersAsli = \App\Models\JurnalPembantuHeader::where('no_dokumen', $record->no_nota)
                                ->where('adalah_jurnal_balik', false)
                                ->where('modul_asal', 'penjualan_telur') // Sesuai modul service Anda
                                ->get();

                            $isMasihDraft = $headersAsli->contains(function ($header) {
                                return $header->status === \App\Models\JurnalPembantuHeader::STATUS_DRAFT;
                            });

                            if ($isMasihDraft) {
                                $nomorAsli  = (int) $headersAsli->first()?->jurnal;
                                $nomorFinal = $nomorAsli;

                                // Geser nomor jika ternyata sudah dipakai
                                if ($nomorAsli > 0 && \App\Models\JurnalUmum::where('jurnal', $nomorAsli)->exists()) {
                                    $nomorFinal = max(
                                        (int) (\App\Models\JurnalUmum::max('jurnal') ?? 0),
                                        (int) (\App\Models\JurnalPembantuHeader::max('jurnal') ?? 0)
                                    ) + 1;

                                    \App\Models\JurnalPembantuHeader::where('no_dokumen', $record->no_nota)
                                        ->where('adalah_jurnal_balik', false)
                                        ->where('modul_asal', 'penjualan_telur')
                                        ->update(['jurnal' => $nomorFinal]);
                                }

                                // Posting ke Jurnal Umum
                                foreach ($headersAsli as $header) {
                                    $itemsAktif = $header->items()->where('status', true)->get();
                                    $totalBanyak = $itemsAktif->sum('banyak');
                                    $totalJumlah = $itemsAktif->sum('jumlah');

                                    $banyak = $totalBanyak > 0 ? $totalBanyak : 1;
                                    $hargaRata = $totalBanyak > 0 ? $totalJumlah / $totalBanyak : $header->total_nilai;

                                    \App\Models\JurnalUmum::create([
                                        'tgl'        => now()->format('Y-m-d'),
                                        'jurnal'     => $nomorFinal,
                                        'no_akun'    => $header->no_akun,
                                        'nama_akun'  => $header->nama_akun,
                                        'nama'       => $record->nama_customer ?? 'Pelanggan',
                                        'keterangan' => $header->keterangan . ' (Otomatis Terposting karena Pembatalan)',
                                        'banyak'     => $banyak,
                                        'harga'      => round($hargaRata, 2),
                                        'map'        => strtolower($header->map),
                                    ]);
                                }

                                $infoNomor  = $nomorFinal !== $nomorAsli ? " (Nomor Jurnal disesuaikan menjadi No. {$nomorFinal} karena No. {$nomorAsli} sudah terpakai)" : "";
                                $pesanNotif = "Jurnal Asli otomatis di-posting ke Jurnal Umum{$infoNomor}, dan ";
                            } else {
                                $pesanNotif = '';
                            }

                            // 3. Buat jurnal balik otomatis
                            app(JurnalBalikService::class)
                                ->buatJurnalBalikDariNota($record->no_nota, $userId);
                            
                            $pesanNotif .= 'Jurnal Balik Baru berhasil diterbitkan di Jurnal Pembantu.';
                        }

                        // 4. Update status record penjualan
                        $record->update([
                            'validated_by'     => null,
                            'status_transaksi' => 'BELUM DIBAYAR',
                        ]);
                    });

                    Notification::make()
                        ->title('Batal Validasi Berhasil')
                        ->body($pesanNotif)
                        ->warning()
                        ->send();
                }),

            Action::make('sinkronkan_data')
                ->label('Sinkronkan Data Penjualan')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->modalWidth('lg')
                ->mountUsing(fn($form, $record) => $form->fill([
                    'total_sebelum' => $record->total,
                    'total_saat_ini' => SyncPenjualanService::calculateCurrentTotal($record->id),
                    'bayar' => $record->bayar,
                    'kembalian' => $record->bayar - SyncPenjualanService::calculateCurrentTotal($record->id),
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
                            $bayar = (float) $state;
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
                        
                        return redirect(request()->header('Referer'));
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