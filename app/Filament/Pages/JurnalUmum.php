<?php

namespace App\Filament\Pages;

use App\Models\AnakAkun;
use App\Models\SubAnakAkun;
use App\Models\JurnalUmum as JurnalModel;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class JurnalUmum extends Page implements HasActions, HasForms
{
    use HasPageShield;
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'filament.pages.jurnal-umum';
    protected static UnitEnum|string|null $navigationGroup = 'Akuntansi';
    protected static ?string $title = 'Jurnal Umum';
    protected static ?int $navigationSort = 5;

    public $tgl, $jurnal, $no_akun, $nama_akun, $nama, $keterangan;
    public $harga = '';
    public $banyak = '';  // default kosong, bukan 1
    public $map = 'd';
    public $items = [];

    public bool $wasBalanced = false;
    public $filterTglDariInput = '';
    public $filterTglSampaiInput = '';
    public $filterTglDari = '';
    public $filterTglSampai = '';
    public int $perPage = 50;
    public bool $hasMorePages = true;
    public bool $isLoadingMore = false;

    public array $selectedIds = [];
    public bool $selectAll = false;

    public function mount(): void
    {
        $this->tgl    = session()->get('jurnal_draft_tgl', now()->format('Y-m-d'));
        $this->items  = session()->get('jurnal_draft_items', []);
        $this->banyak = session()->get('jurnal_draft_banyak', '');  // default ''
        $this->harga  = session()->get('jurnal_draft_harga', '');
        $this->nama   = session()->get('jurnal_draft_nama', '');

        $savedMap    = session()->get('jurnal_draft_map', 'd');
        $this->map   = in_array(strtolower($savedMap), ['d', 'k']) ? strtolower($savedMap) : 'd';

        $this->sortDraftItems();
        $this->syncJurnalNumber();
    }

    protected function syncJurnalNumber(): void
    {
        if (empty($this->items)) {
            $last         = JurnalModel::max('jurnal');
            $this->jurnal = $last ? (int) $last + 1 : 1;
        } else {
            $draft   = collect($this->items);
            $debit   = (float) $draft->filter(fn($i) => strtolower($i['map']) === 'd')->sum('total');
            $kredit  = (float) $draft->filter(fn($i) => strtolower($i['map']) === 'k')->sum('total');
            $balance = abs($debit - $kredit) < 0.01;

            // $this->jurnal = $balance
            //     ? (int) $draft->max('jurnal') + 1
            //     : (int) $draft->first()['jurnal'];
        }
        session()->put('jurnal_draft_kode', $this->jurnal);
    }

    private function persistDraftState(): void
    {
        session()->put('jurnal_draft_items',  $this->items);
        session()->put('jurnal_draft_kode',   $this->jurnal);
        session()->put('jurnal_draft_tgl',    $this->tgl);
        session()->put('jurnal_draft_map',    $this->map);
        session()->put('jurnal_draft_harga',  $this->harga);
        session()->put('jurnal_draft_banyak', $this->banyak);
        session()->put('jurnal_draft_nama',   $this->nama);
    }

    /**
     * Jika banyak diisi → total = banyak × harga
     * Jika banyak kosong/null → total = harga langsung
     */
    private function hitungTotal($banyak, $harga): float
    {
        $harga = (float) $harga;
        if ($banyak === null || $banyak === '' || (float) $banyak <= 0) {
            return $harga;
        }
        return (float) $banyak * $harga;
    }

    protected function sortDraftItems(): void
    {
        if (empty($this->items)) return;

        $this->items = collect($this->items)
            ->sort(function ($a, $b) {
                // 1. Sort by Date desc
                $tglA = $a['tgl'] ?? '';
                $tglB = $b['tgl'] ?? '';
                if ($tglA !== $tglB) {
                    return strcmp($tglB, $tglA); // desc
                }

                // 2. Sort by Journal number desc
                $jurnalA = (int) ($a['jurnal'] ?? 0);
                $jurnalB = (int) ($b['jurnal'] ?? 0);
                if ($jurnalA !== $jurnalB) {
                    return $jurnalB <=> $jurnalA; // desc
                }

                // 3. Sort by map asc (Debit ('d') before Kredit ('k'))
                $mapA = strtolower($a['map'] ?? 'd');
                $mapB = strtolower($b['map'] ?? 'd');
                if ($mapA !== $mapB) {
                    return $mapA === 'd' ? -1 : 1;
                }

                // 4. Sort by no_akun asc
                return strcasecmp($a['no_akun'] ?? '', $b['no_akun'] ?? '');
            })
            ->values()
            ->toArray();
    }

    protected function getViewData(): array
    {
        $query = JurnalModel::orderBy('tgl', 'desc')
            ->orderBy('jurnal', 'desc')
            ->orderBy('map', 'asc')
            ->orderBy('no_akun', 'asc');
        if (!empty($this->filterTglDari))
            $query->whereDate('tgl', '>=', $this->filterTglDari);
        if (!empty($this->filterTglSampai))
            $query->whereDate('tgl', '<=', $this->filterTglSampai);

        $data               = $query->limit($this->perPage + 1)->get();
        $this->hasMorePages = $data->count() > $this->perPage;
        $historyJurnals     = $data->take($this->perPage);

        $totalsQuery = JurnalModel::query();
        if (!empty($this->filterTglDari))
            $totalsQuery->whereDate('tgl', '>=', $this->filterTglDari);
        if (!empty($this->filterTglSampai))
            $totalsQuery->whereDate('tgl', '<=', $this->filterTglSampai);
        $allForTotals = $totalsQuery->get(['map', 'banyak', 'harga']);

        $totalDebitDB  = $allForTotals
            ->filter(fn($j) => strtolower($j->map) === 'd')
            ->sum(fn($j) => $this->hitungTotal($j->banyak, $j->harga));
        $totalKreditDB = $allForTotals
            ->filter(fn($j) => strtolower($j->map) === 'k')
            ->sum(fn($j) => $this->hitungTotal($j->banyak, $j->harga));

        $isHistoryBalanced = abs($totalDebitDB - $totalKreditDB) < 0.01;
        $selisihDB         = abs($totalDebitDB - $totalKreditDB);

        return [
            'accounts'          => SubAnakAkun::selectRaw("kode_sub_anak_akun as no, nama_sub_anak_akun as nama")->get(),
            'historyJurnals'    => $historyJurnals,
            'totalDebitDB'      => $totalDebitDB,
            'totalKreditDB'     => $totalKreditDB,
            'isHistoryBalanced' => $isHistoryBalanced,
            'selisihDB'         => $selisihDB,
        ];
    }

    public function applyFilter(): void
    {
        $this->filterTglDari   = $this->filterTglDariInput;
        $this->filterTglSampai = $this->filterTglSampaiInput;
        $this->perPage         = 50;
        $this->hasMorePages    = true;
        $this->selectedIds     = [];
        $this->selectAll       = false;
    }

    public function resetFilter(): void
    {
        $this->filterTglDariInput   = '';
        $this->filterTglSampaiInput = '';
        $this->filterTglDari        = '';
        $this->filterTglSampai      = '';
        $this->perPage              = 50;
        $this->hasMorePages         = true;
        $this->selectedIds          = [];
        $this->selectAll            = false;
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages) return;
        $this->perPage  += 50;
        $this->selectAll = false;
    }

    protected function isDraftBalanced(): bool
    {
        if (empty($this->items)) return false;

        $data   = collect($this->items);
        $debit  = (float) $data->filter(fn($i) => strtolower($i['map']) === 'd')->sum('total');
        $kredit = (float) $data->filter(fn($i) => strtolower($i['map']) === 'k')->sum('total');

        return abs($debit - $kredit) < 0.01;
    }

    public function updatedNoAkun($value): void
    {
        if (blank($value)) {
            $this->nama_akun = '';
            $this->persistDraftState();
            return;
        }
        $this->nama_akun = SubAnakAkun::where('kode_sub_anak_akun', $value)->first()?->nama_sub_anak_akun ?? '';
        $this->persistDraftState();
    }

    public function addItem(): void
    {
        $errors = [];

        if (blank($this->no_akun))   $errors[] = 'No. Akun wajib dipilih.';
        if (blank($this->nama_akun)) $errors[] = 'Nama Akun belum terisi.';
        if (blank($this->harga) || (float) $this->harga < 0.01)
            $errors[] = 'Harga wajib diisi (minimal Rp 1).';

        // banyak opsional — validasi hanya jika diisi tapi nilainya tidak valid
        if (!blank($this->banyak) && (float) $this->banyak <= 0)
            $errors[] = 'Kuantitas harus lebih dari 0 jika diisi.';

        if (!empty($errors)) {
            foreach ($errors as $err)
                $this->dispatch('toast', type: 'error', title: 'Field Wajib Kosong', msg: $err);
            return;
        }

        $harga = (float) $this->harga;
        $total = $this->hitungTotal($this->banyak, $harga);

        $this->items[] = [
            'tgl'        => $this->tgl,
            'jurnal'     => $this->jurnal,
            'no_akun'    => $this->no_akun,
            'nama_akun'  => $this->nama_akun,
            'nama'       => $this->nama,
            'keterangan' => $this->keterangan,
            'banyak'     => blank($this->banyak) ? null : (float) $this->banyak,
            'harga'      => $harga,
            'total'      => $total,
            'map'        => strtolower($this->map),
        ];

        $this->reset(['no_akun', 'nama_akun', 'nama', 'keterangan']);
        $this->harga  = '';
        $this->banyak = '';

        $this->sortDraftItems();

        if ($this->isDraftBalanced()) {
            $this->map         = 'd';
            $this->wasBalanced = true;
            $this->syncJurnalNumber();
            $this->dispatch('toast', type: 'success', title: 'Jurnal Balanced!', msg: 'Draft selesai — siap diposting.');
        } else {
            $this->dispatch('toast', type: 'info', title: 'Item Ditambahkan', msg: 'Jurnal belum balance — tambah entri penyeimbang.');
        }

        $this->persistDraftState();
    }

    public function removeItem(int $index): void
    {
        if (!isset($this->items[$index])) return;

        array_splice($this->items, $index, 1);

        $this->harga  = '';
        $this->banyak = '';

        $this->sortDraftItems();

        if (empty($this->items)) {
            $this->map = 'd';
            $this->dispatch('toast', type: 'error', title: 'Draft Dikosongkan', msg: 'Semua item berhasil dihapus.');
        } else {
            $this->dispatch('toast', type: 'error', title: 'Item Dihapus', msg: 'Item berhasil dihapus dari draft.');
        }

        $this->syncJurnalNumber();
        $this->persistDraftState();
    }

    public function editDraftAction(): Action
    {
        return Action::make('editDraft')
            ->modalHeading('Edit Draft Transaksi')
            ->modalSubmitActionLabel('Simpan Perubahan')
            ->form([
                Grid::make(2)->schema([
                    DatePicker::make('tgl')->label('Tanggal')->required()->native(false),
                    TextInput::make('jurnal')->label('No. Jurnal')->required(),
                    Select::make('no_akun')
                        ->label('Cari Nomor Akun')
                        ->required()
                        ->searchable()
                        ->options(fn() => SubAnakAkun::all()->mapWithKeys(
                            fn($item) => [$item->kode_sub_anak_akun => "{$item->kode_sub_anak_akun} - {$item->nama_sub_anak_akun}"]
                        ))
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            $set('nama_akun', SubAnakAkun::where('kode_sub_anak_akun', $state)->first()?->nama_sub_anak_akun
                                ?? AnakAkun::where('kode_anak_akun', $state)->first()?->nama_anak_akun ?? '');
                        }),
                    TextInput::make('nama_akun')->label('Nama Akun')->required()->readOnly(),
                    TextInput::make('nama')->label('Nama')->columnSpanFull(),
                    TextInput::make('keterangan')->label('Keterangan')->columnSpanFull(),
                    TextInput::make('banyak')->label('Kuantitas (kosongkan jika tidak perlu)')->numeric(),
                    TextInput::make('harga')->label('Harga Satuan')->numeric()->prefix('Rp')->required(),
                    Select::make('map')->label('Posisi')
                        ->options(['d' => 'Debit', 'k' => 'Kredit'])->required(),
                ])
            ])
            ->fillForm(function (array $arguments) {
                $index = $arguments['index'] ?? null;
                if ($index !== null && isset($this->items[$index])) {
                    $item = $this->items[$index];
                    return [
                        'tgl'       => $item['tgl'],
                        'jurnal'    => $item['jurnal'],
                        'no_akun'   => $item['no_akun'],
                        'nama_akun' => $item['nama_akun'],
                        'nama'      => $item['nama'],
                        'keterangan' => $item['keterangan'],
                        'banyak'    => $item['banyak'],
                        'harga'     => $item['harga'],
                        'map'       => $item['map'],
                    ];
                }
                return [];
            })
            ->action(function (array $data, array $arguments): void {
                $index = $arguments['index'] ?? null;
                if ($index !== null && isset($this->items[$index])) {
                    $harga = (float) $data['harga'];
                    $total = $this->hitungTotal($data['banyak'], $harga);

                    $this->items[$index] = [
                        'tgl'        => $data['tgl'],
                        'jurnal'     => $data['jurnal'],
                        'no_akun'    => $data['no_akun'],
                        'nama_akun'  => $data['nama_akun'],
                        'nama'       => $data['nama'],
                        'keterangan' => $data['keterangan'],
                        'banyak'     => blank($data['banyak']) ? null : (float) $data['banyak'],
                        'harga'      => $harga,
                        'total'      => $total,
                        'map'        => strtolower($data['map']),
                    ];

                    $this->sortDraftItems();
                    $this->syncJurnalNumber();
                    $this->persistDraftState();
                    Notification::make()->title('Entri draft berhasil diperbarui')->success()->send();
                }
            });
    }

    public function saveJurnal(): void
    {
        if (empty($this->items) || !$this->isDraftBalanced()) return;

        try {
            DB::transaction(function () {
                foreach ($this->items as $item) {
                    JurnalModel::create([
                        'tgl'        => $item['tgl'],
                        'jurnal'     => $item['jurnal'],
                        'no_akun'    => $item['no_akun'],
                        'nama_akun'  => $item['nama_akun'],
                        'nama'       => $item['nama'],
                        'keterangan' => $item['keterangan'],
                        'banyak'     => isset($item['banyak']) && $item['banyak'] !== null && $item['banyak'] !== ''
                            ? $item['banyak'] : null,
                        'harga'      => $item['harga'],
                        'map'        => $item['map'],
                    ]);
                }
            });
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', title: 'Error Sistem', msg: 'Gagal menyimpan jurnal: ' . $e->getMessage());
            return;
        }

        session()->forget([
            'jurnal_draft_items',
            'jurnal_draft_kode',
            'jurnal_draft_tgl',
            'jurnal_draft_map',
            'jurnal_draft_harga',
            'jurnal_draft_banyak',
            'jurnal_draft_nama',
        ]);

        $this->items        = [];
        $this->wasBalanced  = false;
        $this->reset(['no_akun', 'nama_akun', 'nama', 'keterangan']);
        $this->harga        = '';
        $this->banyak       = '';
        $this->map          = 'd';
        $this->perPage      = 50;
        $this->hasMorePages = true;

        $this->syncJurnalNumber();
        $this->dispatch('toast', type: 'success', title: 'Jurnal Diposting!', msg: 'Semua entri berhasil disimpan ke database.');
    }

    public function resetForm(): void
    {
        $this->reset(['no_akun', 'nama_akun', 'nama', 'keterangan']);
        $this->harga  = '';
        $this->banyak = '';
        $this->map    = 'd';
        $this->persistDraftState();
    }

    public function toggleSelectAll(array $ids): void
    {
        if ($this->selectAll) {
            $this->selectedIds = [];
            $this->selectAll   = false;
        } else {
            $this->selectedIds = $ids;
            $this->selectAll   = true;
        }
    }

    public function toggleSelected(int $id): void
    {
        if (in_array($id, $this->selectedIds)) {
            $this->selectedIds = array_values(array_filter($this->selectedIds, fn($i) => $i !== $id));
        } else {
            $this->selectedIds[] = $id;
        }
        $this->selectAll = false;
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('toast', type: 'error', title: 'Tidak ada yang dipilih', msg: 'Centang minimal 1 baris terlebih dahulu.');
            return;
        }

        $count = count($this->selectedIds);

        try {
            JurnalModel::whereIn('id', $this->selectedIds)->delete();
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', title: 'Gagal Hapus', msg: $e->getMessage());
            return;
        }

        $this->selectedIds = [];
        $this->selectAll   = false;
        $this->dispatch('toast', type: 'success', title: 'Berhasil Dihapus', msg: "{$count} transaksi berhasil dihapus.");
        $this->dispatch('bulk-delete-done');
    }

    public function editHistoryAction(): Action
    {
        return Action::make('editHistory')
            ->modalHeading('Edit Transaksi Riwayat')
            ->modalSubmitActionLabel('Simpan Perubahan')
            ->form([
                Grid::make(2)->schema([
                    DatePicker::make('tgl')->label('Tanggal')->required()->native(false),
                    TextInput::make('jurnal')->label('No. Jurnal')->required(),
                    Select::make('no_akun')
                        ->label('Cari Nomor Akun')
                        ->required()
                        ->searchable()
                        ->options(fn() => SubAnakAkun::all()->mapWithKeys(
                            fn($item) => [$item->kode_sub_anak_akun => "{$item->kode_sub_anak_akun} - {$item->nama_sub_anak_akun}"]
                        ))
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            $set('nama_akun', SubAnakAkun::where('kode_sub_anak_akun', $state)->first()?->nama_sub_anak_akun
                                ?? AnakAkun::where('kode_anak_akun', $state)->first()?->nama_anak_akun ?? '');
                        }),
                    TextInput::make('nama_akun')->label('Nama Akun')->required()->readOnly(),
                    TextInput::make('nama')->label('Nama')->columnSpanFull(),
                    TextInput::make('keterangan')->label('Keterangan')->columnSpanFull(),
                    TextInput::make('banyak')->label('Kuantitas (kosongkan jika tidak perlu)')->numeric(),
                    TextInput::make('harga')->label('Harga Satuan')->numeric()->prefix('Rp')->required(),
                    Select::make('map')->label('Posisi')
                        ->options(['d' => 'Debit', 'k' => 'Kredit'])->required(),
                ])
            ])
            ->fillForm(fn(array $arguments) => JurnalModel::find($arguments['id'])?->toArray() ?? [])
            ->action(function (array $data, array $arguments): void {
                $record = JurnalModel::find($arguments['id']);
                if ($record) {
                    $data['banyak'] = (isset($data['banyak']) && $data['banyak'] !== '' && (float) $data['banyak'] > 0)
                        ? $data['banyak'] : null;
                    $record->update($data);
                    Notification::make()->title('Data riwayat berhasil diperbarui')->success()->send();
                }
            });
    }

    public function deleteHistoryAction(): Action
    {
        return Action::make('deleteHistory')
            ->requiresConfirmation()
            ->modalHeading('Hapus Transaksi')
            ->modalDescription('Yakin ingin menghapus data ini secara permanen?')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->color('danger')
            ->action(function (array $arguments): void {
                $record = JurnalModel::find($arguments['id']);
                if ($record) {
                    $record->delete();
                    Notification::make()->title('Data riwayat berhasil dihapus')->success()->send();
                }
            });
    }
}
