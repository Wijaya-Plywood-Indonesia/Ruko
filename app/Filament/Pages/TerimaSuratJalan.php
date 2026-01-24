<?php

namespace App\Filament\Pages;

use App\Models\SuratJalan;
use App\Services\SuratJalanPenerimaanService;
use BackedEnum;
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
                ->live(debounce: 500)
                ->datalist(function (?string $state) {

                    // ⛔ saat form pertama kali dibuka
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
                ->afterStateUpdated(
                    fn(?string $state) => $this->loadSuratJalan($state)
                ),
        ]);
    }

    protected function loadSuratJalan(?string $no): void
    {
        if (!$no) {
            $this->suratJalan = null;
            return;
        }

        $this->suratJalan = SuratJalan::with([
            'details.barang',
            'tokoAsal',
            'tokoTujuan',
        ])
            ->where('no_surat_jalan', $no)
            ->where('status', 'dikirim')
            ->first();

        if (!$this->suratJalan) {
            Notification::make()
                ->title('Surat jalan tidak ditemukan / belum dikirim')
                ->danger()
                ->send();
        }
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

        app(SuratJalanPenerimaanService::class)
            ->terima($this->suratJalan, auth()->id());

        Notification::make()
            ->title('Penerimaan barang berhasil')
            ->success()
            ->send();

        $this->reset(['no_surat_jalan', 'suratJalan']);
        $this->form->fill();
    }
}
