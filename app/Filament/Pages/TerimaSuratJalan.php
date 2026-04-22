<?php

namespace App\Filament\Pages;

use App\Models\SuratJalan;
use App\Services\SuratJalanPenerimaanService;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class TerimaSuratJalan extends Page implements HasForms
{
    use InteractsWithForms;

    public static ?string $navigationLabel = 'Terima Barang';
    protected static string|UnitEnum|null $navigationGroup = 'Stock Barang';

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
                        ->whereIn('status', ['dikirim', 'perjalanan'])
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
            ->whereIn('status', ['dikirim', 'perjalanan'])
            ->where('no_surat_jalan', $noSurat)
            ->first();

        if (!$this->suratJalan) {
            Notification::make()
                ->title('Surat jalan tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        $this->details = $this->suratJalan->details
            ->map(function ($detail) {

                $qtyDefault = !empty($detail->qty_diterima) && (float) $detail->qty_diterima > 0
                    ? $detail->qty_diterima
                    : $detail->qty_kirim;

                return [
                    'id' => $detail->id,
                    'barang' => $detail->barang->nama_barang ?? '-',

                    // ✅ FIX: gunakan float
                    'qty_kirim' => number_format((float) $detail->qty_kirim, 2, '.', ''),
                    'qty_diterima' => number_format((float) $qtyDefault, 2, '.', ''),

                    'catatan' => $detail->catatan,
                    'locked' => false,
                ];
            })
            ->values()
            ->toArray();
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

        foreach ($this->details as $item) {

            $qtyKirim = (float) $item['qty_kirim'];
            $qtyDiterima = (float) $item['qty_diterima'];

            // ✅ validasi decimal
            if ($qtyDiterima > $qtyKirim) {
                throw ValidationException::withMessages([
                    'details' => 'Qty diterima tidak boleh melebihi qty kirim',
                ]);
            }

            if ($qtyDiterima < 0) {
                throw ValidationException::withMessages([
                    'details' => 'Qty diterima tidak boleh minus',
                ]);
            }
        }

        // ✅ sum decimal aman
        $totalDiterima = collect($this->details)
            ->sum(fn($item) => (float) $item['qty_diterima']);

        if ($totalDiterima <= 0) {
            Notification::make()
                ->title('Tidak ada barang yang diterima')
                ->danger()
                ->send();
            return;
        }

        app(SuratJalanPenerimaanService::class)->terima(
            $this->suratJalan,
            $this->details,
            auth()->id()
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