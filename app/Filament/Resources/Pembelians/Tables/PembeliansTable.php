<?php

namespace App\Filament\Resources\Pembelians\Tables;

use App\Models\Pembelian;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Services\JurnalPembelianService;
use App\Services\JurnalBalikService;

class PembeliansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_nota')
                    ->label('Nomor Nota')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('supplier_name')
                    ->label('Supplier')
                    ->searchable(),

                TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->formatStateUsing(function ($state) {
                        return 'Rp ' . number_format($state, 0, ',', '.');
                    })
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                // Status menggunakan logic badge seperti POS
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function ($record, $state) {
                        // Jika belum divalidasi, tampilkan 'Belum Diproses' (Draft)
                        if (empty($record->validated_by)) {
                            return Pembelian::labelStatus()[Pembelian::STATUS_DRAFT] ?? 'Belum Diproses';
                        }

                        // Jika sudah divalidasi, ambil label sesuai state dari model
                        return Pembelian::labelStatus()[$state] ?? $state;
                    })
                    ->color(function ($record, $state) {
                        // Jika belum divalidasi, beri warna abu-abu
                        if (empty($record->validated_by)) {
                            return 'gray';
                        }

                        // Mapping warna berdasarkan konstanta model
                        return match ($state) {
                            Pembelian::STATUS_LUNAS => 'success',
                            Pembelian::STATUS_CICILAN => 'warning',
                            Pembelian::STATUS_HUTANG => 'danger',
                            Pembelian::STATUS_BATAL => 'danger',
                            Pembelian::STATUS_DRAFT => 'gray',
                            default => 'secondary',
                        };
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('createdBy.name')
                    ->label('Admin/Purchasing')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('validatedBy.name')
                    ->label('Validator')
                    ->placeholder('Belum Validasi')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                // ✅ ACTION: VALIDASI PEMBELIAN
                Action::make('validasi_pembelian')
                    ->label('Validasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn($record) => empty($record->validated_by) && $record->status !== Pembelian::STATUS_BATAL)
                    ->disabled(fn($record) => $record->created_by === filament()->auth()->id() && !filament()->auth()->user()->hasRole('super_admin'))
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
                    ->action(function ($record, array $data) {
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

                // ❌ ACTION: BATAL VALIDASI
                Action::make('batal_validasi')
                    ->label('Batal Validasi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn($record) => !empty($record->validated_by) && filament()->auth()->user()->hasRole('super_admin'))
                    ->action(function ($record) {
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

                ViewAction::make(),
                EditAction::make()
                    ->visible(function ($record) {
                        $user = filament()->auth()->user();

                        // Jika dia super_admin, tombol EDIT selalu muncul
                        if ($user->hasRole('super_admin')) {
                            return true;
                        }

                        // Jika bukan super_admin, hanya muncul jika BELUM divalidasi
                        return empty($record->validated_by);
                    }),

                DeleteAction::make()
                    ->visible(function ($record) {
                        $user = filament()->auth()->user();
                        // Super Admin selalu bisa lihat tombol, Staff biasa hanya bisa lihat sebelum validasi
                        if ($user->hasRole('super_admin')) {
                            return true;
                        }
                        return empty($record->validated_by);
                    })
                    ->requiresConfirmation()
                    ->action(function ($record, DeleteAction $action) {

                        $adaDetailBarang = $record->detailPembelians()->exists();
                        $adaRiwayatBayar = $record->metodePembayarans()->exists();

                        if ($adaDetailBarang || $adaRiwayatBayar) {
                            $alasan = $adaRiwayatBayar
                                ? 'Sudah terdapat data riwayat pembayaran/DP yang terikat.'
                                : 'Masih terdapat rincian detail barang di dalam keranjang nota.';

                            Notification::make()
                                ->danger()
                                ->title('Data Gagal Dihapus!')
                                ->body("Nota {$record->nomor_nota} tidak dapat dihapus karena: {$alasan} Silakan hapus data relasi terlebih dahulu.")
                                ->persistent()
                                ->duration(3000)
                                ->send();

                            // 🛑 GANTI return; MENJADI INI:
                            // Ini akan memaksa Filament berhenti total dan mematikan notifikasi "Deleted"
                            $action->halt();
                        }

                        // Jika lolos pemeriksaan, baru benar-benar dihapus
                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title('Berhasil Dihapus')
                            ->body("Data pembelian Nota {$record->nomor_nota} telah bersih dihapus dari sistem.")
                            ->duration(3000)
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => filament()->auth()->user()->hasRole('super_admin'))
                        ->successNotification(fn() => null)
                        ->successNotificationTitle(fn() => null)
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $gagalDihapus = [];
                            $berhasilDihapusCount = 0;

                            // Looping setiap data nota pembelian yang dicentang oleh user
                            foreach ($records as $record) {
                                // Cek data anak / relasi manajer
                                $adaDetailBarang = $record->detailPembelians()->exists();
                                $adaRiwayatBayar = $record->metodePembayarans()->exists();

                                if ($adaDetailBarang || $adaRiwayatBayar) {
                                    // Jika ada data anak, masukkan nomor nota ke daftar gagal
                                    $gagalDihapus[] = $record->nomor_nota;
                                } else {
                                    // Jika aman, eksekusi hapus
                                    $record->delete();
                                    $berhasilDihapusCount++;
                                }
                            }

                            // ─── KONDISI 1: JIKA ADA DATA YANG GAGAL DIHAPUS ───
                            if (count($gagalDihapus) > 0) {
                                $daftarNota = implode(', ', $gagalDihapus);

                                Notification::make()
                                    ->danger()
                                    ->title('Beberapa Data Gagal Dihapus!')
                                    ->body("Gagal menghapus nota: **{$daftarNota}** karena masih memiliki detail barang atau riwayat pembayaran yang terikat.")
                                    ->duration(3000)
                                    ->send();
                            }

                            if ($berhasilDihapusCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Hapus Massal Berhasil')
                                    ->body("Sebanyak {$berhasilDihapusCount} data pembelian yang aman telah berhasil dihapus dari sistem.")
                                    ->duration(3000)
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }
}
