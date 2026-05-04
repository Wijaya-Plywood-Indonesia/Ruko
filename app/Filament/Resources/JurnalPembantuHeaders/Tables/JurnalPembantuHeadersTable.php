<?php

namespace App\Filament\Resources\JurnalPembantuHeaders\Tables;

use App\Models\JurnalPembantuHeader;
use App\Models\JurnalUmum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class JurnalPembantuHeadersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_jurnal_pembantu')
                    ->label('No. JP')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('jurnal')
                    ->label('No. Jurnal')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('tgl_transaksi')
                    ->label('Tgl. Transaksi')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('jenis_transaksi')
                    ->label('Jenis')
                    ->formatStateUsing(fn($state) => JurnalPembantuHeader::JENIS[$state] ?? $state)
                    ->sortable(),

                TextColumn::make('no_akun')
                    ->label('Akun')
                    ->searchable(),

                TextColumn::make('nama_akun')
                    ->label('Nama Akun')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('keterangan')
                    ->limit(100),

                TextColumn::make('map')
                    ->label('D/K')
                    ->badge()
                    ->color(fn($state) => $state === 'd' ? 'info' : 'warning')
                    ->formatStateUsing(fn($state) => strtoupper($state)),

                TextColumn::make('total_nilai')
                    ->label('Total Nilai')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'draft'      => 'gray',
                        'diposting'  => 'success',
                        'dibalik'    => 'warning',
                        'dibatalkan' => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn($state) => JurnalPembantuHeader::STATUSES[$state] ?? $state),

                TextColumn::make('no_dokumen')
                    ->label('No. Dokumen')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('dibuatOleh.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(JurnalPembantuHeader::STATUSES),

                SelectFilter::make('jenis_transaksi')
                    ->label('Jenis Transaksi')
                    ->options(JurnalPembantuHeader::JENIS),

                SelectFilter::make('map')
                    ->label('Posisi D/K')
                    ->options(JurnalPembantuHeader::MAP),

                Filter::make('tgl_transaksi')
                    ->form([
                        DatePicker::make('dari')->label('Dari Tanggal'),
                        DatePicker::make('sampai')->label('Sampai Tanggal'),
                    ])
                    ->query(
                        fn(Builder $query, array $data): Builder => $query
                            ->when($data['dari'],   fn($q, $v) => $q->whereDate('tgl_transaksi', '>=', $v))
                            ->when($data['sampai'], fn($q, $v) => $q->whereDate('tgl_transaksi', '<=', $v))
                    ),
            ])

            ->actions([
                ViewAction::make(),

                EditAction::make()
                    ->visible(fn($record) => $record->isDraft()),

                // ── ACTION: Posting ke Jurnal Umum ────────────────────
                // Tombol hanya muncul di baris PERTAMA (id terkecil) dalam grup jurnal yang sama
                Action::make('posting')
                    ->label(fn($record) => "Posting Jurnal No. {$record->jurnal}")
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->visible(function ($record) {
                        if (!$record->isDraft()) return false;
                        // Hanya tampilkan di baris pertama (id terkecil) per nomor jurnal
                        $idPertama = JurnalPembantuHeader::where('jurnal', $record->jurnal)
                            ->where('status', JurnalPembantuHeader::STATUS_DRAFT)
                            ->min('id');
                        return $record->id === $idPertama;
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn($record) => "Posting Jurnal No. {$record->jurnal} ke Jurnal Umum")
                    ->modalDescription(function ($record) {
                        $headers = JurnalPembantuHeader::where('jurnal', $record->jurnal)->get();
                        $totalD  = $headers->where('map', 'd')->sum('total_nilai');
                        $totalK  = $headers->where('map', 'k')->sum('total_nilai');
                        $count   = $headers->count();
                        $balance = abs($totalD - $totalK) < 0.0001 ? '✓ Balance' : '✗ TIDAK BALANCE';

                        return "Jurnal ini memiliki {$count} baris. "
                            . "Debit: Rp " . number_format($totalD, 0, ',', '.') . " | "
                            . "Kredit: Rp " . number_format($totalK, 0, ',', '.') . ". "
                            . "Status: {$balance}.";
                    })
                    ->modalSubmitActionLabel('Ya, Posting Sekarang')
                    ->action(function ($record) {

                        // 1. Cek balance seluruh jurnal se-nomor
                        if (!$record->isBalanced()) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak Balance!')
                                ->body('Total Debet ≠ Total Kredit untuk Jurnal No. ' . $record->jurnal . '. Posting dibatalkan.')
                                ->send();
                            return;
                        }

                        // 2. Pastikan semua baris se-jurnal masih draft
                        $adaYangBukanDraft = JurnalPembantuHeader::where('jurnal', $record->jurnal)
                            ->where('status', '!=', JurnalPembantuHeader::STATUS_DRAFT)
                            ->exists();

                        if ($adaYangBukanDraft) {
                            Notification::make()
                                ->danger()
                                ->title('Jurnal Sudah Diposting')
                                ->body('Jurnal No. ' . $record->jurnal . ' sebagian atau seluruhnya sudah diposting sebelumnya.')
                                ->send();
                            return;
                        }

                        // 3. Cek duplikasi di Jurnal Umum
                        $sudahAda = JurnalUmum::where('jurnal', $record->jurnal)->exists();

                        if ($sudahAda) {
                            Notification::make()
                                ->danger()
                                ->title('Duplikasi Jurnal!')
                                ->body('Jurnal No. ' . $record->jurnal . ' sudah ada di Jurnal Umum.')
                                ->send();
                            return;
                        }

                        // 4. Ambil semua header se-jurnal dengan urutan id ASC
                        //    Lalu INSERT ke JU dengan urutan TERBALIK (id DESC)
                        //    Karena JU ditampilkan latest('id'), urutan terbalik saat insert
                        //    = urutan benar saat ditampilkan di JU
                        $headers = JurnalPembantuHeader::where('jurnal', $record->jurnal)
                            ->orderBy('id', 'desc') // ← terbalik agar di JU tampil ASC
                            ->get();

                        $tgl         = $record->tgl_transaksi?->format('Y-m-d') ?? now()->format('Y-m-d');
                        $postingOleh = Auth::id();
                        $tglPosting  = now();

                        DB::transaction(function () use ($headers, $tgl, $postingOleh, $tglPosting) {
                            foreach ($headers as $header) {

                                // Hitung total qty dan harga rata-rata dari items aktif
                                $itemsAktif  = $header->items()->where('status', true)->get();
                                $totalBanyak = $itemsAktif->sum('banyak');
                                $totalJumlah = $itemsAktif->sum('jumlah');

                                if ($totalBanyak > 0) {
                                    $hargaRata = $totalJumlah / $totalBanyak;
                                    $banyak    = $totalBanyak;
                                } else {
                                    $hargaRata = $header->total_nilai;
                                    $banyak    = 1;
                                }

                                JurnalUmum::create([
                                    'tgl'        => $tgl,
                                    'jurnal'     => $header->jurnal,
                                    'no_akun'    => $header->no_akun,
                                    'nama_akun'  => $header->nama_akun,
                                    'nama'       => $header->no_dokumen
                                        ?? JurnalPembantuHeader::JENIS[$header->jenis_transaksi]
                                        ?? null,
                                    'keterangan' => $header->keterangan,
                                    'banyak'     => $banyak,
                                    'harga'      => round($hargaRata, 2),
                                    'map'        => strtolower($header->map),
                                ]);

                                $header->update([
                                    'status'         => JurnalPembantuHeader::STATUS_DIPOSTING,
                                    'diposting_oleh' => $postingOleh,
                                    'tgl_posting'    => $tglPosting,
                                ]);
                            }
                        });

                        Notification::make()
                            ->success()
                            ->title('Berhasil Diposting!')
                            ->body(
                                'Jurnal No. ' . $record->jurnal .
                                ' (' . $headers->count() . ' baris) berhasil dikirim ke Jurnal Umum.'
                            )
                            ->send();
                    }),

                // ── ACTION: Jurnal Balik ──────────────────────────────
                Action::make('balik')
                    ->label('Jurnal Balik')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn($record) => $record->isPosted() && !$record->adalah_jurnal_balik)
                    ->requiresConfirmation()
                    ->modalHeading('Buat Jurnal Balik')
                    ->modalDescription(
                        fn($record) =>
                        "Jurnal No. {$record->jurnal} akan dibalik. Sistem membuat jurnal baru D/K terbalik dengan status Draft."
                    )
                    ->action(function ($record) {
                        $headers = JurnalPembantuHeader::where('jurnal', $record->jurnal)
                            ->where('status', JurnalPembantuHeader::STATUS_DIPOSTING)
                            ->orderBy('id')
                            ->get();

                        $noJurnalBaru = (JurnalPembantuHeader::max('jurnal') ?? 0) + 1;
                        $noJpBaru     = (JurnalPembantuHeader::max('no_jurnal_pembantu') ?? 0) + 1;

                        DB::transaction(function () use ($headers, $noJurnalBaru, &$noJpBaru) {
                            foreach ($headers as $header) {
                                // Buat header jurnal balik (D/K dibalik)
                                $headerBalik = JurnalPembantuHeader::create([
                                    'no_jurnal_pembantu'  => $noJpBaru++,
                                    'tgl_transaksi'       => now()->toDateString(),
                                    'jenis_transaksi'     => 'balik',
                                    'modul_asal'          => $header->modul_asal,
                                    'jurnal'              => $noJurnalBaru,
                                    'no_akun'             => $header->no_akun,
                                    'nama_akun'           => $header->nama_akun,
                                    'map'                 => $header->map === 'd' ? 'k' : 'd',
                                    'keterangan'          => 'BALIK: ' . $header->keterangan,
                                    'no_dokumen'          => $header->no_dokumen,
                                    'total_nilai'         => $header->total_nilai,
                                    'status'              => JurnalPembantuHeader::STATUS_DRAFT,
                                    'adalah_jurnal_balik' => true,
                                    'membalik_id'         => $header->id,
                                    'dibuat_oleh'         => Auth::id(),
                                ]);

                                // Salin items aktif dari header asli ke header balik
                                $itemsAktif = $header->items()->where('status', true)->get();
                                foreach ($itemsAktif as $item) {
                                    $headerBalik->items()->create([
                                        'urut'         => $item->urut,
                                        'jenis_pihak'  => $item->jenis_pihak,
                                        'nama_pihak'   => $item->nama_pihak,
                                        'nama_barang'  => $item->nama_barang,
                                        'no_dokumen'   => $item->no_dokumen,
                                        'no_referensi' => $item->no_referensi,
                                        'keterangan'   => $item->keterangan,
                                        'banyak'       => $item->banyak,
                                        'harga'        => $item->harga,
                                        'jumlah'       => $item->jumlah,
                                        'status'       => true,
                                        'created_by'   => Auth::id(),
                                    ]);
                                }

                                $header->update(['status' => JurnalPembantuHeader::STATUS_DIBALIK]);
                            }
                        });

                        Notification::make()
                            ->success()
                            ->title('Jurnal Balik Dibuat')
                            ->body("Jurnal Balik No. {$noJurnalBaru} berhasil dibuat (status Draft). Items dari jurnal asli sudah disalin.")
                            ->send();
                    }),

                DeleteAction::make()
                    ->visible(fn($record) => $record->isDraft()),
            ])

            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('created_at', 'desc');
    }
}