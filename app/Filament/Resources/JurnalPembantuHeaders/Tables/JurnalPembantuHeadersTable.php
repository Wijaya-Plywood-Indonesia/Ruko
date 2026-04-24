<?php

namespace App\Filament\Resources\JurnalPembantuHeaders\Tables;

use App\Models\JurnalPembantuHeader;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
                        'draft' => 'gray',
                        'diposting' => 'success',
                        'dibalik' => 'warning',
                        'dibatalkan' => 'danger',
                        default => 'gray',
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
                            ->when($data['dari'], fn($q, $v) => $q->whereDate('tgl_transaksi', '>=', $v))
                            ->when($data['sampai'], fn($q, $v) => $q->whereDate('tgl_transaksi', '<=', $v))
                    ),
            ])

            ->actions([
                ViewAction::make(),

                EditAction::make()
                    ->visible(fn($record) => $record->isDraft()),

                // ── ACTION: Posting ───────────────────────────────────
                Action::make('posting')
                    ->label('Posting')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->visible(fn($record) => $record->isDraft())
                    ->requiresConfirmation()
                    ->modalHeading('Posting ke Jurnal Umum')
                    ->modalDescription(
                        fn($record) =>
                        "Jurnal No. {$record->jurnal} akan diposting. Pastikan semua pasangan D/K sudah lengkap dan balance."
                    )
                    ->action(function ($record) {
                        if (!$record->isBalanced()) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak Balance!')
                                ->body('Total Debet ≠ Total Kredit untuk Jurnal No. ' . $record->jurnal . '. Posting dibatalkan.')
                                ->send();
                            return;
                        }

                        $adaYangBukanDraft = JurnalPembantuHeader::where('jurnal', $record->jurnal)
                            ->where('status', '!=', JurnalPembantuHeader::STATUS_DRAFT)
                            ->exists();

                        if ($adaYangBukanDraft) {
                            Notification::make()
                                ->danger()
                                ->title('Ada Header Tidak Draft')
                                ->body('Sebagian header dalam Jurnal No. ' . $record->jurnal . ' sudah diposting atau dibatalkan.')
                                ->send();
                            return;
                        }

                        $tgl = $record->tgl_transaksi ?? $record->created_at->toDateString();
                        $headers = JurnalPembantuHeader::where('jurnal', $record->jurnal)->get();

                        foreach ($headers as $header) {
                            \App\Models\JurnalUmum::create([
                                'tgl' => $tgl,
                                'jurnal' => $header->jurnal,
                                'no_akun' => $header->no_akun,
                                'nama_akun' => $header->nama_akun,
                                'keterangan' => $header->keterangan,
                                'banyak' => 1,
                                'harga' => $header->total_nilai,
                                'map' => strtoupper($header->map),
                            ]);

                            $header->update([
                                'status' => JurnalPembantuHeader::STATUS_DIPOSTING,
                                'diposting_oleh' => Auth::id(),
                                'tgl_posting' => now(),
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title('Berhasil Diposting')
                            ->body('Jurnal No. ' . $record->jurnal . ' berhasil dikirim ke Jurnal Umum.')
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
                            ->get();

                        $noJurnalBaru = (JurnalPembantuHeader::max('jurnal') ?? 0) + 1;
                        $noJpBaru = (JurnalPembantuHeader::max('no_jurnal_pembantu') ?? 0) + 1;

                        foreach ($headers as $header) {
                            JurnalPembantuHeader::create([
                                'no_jurnal_pembantu' => $noJpBaru++,
                                'tgl_transaksi' => now()->toDateString(),
                                'jenis_transaksi' => 'balik',
                                'modul_asal' => $header->modul_asal,
                                'jurnal' => $noJurnalBaru,
                                'no_akun' => $header->no_akun,
                                'nama_akun' => $header->nama_akun,
                                'map' => $header->map === 'd' ? 'k' : 'd',
                                'keterangan' => 'BALIK: ' . $header->keterangan,
                                'no_dokumen' => $header->no_dokumen,
                                'total_nilai' => $header->total_nilai,
                                'status' => JurnalPembantuHeader::STATUS_DRAFT,
                                'adalah_jurnal_balik' => true,
                                'membalik_id' => $header->id,
                                'dibuat_oleh' => Auth::id(),
                            ]);

                            $header->update(['status' => JurnalPembantuHeader::STATUS_DIBALIK]);
                        }

                        Notification::make()
                            ->success()
                            ->title('Jurnal Balik Dibuat')
                            ->body("Jurnal Balik No. {$noJurnalBaru} berhasil dibuat (status Draft).")
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
