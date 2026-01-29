<?php

namespace App\Filament\Pages;

use App\Models\SuratJalan;
use App\Services\SuratJalanPenerimaanService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Forms;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TerimaSuratJalan extends Page implements HasForms
{
    use InteractsWithForms;

    public static ?string $navigationLabel = 'Terima Barang';

    public static BackedEnum|string|null $navigationIcon =
        'heroicon-o-clipboard-document-check';

    public function getView(): string
    {
        return 'filament.pages.terima-surat-jalan';
    }

    public ?string $no_surat_jalan = null;
    public ?SuratJalan $suratJalan = null;
    public array $details = [];

    public function mount(): void
    {
        $this->form->fill();

    }

    /**
     * ✅ SATU-SATUNYA CARA BENAR DI PAGE
     */
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('no_surat_jalan')
                ->label('No Surat Jalan')
                ->required()
                ->datalist(function (?string $state) {
                    if (!$state || strlen($state) < 3) {
                        return [];
                    }

                    return SuratJalan::query()
                        ->where('status', 'dikirim')
                        ->where('no_surat_jalan', 'like', "%{$state}%")
                        ->orderByDesc('tanggal_kirim')
                        ->limit(10)
                        ->pluck('no_surat_jalan')
                        ->toArray();
                })
                ->helperText('Ketik minimal 3 karakter lalu klik Cari')
                ->suffixAction(
                    Action::make('cari')
                        ->icon('heroicon-o-magnifying-glass')
                        ->tooltip('Cari Surat Jalan')
                        ->extraAttributes([
                            'class' => 'px-5 py-2',
                        ])
                        ->action(
                            fn() =>
                            $this->loadSuratJalan(
                                $this->form->getState()['no_surat_jalan'] ?? null
                            )
                        )
                ),
        ]);
    }


    // protected function loadSuratJalan(?string $no): void
    // {
    //     if (!$no) {
    //         $this->suratJalan = null;
    //         return;
    //     }

    //     $this->suratJalan = SuratJalan::with([
    //         'details.barang',
    //         'tokoAsal',
    //         'tokoTujuan',
    //     ])
    //         ->where('no_surat_jalan', $no)
    //         ->where('status', 'dikirim')
    //         ->first();

    //     if (!$this->suratJalan) {
    //         Notification::make()
    //             ->title('Surat jalan tidak ditemukan / belum dikirim')
    //             ->danger()
    //             ->send();
    //         return;
    //     }

    //     // ✅ DEFAULT qty_diterima = qty_kirim
    //     foreach ($this->suratJalan->details as $detail) {
    //         $detail->qty_diterima ??= $detail->qty_kirim;
    //         $detail->catatan ??= null;
    //     }
    // }
    public function loadSuratJalan(?string $noSurat): void
    {
        if (!$noSurat) {
            return;
        }

        $this->suratJalan = SuratJalan::with([
            'details.barang',
            'tokoAsal',
            'tokoTujuan',
        ])
            ->where('status', 'dikirim')
            ->where('no_surat_jalan', $noSurat)
            ->first();

        if (!$this->suratJalan) {
            Notification::make()
                ->title('Surat jalan tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        // 🔑 INI YANG PALING PENTING
        $this->details = $this->suratJalan->details->map(function ($detail) {
            return [
                'id' => $detail->id,
                'barang' => $detail->barang->nama_barang ?? '-',
                'qty_kirim' => $detail->qty_kirim,
                // 🔑 DEFAULT CEPAT
                'qty_diterima' => $detail->qty_diterima ?? $detail->qty_kirim,

                //'qty_diterima' => $detail->qty_diterima,
                'catatan' => $detail->catatan,
                // 🔒 state kunci
                'locked' => false,
            ];
        })->toArray();
    }


    public function submit(): void
    {
        if (!$this->suratJalan) {
            Notification::make()
                ->title('Data surat jalan belum valid')
                ->danger()
                ->send();
            return;
        }

        // 🔒 VALIDASI PER ITEM
        foreach ($this->details as $item) {
            if ($item['qty_diterima'] > $item['qty_kirim']) {
                throw ValidationException::withMessages([
                    'details' => 'Qty diterima tidak boleh melebihi qty kirim',
                ]);
            }
        }

        app(SuratJalanPenerimaanService::class)->terima(
            suratJalan: $this->suratJalan,
            userId: auth()->id(),
            details: $this->details
        );

        Notification::make()
            ->title('Penerimaan barang berhasil')
            ->success()
            ->send();

        $this->reset(['no_surat_jalan', 'suratJalan', 'details']);
        $this->form->fill();
    }
    protected function getFormStatePath(): string
    {
        return 'data';
    }
}
