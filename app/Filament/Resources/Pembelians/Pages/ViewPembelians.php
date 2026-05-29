<?php

namespace App\Filament\Resources\Pembelians\Pages;

use App\Filament\Resources\Pembelians\PembeliansResource;
use App\Models\Pembelian;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use App\Services\JurnalPembelianService;
use App\Services\JurnalBalikService;

class ViewPembelians extends ViewRecord
{
    protected static string $resource = PembeliansResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ✅ ACTION: VALIDASI PEMBELIAN (Dipindah ke View)
            Action::make('validasi_pembelian')
                ->label('Validasi')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(Pembelian $record) => empty($record->validated_by) && $record->status !== Pembelian::STATUS_BATAL)
                ->disabled(fn(Pembelian $record) => $record->created_by === filament()->auth()->id() && !filament()->auth()->user()->hasRole('super_admin'))
                ->form([
                    TextInput::make('validator_name')
                        ->label('Petugas Validasi')
                        ->default(fn() => filament()->auth()->user()->name)
                        ->disabled()
                        ->dehydrated(false),

                    Select::make('status')
                        ->label('Update Status Pembelian')
                        ->options(Pembelian::labelStatus())
                        ->required()
                        ->disableOptionWhen(fn(string $value): bool => $value === Pembelian::STATUS_DRAFT),
                ])
                ->action(function (Pembelian $record, array $data) {
                    $validatorId = filament()->auth()->id();

                    DB::transaction(function () use ($record, $data, $validatorId) {
                        $record->update([
                            'validated_by' => $validatorId,
                            'status'       => $data['status'],
                            'tanggal_validasi' => now(),
                        ]);

                        app(JurnalPembelianService::class)
                            ->buatJurnalDariPembelian($record, $validatorId);
                    });

                    Notification::make()
                        ->title('Pembelian Berhasil Divalidasi & Jurnal Tercatat')
                        ->success()
                        ->send();
                }),

            // ❌ ACTION: BATAL VALIDASI (Dipindah ke View)
            Action::make('batal_validasi')
                ->label('Batal Validasi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(Pembelian $record) => !empty($record->validated_by) && filament()->auth()->user()->hasRole('super_admin'))
                ->action(function (Pembelian $record) {
                    $userId = filament()->auth()->id();
                    $pesanNotif = 'Validasi telah dibatalkan.';

                    DB::transaction(function () use ($record, $userId, &$pesanNotif) {
                        $headersAsli = \App\Models\JurnalPembantuHeader::where('no_dokumen', $record->nomor_nota)
                            ->where('adalah_jurnal_balik', false)
                            ->where('modul_asal', 'pembelian_barang')
                            ->get();

                        $isMasihDraft = $headersAsli->contains(function ($header) {
                            return $header->status === \App\Models\JurnalPembantuHeader::STATUS_DRAFT;
                        });

                        if ($isMasihDraft) {
                            $nomorAsli  = (int) $headersAsli->first()?->jurnal;
                            $nomorFinal = $nomorAsli;

                            if ($nomorAsli > 0 && \App\Models\JurnalUmum::where('jurnal', $nomorAsli)->exists()) {
                                $nomorFinal = max(
                                    (int) (\App\Models\JurnalUmum::max('jurnal') ?? 0),
                                    (int) (\App\Models\JurnalPembantuHeader::max('jurnal') ?? 0)
                                ) + 1;

                                \App\Models\JurnalPembantuHeader::where('no_dokumen', $record->nomor_nota)
                                    ->where('adalah_jurnal_balik', false)
                                    ->where('modul_asal', 'pembelian_barang')
                                    ->update(['jurnal' => $nomorFinal]);
                            }

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
                                    'nama'       => $record->supplier_name ?? $header->no_dokumen,
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

                        app(JurnalBalikService::class)
                            ->buatJurnalBalikDariNota($record->nomor_nota, $userId);

                        $pesanNotif .= 'Jurnal Balik Baru berhasil diterbitkan di Jurnal Pembantu.';

                        $record->update([
                            'validated_by' => null,
                            'status'       => Pembelian::STATUS_DRAFT,
                        ]);
                    });

                    Notification::make()
                        ->title('Batal Validasi Berhasil')
                        ->body($pesanNotif)
                        ->warning()
                        ->send();
                }),

            EditAction::make(),
        ];
    }
}