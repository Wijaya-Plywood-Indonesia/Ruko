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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

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
                    ->formatStateUsing(
                        fn($state) =>
                        JurnalPembantuHeader::JENIS[$state] ?? $state
                    )
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
                    ->color(
                        fn($state) =>
                        strtolower($state) === 'd'
                        ? 'info'
                        : 'warning'
                    )
                    ->formatStateUsing(
                        fn($state) => strtoupper($state)
                    ),

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
                    ->formatStateUsing(
                        fn($state) =>
                        JurnalPembantuHeader::STATUSES[$state] ?? $state
                    ),

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
                        DatePicker::make('dari')
                            ->label('Dari Tanggal'),

                        DatePicker::make('sampai')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(
                        fn(Builder $query, array $data): Builder =>
                        $query
                            ->when(
                                $data['dari'] ?? null,
                                fn($q, $v) =>
                                $q->whereDate('tgl_transaksi', '>=', $v)
                            )
                            ->when(
                                $data['sampai'] ?? null,
                                fn($q, $v) =>
                                $q->whereDate('tgl_transaksi', '<=', $v)
                            )
                    ),

            ])

            ->actions([

                ViewAction::make(),

                EditAction::make()
                    ->visible(
                        fn($record) =>
                        $record->isDraft()
                    ),

                /*
                |--------------------------------------------------------------------------
                | POSTING JURNAL
                |--------------------------------------------------------------------------
                */

                Action::make('posting')

                    ->label(
                        fn($record) =>
                        "Posting Jurnal No. {$record->jurnal}"
                    )

                    ->icon('heroicon-o-arrow-up-tray')

                    ->color('success')

                    ->visible(function ($record) {

                        if (!$record->isDraft()) {
                            return false;
                        }

                        $idPertama = JurnalPembantuHeader::query()
                            ->where('jurnal', $record->jurnal)
                            ->where('status', JurnalPembantuHeader::STATUS_DRAFT)
                            ->min('id');

                        return (int) $record->id === (int) $idPertama;
                    })

                    ->requiresConfirmation()

                    ->modalHeading(
                        fn($record) =>
                        "Posting Jurnal No. {$record->jurnal}"
                    )

                    ->modalDescription(function ($record) {

                        $headers = JurnalPembantuHeader::query()
                            ->where('jurnal', $record->jurnal)
                            ->get();

                        $totalD = $headers
                            ->where('map', 'd')
                            ->sum('total_nilai');

                        $totalK = $headers
                            ->where('map', 'k')
                            ->sum('total_nilai');

                        $balance =
                            abs($totalD - $totalK) < 0.0001;

                        return
                            "Jumlah Baris: {$headers->count()} | " .
                            "Debit: Rp " . number_format($totalD, 0, ',', '.') . " | " .
                            "Kredit: Rp " . number_format($totalK, 0, ',', '.') . " | " .
                            "Status: " . ($balance ? '✓ Balance' : '✗ Tidak Balance');
                    })

                    ->modalSubmitActionLabel('Ya, Posting')

                    ->action(function ($record) {

                        try {

                            DB::transaction(function () use ($record) {

                                /*
                                |--------------------------------------------------------------------------
                                | LOCK DATA
                                |--------------------------------------------------------------------------
                                | Mencegah race condition multi user
                                */

                                $headers = JurnalPembantuHeader::query()

                                    ->with([
                                        'items' => fn($q) =>
                                            $q->where('status', true)
                                    ])

                                    ->where('jurnal', $record->jurnal)

                                    ->lockForUpdate()

                                    ->get();

                                if ($headers->isEmpty()) {
                                    throw new \Exception(
                                        'Data jurnal tidak ditemukan.'
                                    );
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | VALIDASI STATUS
                                |--------------------------------------------------------------------------
                                */

                                $adaNonDraft = $headers
                                    ->where('status', '!=', JurnalPembantuHeader::STATUS_DRAFT)
                                    ->count();

                                if ($adaNonDraft > 0) {
                                    throw new \Exception(
                                        'Sebagian jurnal sudah diposting.'
                                    );
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | VALIDASI BALANCE
                                |--------------------------------------------------------------------------
                                */

                                $totalDebit = $headers
                                    ->where('map', 'd')
                                    ->sum('total_nilai');

                                $totalKredit = $headers
                                    ->where('map', 'k')
                                    ->sum('total_nilai');

                                if (abs($totalDebit - $totalKredit) > 0.0001) {

                                    throw new \Exception(
                                        'Jurnal tidak balance.'
                                    );
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | GENERATE NOMOR JURNAL AMAN
                                |--------------------------------------------------------------------------
                                */

                                $nomorFinal = (int) $record->jurnal;

                                $sudahAda = JurnalUmum::query()
                                    ->where('jurnal', $nomorFinal)
                                    ->lockForUpdate()
                                    ->exists();

                                if ($sudahAda) {

                                    $maxJU = (int) 
                                        (JurnalUmum::query()
                                            ->lockForUpdate()
                                            ->max('jurnal') ?? 0);

                                    $maxJP = (int) 
                                        (JurnalPembantuHeader::query()
                                            ->lockForUpdate()
                                            ->max('jurnal') ?? 0);

                                    $nomorFinal = max($maxJU, $maxJP) + 1;

                                    JurnalPembantuHeader::query()
                                        ->where('jurnal', $record->jurnal)
                                        ->update([
                                            'jurnal' => $nomorFinal
                                        ]);
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | POSTING KE JURNAL UMUM
                                |--------------------------------------------------------------------------
                                */

                                foreach ($headers as $header) {

                                    $items = $header->items;

                                    $totalBanyak = (float) 
                                        $items->sum('banyak');

                                    $totalJumlah = (float) 
                                        $items->sum('jumlah');

                                    $banyak =
                                        $totalBanyak > 0
                                        ? $totalBanyak
                                        : 1;

                                    $harga =
                                        $totalBanyak > 0
                                        ? ($totalJumlah / $totalBanyak)
                                        : (float) $header->total_nilai;

                                    // Ekstrak nama pihak dari keterangan (format: "Keterangan | No.Nota: XXX | Nama Pihak")
                                    $parts = explode('|', $header->keterangan);
                                    $parsedNama = isset($parts[2]) ? trim($parts[2]) : null;

                                    JurnalUmum::create([


                                        'tgl' => $header->tgl_transaksi
                                            ? $header->tgl_transaksi->format('Y-m-d')
                                            : now()->format('Y-m-d'),

                                        'jurnal' => $nomorFinal,

                                        'no_akun' => $header->no_akun,

                                        'nama_akun' => $header->nama_akun,

                                        'nama' =>
                                            $header->no_dokumen
                                            ?? (JurnalPembantuHeader::JENIS[$header->jenis_transaksi] ?? null),

                                        'keterangan' => $header->keterangan,

                                        'banyak' => round($banyak, 2),

                                        'harga' => round($harga, 2),

                                        'map' => strtolower($header->map),

                                    ]);

                                    /*
                                    |--------------------------------------------------------------------------
                                    | UPDATE STATUS HEADER
                                    |--------------------------------------------------------------------------
                                    */

                                    $header->update([

                                        'status' =>
                                            JurnalPembantuHeader::STATUS_DIPOSTING,

                                        'diposting_oleh' =>
                                            Auth::id() ?? 1,

                                        'tgl_posting' =>
                                            now(),

                                    ]);
                                }
                            }, 5);

                            Notification::make()
                                ->success()
                                ->title('Berhasil Diposting')
                                ->body(
                                    "Jurnal berhasil diposting."
                                )
                                ->send();
                        } catch (Throwable $e) {

                            report($e);

                            Notification::make()
                                ->danger()
                                ->title('Gagal Posting')
                                ->body(
                                    $e->getMessage()
                                )
                                ->send();
                        }
                    }),

                /*
                |--------------------------------------------------------------------------
                | JURNAL BALIK
                |--------------------------------------------------------------------------
                */

                Action::make('balik')

                    ->label('Jurnal Balik')

                    ->icon('heroicon-o-arrow-path')

                    ->color('warning')

                    ->visible(function ($record) {

                        if (
                            !$record->isPosted() ||
                            $record->adalah_jurnal_balik
                        ) {
                            return false;
                        }

                        $idPertama = JurnalPembantuHeader::query()
                            ->where('jurnal', $record->jurnal)
                            ->where('status', JurnalPembantuHeader::STATUS_DIPOSTING)
                            ->min('id');

                        return (int) $record->id === (int) $idPertama;
                    })

                    ->requiresConfirmation()

                    ->modalHeading(
                        fn($record) =>
                        "Buat Jurnal Balik No. {$record->jurnal}"
                    )

                    ->modalSubmitActionLabel('Ya, Buat')

                    ->action(function ($record) {

                        try {

                            DB::transaction(function () use ($record) {

                                /*
                                |--------------------------------------------------------------------------
                                | LOCK HEADER
                                |--------------------------------------------------------------------------
                                */

                                $headers = JurnalPembantuHeader::query()

                                    ->with([
                                        'items' => fn($q) =>
                                            $q->where('status', true)
                                    ])

                                    ->where('jurnal', $record->jurnal)

                                    ->where('status', JurnalPembantuHeader::STATUS_DIPOSTING)

                                    ->lockForUpdate()

                                    ->get();

                                if ($headers->isEmpty()) {
                                    throw new \Exception(
                                        'Data posting tidak ditemukan.'
                                    );
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | CEK SUDAH DIBALIK?
                                |--------------------------------------------------------------------------
                                */

                                $sudahDibalik = $headers
                                    ->where('status', JurnalPembantuHeader::STATUS_DIBALIK)
                                    ->count();

                                if ($sudahDibalik > 0) {
                                    throw new \Exception(
                                        'Jurnal sudah dibalik sebelumnya.'
                                    );
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | GENERATE NOMOR BARU
                                |--------------------------------------------------------------------------
                                */

                                $maxJurnal = (int) 
                                    (JurnalPembantuHeader::query()
                                        ->lockForUpdate()
                                        ->max('jurnal') ?? 0);

                                $maxNoJP = (int) 
                                    (JurnalPembantuHeader::query()
                                        ->lockForUpdate()
                                        ->max('no_jurnal_pembantu') ?? 0);

                                $nomorJurnalBaru = $maxJurnal + 1;

                                $noJPBaru = $maxNoJP + 1;

                                /*
                                |--------------------------------------------------------------------------
                                | CREATE JURNAL BALIK
                                |--------------------------------------------------------------------------
                                */

                                foreach ($headers as $header) {

                                    $headerBaru =
                                        JurnalPembantuHeader::create([

                                            'no_jurnal_pembantu' => $noJPBaru++,

                                            'tgl_transaksi' =>
                                                now()->format('Y-m-d'),

                                            'jenis_transaksi' =>
                                                'balik',

                                            'modul_asal' =>
                                                $header->modul_asal,

                                            'jurnal' =>
                                                $nomorJurnalBaru,

                                            'no_akun' =>
                                                $header->no_akun,

                                            'nama_akun' =>
                                                $header->nama_akun,

                                            'map' =>
                                                strtolower($header->map) === 'd'
                                                ? 'k'
                                                : 'd',

                                            'keterangan' =>
                                                'BALIK: ' . $header->keterangan,

                                            'no_dokumen' =>
                                                $header->no_dokumen,

                                            'total_nilai' =>
                                                $header->total_nilai,

                                            'status' =>
                                                JurnalPembantuHeader::STATUS_DRAFT,

                                            'adalah_jurnal_balik' =>
                                                true,

                                            'membalik_id' =>
                                                $header->id,

                                            'dibuat_oleh' =>
                                                Auth::id() ?? 1,

                                        ]);

                                    /*
                                    |--------------------------------------------------------------------------
                                    | COPY ITEMS
                                    |--------------------------------------------------------------------------
                                    */

                                    foreach ($header->items as $item) {

                                        $headerBaru->items()->create([

                                            'urut' =>
                                                $item->urut,

                                            'jenis_pihak' =>
                                                $item->jenis_pihak,

                                            'nama_pihak' =>
                                                $item->nama_pihak,

                                            'nama_barang' =>
                                                $item->nama_barang,

                                            'no_dokumen' =>
                                                $item->no_dokumen,

                                            'no_referensi' =>
                                                $item->no_referensi,

                                            'keterangan' =>
                                                $item->keterangan,

                                            'banyak' =>
                                                $item->banyak,

                                            'harga' =>
                                                $item->harga,

                                            'jumlah' =>
                                                $item->jumlah,

                                            'status' =>
                                                true,

                                            'created_by' =>
                                                Auth::id() ?? 1,

                                        ]);
                                    }

                                    /*
                                    |--------------------------------------------------------------------------
                                    | UPDATE STATUS ASLI
                                    |--------------------------------------------------------------------------
                                    */

                                    $header->update([

                                        'status' =>
                                            JurnalPembantuHeader::STATUS_DIBALIK,

                                    ]);
                                }
                            }, 5);

                            Notification::make()
                                ->success()
                                ->title('Berhasil')
                                ->body(
                                    'Jurnal balik berhasil dibuat.'
                                )
                                ->send();
                        } catch (Throwable $e) {

                            report($e);

                            Notification::make()
                                ->danger()
                                ->title('Gagal')
                                ->body(
                                    $e->getMessage()
                                )
                                ->send();
                        }
                    }),

                DeleteAction::make()
                    ->visible(
                        fn($record) =>
                        $record->isDraft()
                    ),

            ])

            ->bulkActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make(),

                ]),

            ])

            ->defaultSort('created_at', 'desc');
    }
}