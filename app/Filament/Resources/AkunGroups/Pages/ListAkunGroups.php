<?php

namespace App\Filament\Resources\AkunGroups\Pages;

use App\Filament\Resources\AkunGroups\AkunGroupResource;
use App\Models\AkunGroup;
use App\Models\SubAnakAkun;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAkunGroups extends ListRecords
{
    protected static string $resource = AkunGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sinkronAkun')
                ->label('Sinkron Akun Baru')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sinkronisasi Akun Otomatis')
                ->modalDescription('Aksi ini akan memasukkan Sub Akun baru ke Grup Akun (Aktiva Lancar, Pasiva, dll) berdasarkan awalan kode akun induk secara otomatis. Lanjutkan?')
                ->action(function () {
                    $this->syncSubAkunToGroup();
                }),
            CreateAction::make(),
        ];
    }

    /**
     * Logika sinkronisasi otomatis Sub Anak Akun ke Akun Group
     */
    protected function syncSubAkunToGroup(): void
    {
        // 1. Ambil ID dari Akun Group target berdasarkan namanya 
        // Pastikan nama di database sesuai persis (case-sensitive bisa berpengaruh tergantung DB)
        $groupAktivaLancar = AkunGroup::where('nama', 'AKTIVA LANCAR')->first();
        $groupPasiva       = AkunGroup::where('nama', 'PASIVA')->first();
        $groupPendapatan   = AkunGroup::where('nama', 'PENDAPATAN PENJUALAN')->first();
        $groupBeban        = AkunGroup::where('nama', 'BEBAN')->first();
        $groupHpp          = AkunGroup::where('nama', 'HPP')->first();

        // 2. Tarik semua sub akun beserta relasinya untuk mendapatkan kode induk akun
        $subAkuns = SubAnakAkun::with('anakAkun.indukAkun')->get();

        $syncedCount = 0;

        foreach ($subAkuns as $subAkun) {
            // Abaikan jika relasi ke induk tidak valid
            if (! $subAkun->anakAkun || ! $subAkun->anakAkun->indukAkun) {
                continue;
            }

            // Ambil awalan/digit pertama dari kode induk akun (misal: '1' dari '1578.00')
            $kodeInduk = (string) $subAkun->anakAkun->indukAkun->kode_induk_akun;
            $prefix = substr($kodeInduk, 0, 1);
            $targetGroup = null;

            // 3. Tentukan masuk ke Grup mana berdasarkan digit pertama
            if ($prefix === '1' && $groupAktivaLancar) {
                $targetGroup = $groupAktivaLancar;
            } elseif (($prefix === '2' || $prefix === '3') && $groupPasiva) {
                $targetGroup = $groupPasiva;
            } elseif ($prefix === '4' && $groupPendapatan) {
                $targetGroup = $groupPendapatan;
            } elseif ($prefix === '5' && $groupBeban) {
                $targetGroup = $groupBeban;
            } elseif ($prefix === '6' && $groupHpp) {
                $targetGroup = $groupHpp;
            }

            // 4. Jika target grup cocok, pasangkan (attach) datanya
            if ($targetGroup) {
                // syncWithoutDetaching berfungsi untuk mengaitkan data ke pivot
                // tanpa menghapus data lama dan otomatis mencegah duplikasi.
                $result = $targetGroup->subAnakAkuns()->syncWithoutDetaching([$subAkun->id]);
                
                // Menghitung hanya data yang baru saja berhasil ditambahkan (bukan yang sudah ada sebelumnya)
                if (! empty($result['attached'])) {
                    $syncedCount++;
                }
            }
        }

        // 5. Tampilkan notifikasi keberhasilan
        Notification::make()
            ->title('Sinkronisasi Selesai')
            ->body("Berhasil mensinkronkan <strong>{$syncedCount}</strong> Sub Akun baru ke Akun Group.")
            ->success()
            ->send();
    }
}