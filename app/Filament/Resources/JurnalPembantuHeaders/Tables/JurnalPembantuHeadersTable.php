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

                        // ── STEP 1: Validasi Balance ──────────────────────────────────────
                        // Sebelum apapun dilakukan, pastikan total Debit = total Kredit.
                        // Kalau tidak balance, jurnal tidak boleh diposting karena akan
                        // merusak keseimbangan buku besar. Proses langsung dihentikan.
                        if (!$record->isBalanced()) {
                            Notification::make()->danger()
                                ->title('Tidak Balance!')
                                ->body('Posting dibatalkan.')
                                ->send();
                            return;
                        }

                        // ── STEP 2: Validasi Status Draft ────────────────────────────────
                        // Pastikan semua baris dalam grup jurnal ini masih berstatus draft.
                        // Kalau ada yang sudah diposting sebelumnya, hentikan prosesnya
                        // agar tidak terjadi double posting pada baris yang sama.
                        if (JurnalPembantuHeader::where('jurnal', $record->jurnal)
                            ->where('status', '!=', JurnalPembantuHeader::STATUS_DRAFT)
                            ->exists()
                        ) {
                            Notification::make()->danger()
                                ->title('Jurnal Sudah Diposting')
                                ->send();
                            return;
                        }

                        // ── STEP 3: Tentukan Nomor Jurnal Final ──────────────────────────
                        // Cek apakah nomor jurnal ini sudah ada di Jurnal Umum.
                        // Kalau belum ada  → pakai nomor asli.
                        // Kalau sudah ada  → ambil nomor terbesar dari kedua tabel (JP & JU)
                        //                    lalu tambah 1 sebagai nomor baru yang aman.
                        // Ini hanya kalkulasi — belum ada operasi ke database sama sekali.
                        $nomorAsli  = (int) $record->jurnal;
                        $nomorFinal = $nomorAsli;

                        if (JurnalUmum::where('jurnal', $nomorAsli)->exists()) {
                            $nomorFinal = max(
                                (int) (JurnalUmum::max('jurnal') ?? 0),
                                (int) (JurnalPembantuHeader::max('jurnal') ?? 0)
                            ) + 1;
                        }


                        // ── STEP 4: Ambil Data Headers Sebelum Transaksi ─────────────────
                        // Query dilakukan di luar transaksi menggunakan nomor ASLI,
                        // karena JP belum diupdate di titik ini.
                        // Urutan id DESC agar saat masuk ke JU (yang tampil latest id),
                        // urutan baris tetap terbaca dari atas ke bawah dengan benar.
                        $headers     = JurnalPembantuHeader::where('jurnal', $nomorAsli)
                            ->orderBy('id', 'desc')
                            ->get();

                        $tgl         = $record->tgl_transaksi?->format('Y-m-d') ?? now()->format('Y-m-d');
                        $postingOleh = Auth::id();
                        $tglPosting  = now();


                        // ── STEP 5: Eksekusi dalam Satu Transaksi Database ───────────────
                        // Semua operasi DB digabung dalam satu DB::transaction.
                        // Artinya: kalau salah satu langkah gagal di tengah jalan,
                        // seluruh operasi otomatis di-rollback — tidak ada data setengah jalan.
                        // Urutan di dalam transaksi:
                        //   5a → Update nomor jurnal di JP (kalau berubah)
                        //   5b → Insert ke Jurnal Umum
                        //   5c → Update status JP menjadi 'diposting'
                        try {
                            DB::transaction(function () use ($headers, $tgl, $postingOleh, $tglPosting, $nomorAsli, $nomorFinal) {

                                // ✅ Update nomor JP — hanya di dalam transaksi, hanya sekali
                                if ($nomorFinal !== $nomorAsli) {
                                    JurnalPembantuHeader::where('jurnal', $nomorAsli)
                                        ->update(['jurnal' => $nomorFinal]);
                                }

                                foreach ($headers as $header) {
                                    $itemsAktif  = $header->items()->where('status', true)->get();
                                    $totalBanyak = $itemsAktif->sum('banyak');
                                    $totalJumlah = $itemsAktif->sum('jumlah');

                                    $banyak    = $totalBanyak > 0 ? $totalBanyak : 1;
                                    $hargaRata = $totalBanyak > 0
                                        ? $totalJumlah / $totalBanyak
                                        : $header->total_nilai;

                                    // Ekstrak nama pihak dari keterangan (format: "Keterangan | No.Nota: XXX | Nama Pihak")
                                    $parts = explode('|', $header->keterangan);
                                    $parsedNama = isset($parts[2]) ? trim($parts[2]) : null;

                                    JurnalUmum::create([
                                        'tgl'        => $tgl,
                                        'jurnal'     => $nomorFinal, // ← nomor yang sudah benar
                                        'no_akun'    => $header->no_akun,
                                        'nama_akun'  => $header->nama_akun,
                                        'nama'       => $parsedNama
                                            ?? $header->no_dokumen
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
                        } catch (\Throwable $e) {
                            Notification::make()->danger()
                                ->title('Gagal Posting')
                                ->body($e->getMessage())
                                ->send();
                            return;
                        }

                        $info = $nomorFinal !== $nomorAsli
                            ? " (No. disesuaikan: {$nomorAsli} → {$nomorFinal})"
                            : '';

                        Notification::make()->success()
                            ->title('Berhasil Diposting!')
                            ->body("Jurnal No. {$nomorFinal} ({$headers->count()} baris) dikirim ke Jurnal Umum.{$info}")
                            ->send();
                    }),

                // ── ACTION: Jurnal Balik ──────────────────────────────
                Action::make('balik')
                    ->label('Jurnal Balik')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(function ($record) {
                        // Hanya tampilkan kalau sudah diposting dan bukan jurnal balik
                        if (!$record->isPosted() || $record->adalah_jurnal_balik) return false;

                        // ✅ Sama seperti posting — hanya muncul di baris pertama
                        // (id terkecil) per nomor jurnal yang sama
                        $idPertama = JurnalPembantuHeader::where('jurnal', $record->jurnal)
                            ->where('status', JurnalPembantuHeader::STATUS_DIPOSTING)
                            ->min('id');

                        return $record->id === $idPertama;
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn($record) => "Buat Jurnal Balik No. {$record->jurnal}")
                    ->modalDescription(function ($record) {
                        // Tampilkan ringkasan jurnal yang akan dibalik
                        $headers = JurnalPembantuHeader::where('jurnal', $record->jurnal)
                            ->where('status', JurnalPembantuHeader::STATUS_DIPOSTING)
                            ->get();

                        $totalD = $headers->where('map', 'd')->sum('total_nilai');
                        $totalK = $headers->where('map', 'k')->sum('total_nilai');
                        $count  = $headers->count();

                        return "Jurnal No. {$record->jurnal} memiliki {$count} baris. "
                            . "Debit: Rp " . number_format($totalD, 0, ',', '.') . " | "
                            . "Kredit: Rp " . number_format($totalK, 0, ',', '.') . ". "
                            . "Sistem akan membuat jurnal baru dengan D/K terbalik, status Draft.";
                    })
                    ->modalSubmitActionLabel('Ya, Buat Jurnal Balik')
                    ->action(function ($record) {

                        // ── STEP 1: Ambil semua headers se-jurnal yang sudah diposting ───
                        // Hanya ambil yang status-nya DIPOSTING agar tidak ikut
                        // membalik baris yang mungkin sudah berstatus lain.
                        $headers = JurnalPembantuHeader::where('jurnal', $record->jurnal)
                            ->where('status', JurnalPembantuHeader::STATUS_DIPOSTING)
                            ->orderBy('id')
                            ->get();

                        if ($headers->isEmpty()) {
                            Notification::make()->danger()
                                ->title('Tidak Ada Data')
                                ->body('Tidak ada baris diposting untuk Jurnal No. ' . $record->jurnal)
                                ->send();
                            return;
                        }

                        // ── STEP 2: Tentukan Nomor Jurnal & No JP Baru ───────────────────
                        // Ambil nomor terbesar dari tabel JP lalu +1.
                        // Dilakukan di luar transaksi karena hanya kalkulasi.
                        $noJurnalBaru = (int) (JurnalPembantuHeader::max('jurnal') ?? 0) + 1;
                        $noJpBaru     = (int) (JurnalPembantuHeader::max('no_jurnal_pembantu') ?? 0) + 1;

                        // ── STEP 3: Eksekusi dalam Satu Transaksi ────────────────────────
                        // Semua operasi digabung agar atomic — kalau gagal di tengah,
                        // seluruh perubahan otomatis di-rollback.
                        try {
                            DB::transaction(function () use ($headers, $noJurnalBaru, &$noJpBaru) {
                                foreach ($headers as $header) {

                                    // ── STEP 3a: Buat Header Jurnal Balik ────────────────
                                    // D/K dibalik: yang tadinya 'd' jadi 'k', begitu pula sebaliknya.
                                    // Keterangan diberi prefix 'BALIK:' agar mudah diidentifikasi.
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

                                    // ── STEP 3b: Salin Items Aktif ke Header Balik ───────
                                    // Items disalin agar jurnal balik punya detail
                                    // yang sama dengan jurnal aslinya.
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

                                    // ── STEP 3c: Update Status Header Asli → DIBALIK ─────
                                    // Tandai header asli sebagai sudah dibalik
                                    // agar tidak bisa dibalik dua kali.
                                    $header->update([
                                        'status' => JurnalPembantuHeader::STATUS_DIBALIK,
                                    ]);
                                }
                            });
                        } catch (\Throwable $e) {
                            // ── STEP 3 GAGAL: Tangkap Error ──────────────────────────────
                            // Transaksi otomatis di-rollback, tidak ada data setengah jalan.
                            Notification::make()->danger()
                                ->title('Gagal Membuat Jurnal Balik')
                                ->body('Terjadi error: ' . $e->getMessage())
                                ->send();
                            return;
                        }

                        // ── STEP 4: Notifikasi Sukses ─────────────────────────────────────
                        // Informasikan nomor jurnal balik yang baru dibuat.
                        // Status masih Draft — user perlu posting jurnal balik ini secara terpisah.
                        Notification::make()->success()
                            ->title('Jurnal Balik Dibuat')
                            ->body(
                                "Jurnal Balik No. {$noJurnalBaru} berhasil dibuat ({$headers->count()} baris), "
                                    . "status Draft. Silakan posting jika sudah siap."
                            )
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
