<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use App\Models\IdentitasToko;
use App\Models\ProduksiPakan;
use App\Models\ProduksiPakanMentah;
use App\Models\ProduksiPakanCampuran;
use App\Models\Satuan;
use App\Models\SatuanKonversi;
use App\Models\StokBarangToko;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Support\Facades\Log;

class ProduksiPakanLaporan extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel  = 'Produksi Pakan';
    protected static ?string $title            = 'Laporan Produksi Pakan';
    protected static ?string $slug             = 'produksi-pakan-laporan';
    protected string $view                     = 'filament.pages.produksi-pakan-laporan';

    /* ─── State ─────────────────────────────────────────────────────────── */
    public ?string        $selectedDate  = null;
    public ?ProduksiPakan $currentRecord = null;
    public array          $mentahState   = [];
    public array          $campuranState = [];
    public array $karungState = [];
    public ?string        $keterangan    = '';
    public bool           $isLocked      = false;

    /* ─── Status ────────────────────────────────────────────────────────── */
    public bool $isSuperAdmin      = false;
    public bool $isCreator         = false;
    public bool $isDraftSaved      = false;
    public bool $canEdit           = true;
    public bool $showSaveButton    = true;
    public bool $showValidateButton = false;

    protected bool $isRecalculating = false;

    /* ═══════════════════════════════════════════════════════════════════════
    |  SISTEM LOGGING & SESSION
    ═══════════════════════════════════════════════════════════════════════ */

    private function sessionKey(): string
    {
        return 'pp_draft_' . Auth::id() . '_' . ($this->selectedDate ?? 'nodate');
    }

    private function logInfo($message, $data = [])
    {
        Log::info("[ProduksiPakan] $message", array_merge([
            'user' => Auth::user()->name,
            'date' => $this->selectedDate
        ], $data));
    }

    /* ═══════════════════════════════════════════════════════════════════════
    |  LIFECYCLE
    ═══════════════════════════════════════════════════════════════════════ */

    public function mount(): void
    {
        $this->isSuperAdmin = Auth::user()->hasRole('super_admin');
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadDataByDate();
    }

    public function updatedSelectedDate(): void
    {
        $this->loadDataByDate();
    }

    /* ═══════════════════════════════════════════════════════════════════════
    |  DATABASE & STATE LOADERS
    ═══════════════════════════════════════════════════════════════════════ */

    public function loadDataByDate(): void
    {
        if (!$this->selectedDate) return;

        $this->logInfo('Memuat data tanggal baru');

        // Ambil record header produksi
        $this->currentRecord = ProduksiPakan::with([
            'pakanMentahs.barang.kategori',
            'pakanMentahs.barang.satuan',
            'pakanCampurans.barang.satuan',
        ])->whereDate('tanggal_produksi', $this->selectedDate)->first();

        // JIKA DATA TIDAK DITEMUKAN (NULL)
        if (!$this->currentRecord) {
            $this->logInfo('DB Kosong: Membangun state awal dari Stok Kandang');
            $this->isDraftSaved = false;
            $this->isLocked     = false;
            $this->keterangan   = '';

            // Gunakan fungsi buildState untuk inisialisasi baris kosong/stok awal
            $this->buildStateFromBarang();

            // Pulihkan dari session jika ada draft yang belum disimpan
            $this->restoreFromSession();
        }
        // JIKA DATA DITEMUKAN
        else {
            $this->logInfo('DB Terisi: Memulihkan data dari database');
            $this->isDraftSaved = true;
            $this->isLocked     = !empty($this->currentRecord->validated_by);
            $this->isCreator    = ($this->currentRecord->created_by === Auth::user()->name);
            $this->keterangan   = $this->currentRecord->keterangan ?? '';

            // Ambil semua detail mentah (termasuk pakan & karung/ayam)
            $allMentahs = $this->currentRecord->pakanMentahs;

            // Pisahkan Mentah Biasa (Bukan kategori ayam)
            $this->mentahState = $allMentahs->filter(function ($i) {
                return strtolower($i->barang?->kategori?->nama_kategori ?? '') !== 'ayam';
            })->map(fn($item) => $this->mapMentahItemFromDb($item))->values()->toArray();

            // Pisahkan Karung (Kategori ayam)
            $this->karungState = $allMentahs->filter(function ($i) {
                return strtolower($i->barang?->kategori?->nama_kategori ?? '') === 'ayam';
            })->map(fn($item) => $this->mapMentahItemFromDb($item))->values()->toArray();

            // Pakan Campuran
            $this->campuranState = $this->currentRecord->pakanCampurans
                ->map(fn($item) => $this->mapCampuranItemFromDb($item))->toArray();

            if ($this->canEdit) {
                $this->restoreFromSession();
            }
        }

        $this->recalculateAll();
        $this->computePermissions();
    }

    private function mapMentahItemFromDb($item)
    {
        return [
            'id'            => $item->id,
            'barang_id'     => $item->id_barang,
            'nama'          => $item->barang?->nama_barang . ' (' . ($item->barang?->satuan?->nama_satuan ?? '-') . ')',
            'awal'          => (float) $item->stok_awal,
            'konversi_sak'  => $this->getKonversiSak($item->id_barang),
            'p_sak'         => 0,
            'l1_sak' => 0,
            'l2_sak' => 0, // Reset helper input
            'p'             => (float) $item->keluar_pullet,
            'l1'            => (float) $item->keluar_l1,
            'l2'            => (float) $item->keluar_l2,
            'akhir'         => (float) $item->stok_akhir,
        ];
    }

    private function mapCampuranItemFromDb($item)
    {
        return [
            'id'            => $item->id,
            'barang_id'     => $item->id_barang,
            'nama'          => $item->barang?->nama_barang . ' (' . ($item->barang?->satuan?->nama_satuan ?? '-') . ')',
            'awal'          => (float) $item->stok_awal,
            'masuk'         => (float) $item->masuk,
            'p'             => (float) $item->keluar_pullet,
            'l1'            => (float) $item->keluar_l1,
            'l2'            => (float) $item->keluar_l2,
            'akhir'         => (float) $item->stok_akhir,
        ];
    }

    private function buildStateFromBarang(): void
    {
        // 1. Identifikasi Toko Kandang
        $kandangToko = IdentitasToko::whereRaw('LOWER(nama_toko) LIKE ?', ['%kandang%'])->first();

        $semuaBarang = Barang::with(['satuan', 'kategori'])
            ->whereHas('kategori', function ($query) {
                // Kita bungkus dalam nested where agar logic OR tetap berada di dalam subquery EXISTS
                $query->where(function ($q) {
                    $q->whereRaw('LOWER(nama_kategori) LIKE ?', ['%pakan%'])
                        ->orWhereRaw('LOWER(nama_kategori) LIKE ?', ['%ayam%']);
                });
            })->get();

        // 3. Ambil Stok Realtime Kandang
        $stokMap = [];
        if ($kandangToko) {
            $stokMap = StokBarangToko::where('toko_id', $kandangToko->id)
                ->whereIn('barang_id', $semuaBarang->pluck('id'))
                ->get()
                ->pluck('stok', 'barang_id')
                ->toArray();
        }

        $this->mentahState = [];
        $this->campuranState = [];
        $this->karungState = [];

        foreach ($semuaBarang as $b) {
            $namaUpper = strtoupper($b->nama_barang);
            $kategoriLower = strtolower($b->kategori->nama_kategori ?? '');
            // Klasifikasi sederhana: Barang jadi mengandung kata Pullet/Layer
            $isCampuran = str_contains($namaUpper, 'PULLET') || str_contains($namaUpper, 'PULET') || str_contains($namaUpper, 'LAYER');
            $stokAwal = (float) ($stokMap[$b->id] ?? 0);

            $base = [
                'id'            => null,
                'barang_id'     => $b->id,
                'nama'          => $b->nama_barang . ' (' . ($b->satuan?->nama_satuan ?? '-') . ')',
                'awal'          => $stokAwal,
                'p' => 0.0,
                'l1' => 0.0,
                'l2' => 0.0,
                'akhir'         => $stokAwal,
            ];

            if ($kategoriLower === 'ayam') {
                // Masuk ke tabel Karung/Ayam
                $this->karungState[] = array_merge($base, [
                    'konversi_sak' => $this->getKonversiSak($b->id),
                    'p_sak' => 0,
                    'l1_sak' => 0,
                    'l2_sak' => 0
                ]);
            } elseif ($isCampuran) {
                $this->campuranState[] = array_merge($base, ['masuk' => 0.0]);
            } else {
                $this->mentahState[] = array_merge($base, [
                    'konversi_sak' => $this->getKonversiSak($b->id),
                    'p_sak' => 0,
                    'l1_sak' => 0,
                    'l2_sak' => 0
                ]);
            }
        }
    }

    private function getKonversiSak($barangId)
    {
        $satuanSak = Satuan::whereRaw('LOWER(nama_satuan) = ?', ['sak'])->first();
        if (!$satuanSak) return 1;

        $konversi = SatuanKonversi::where('id_barang', $barangId)
            ->where('id_satuan_asal', $satuanSak->id)
            ->aktif()
            ->first();

        return $konversi ? (float) $konversi->nilai_konversi : 1;
    }

    /* ═══════════════════════════════════════════════════════════════════════
    |  LOGIKA PERHITUNGAN & KONVERSI
    ═══════════════════════════════════════════════════════════════════════ */

    public function updated($propertyName): void
    {
        if (!$this->canEdit || $this->isRecalculating) return;

        // Konversi sak → kg untuk mentahState
        if (preg_match('/^mentahState\.(\d+)\.(p|l1|l2)_sak$/', $propertyName, $m)) {
            $idx      = $m[1];
            $fieldSak = $m[2] . '_sak';
            $fieldKg  = $m[2];
            $faktor   = (float) ($this->mentahState[$idx]['konversi_sak'] ?? 1);
            $this->mentahState[$idx][$fieldKg] = (float) $this->mentahState[$idx][$fieldSak] * $faktor;
        }

        // ← TAMBAH: Konversi sak → kg untuk karungState
        if (preg_match('/^karungState\.(\d+)\.(p|l1|l2)_sak$/', $propertyName, $m)) {
            $idx      = $m[1];
            $fieldSak = $m[2] . '_sak';
            $fieldKg  = $m[2];
            $faktor   = (float) ($this->karungState[$idx]['konversi_sak'] ?? 1);
            $this->karungState[$idx][$fieldKg] = (float) $this->karungState[$idx][$fieldSak] * $faktor;
        }

        // ← TAMBAH: Jika input karung langsung (tanpa konversi sak), tetap recalculate
        if (preg_match('/^karungState\.(\d+)\.(p|l1|l2)$/', $propertyName, $m)) {
            // Tidak perlu konversi, langsung lanjut ke recalculate
        }

        $this->isRecalculating = true;
        $this->recalculateAll();
        $this->saveToSession();
        $this->isRecalculating = false;
    }

    private function recalculateAll(): void
    {
        $totalP = 0.0;
        $totalL1 = 0.0;
        $totalL2 = 0.0;

        // 1. Hitung Bahan Mentah & Konversi ke Kg
        foreach ($this->mentahState as $idx => $item) {
            $p  = (float) ($item['p']  ?? 0);
            $l1 = (float) ($item['l1'] ?? 0);
            $l2 = (float) ($item['l2'] ?? 0);

            $totalKeluar = $p + $l1 + $l2;
            $this->mentahState[$idx]['akhir'] = max(0, (float)$item['awal'] - $totalKeluar);

            $totalP += $p;
            $totalL1 += $l1;
            $totalL2 += $l2;
        }

        foreach ($this->karungState as $idx => $item) {
            $faktor = (float)($item['konversi_sak'] ?? 1);

            if ($faktor > 1) {
                // Punya konversi sak: hitung kg dari input sak
                $this->karungState[$idx]['p']  = (float)$item['p_sak']  * $faktor;
                $this->karungState[$idx]['l1'] = (float)$item['l1_sak'] * $faktor;
                $this->karungState[$idx]['l2'] = (float)$item['l2_sak'] * $faktor;
            }
            // Jika faktor = 1, p/l1/l2 sudah diinput langsung → JANGAN overwrite

            $p_kg  = (float)$this->karungState[$idx]['p'];
            $l1_kg = (float)$this->karungState[$idx]['l1'];
            $l2_kg = (float)$this->karungState[$idx]['l2'];

            $this->karungState[$idx]['akhir'] = max(0, (float)$item['awal'] - ($p_kg + $l1_kg + $l2_kg));
        }

        // 2. Distribusi ke Pakan Campuran (Kolom MASUK)
        foreach ($this->campuranState as $idx => $item) {
            $nama = strtoupper($item['nama']);
            $masuk = 0.0;

            if (str_contains($nama, 'PULLET') || str_contains($nama, 'PULET')) $masuk = $totalP;
            elseif (str_contains($nama, 'LAYER 1') || str_contains($nama, 'L1')) $masuk = $totalL1;
            elseif (str_contains($nama, 'LAYER 2') || str_contains($nama, 'L2')) $masuk = $totalL2;

            $this->campuranState[$idx]['masuk'] = $masuk;

            $keluar = (float)($item['p'] ?? 0) + (float)($item['l1'] ?? 0) + (float)($item['l2'] ?? 0);

            // Perbaikan variabel: $totalMasuk didefinisikan agar tidak undefined
            $totalTersedia = (float)$item['awal'] + $masuk;
            $this->campuranState[$idx]['akhir'] = max(0, $totalTersedia - $keluar);
        }

        $this->logInfo('Rekalkulasi selesai', ['p' => $totalP, 'l1' => $totalL1]);
    }

    /* ═══════════════════════════════════════════════════════════════════════
    |  DRAFT & PERSISTENCE
    ═══════════════════════════════════════════════════════════════════════ */

    private function saveToSession(): void
    {
        Session::put($this->sessionKey(), [
            'mentah'    => collect($this->mentahState)->keyBy('barang_id')->toArray(),
            'campuran'  => collect($this->campuranState)->keyBy('barang_id')->toArray(),
            'karung'    => collect($this->karungState)->keyBy('barang_id')->toArray(), // ← TAMBAH INI
            'keterangan' => $this->keterangan,
        ]);
    }

    private function restoreFromSession(): void
    {
        $draft = Session::get($this->sessionKey());
        if (!$draft) return;

        foreach ($this->mentahState as $idx => $item) {
            $saved = $draft['mentah'][$item['barang_id']] ?? null;
            if ($saved) {
                $this->mentahState[$idx]['p']     = $saved['p'];
                $this->mentahState[$idx]['l1']    = $saved['l1'];
                $this->mentahState[$idx]['l2']    = $saved['l2'];
                $this->mentahState[$idx]['p_sak']  = $saved['p_sak']  ?? 0;
                $this->mentahState[$idx]['l1_sak'] = $saved['l1_sak'] ?? 0;
                $this->mentahState[$idx]['l2_sak'] = $saved['l2_sak'] ?? 0;
            }
        }

        foreach ($this->campuranState as $idx => $item) {
            $saved = $draft['campuran'][$item['barang_id']] ?? null;
            if ($saved) {
                $this->campuranState[$idx]['p']  = $saved['p'];
                $this->campuranState[$idx]['l1'] = $saved['l1'];
                $this->campuranState[$idx]['l2'] = $saved['l2'];
            }
        }

        // ← TAMBAH BLOK INI
        foreach ($this->karungState as $idx => $item) {
            $saved = $draft['karung'][$item['barang_id']] ?? null;
            if ($saved) {
                $this->karungState[$idx]['p_sak']  = $saved['p_sak']  ?? 0;
                $this->karungState[$idx]['l1_sak'] = $saved['l1_sak'] ?? 0;
                $this->karungState[$idx]['l2_sak'] = $saved['l2_sak'] ?? 0;
                $this->karungState[$idx]['p']      = $saved['p']  ?? 0;
                $this->karungState[$idx]['l1']     = $saved['l1'] ?? 0;
                $this->karungState[$idx]['l2']     = $saved['l2'] ?? 0;
            }
        }

        $this->keterangan = $draft['keterangan'] ?? $this->keterangan;
    }

    public function save(): void
    {
        if (!$this->canEdit) return;

        try {
            DB::transaction(function () {
                // 1. Upsert Header Produksi
                if (!$this->currentRecord) {
                    $this->currentRecord = ProduksiPakan::create([
                        'tanggal_produksi' => $this->selectedDate,
                        'created_by'       => Auth::user()->name,
                        'keterangan'       => $this->keterangan,
                    ]);
                } else {
                    $this->currentRecord->update(['keterangan' => $this->keterangan]);
                }

                // 2. Simpan Detail Mentah (Gabungan Bahan Baku & Karung)
                // Pastikan menggunakan variabel hasil merge di bawah ini
                $semuaInputMentah = array_merge($this->mentahState, $this->karungState);

                foreach ($semuaInputMentah as $data) {
                    ProduksiPakanMentah::updateOrCreate(
                        ['id_produksi_pakan' => $this->currentRecord->id, 'id_barang' => $data['barang_id']],
                        [
                            // PENTING: Gunakan (float) dan ?? 0 untuk mencegah pengiriman string kosong ke DB
                            'stok_awal'     => (float) ($data['awal'] ?? 0),
                            'keluar_pullet' => (float) ($data['p'] ?? 0),
                            'keluar_l1'     => (float) ($data['l1'] ?? 0),
                            'keluar_l2'     => (float) ($data['l2'] ?? 0),
                            'stok_akhir'    => (float) ($data['akhir'] ?? 0),
                        ]
                    );
                }

                // 3. Simpan Detail Campuran
                foreach ($this->campuranState as $data) {
                    ProduksiPakanCampuran::updateOrCreate(
                        ['id_produksi_pakan' => $this->currentRecord->id, 'id_barang' => $data['barang_id']],
                        [
                            'stok_awal'     => (float) ($data['awal'] ?? 0),
                            'masuk'         => (float) ($data['masuk'] ?? 0),
                            'keluar_pullet' => (float) ($data['p'] ?? 0),
                            'keluar_l1'     => (float) ($data['l1'] ?? 0),
                            'keluar_l2'     => (float) ($data['l2'] ?? 0),
                            'stok_akhir'    => (float) ($data['akhir'] ?? 0),
                        ]
                    );
                }
            });

            $this->logInfo('Berhasil simpan ke database');
            Session::forget($this->sessionKey());
            $this->loadDataByDate();
            Notification::make()->title('Data Berhasil Disimpan')->success()->send();
        } catch (\Exception $e) {
            Log::error("[ProduksiPakan] Gagal simpan: " . $e->getMessage());
            Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
        }
    }

    public function validateData(): void
    {
        if (!$this->showValidateButton) return;
        $this->save();
        $this->currentRecord->update(['validated_by' => Auth::user()->name, 'validated_at' => now()]);
        $this->loadDataByDate();
        Notification::make()->title('Laporan Divalidasi & Terkunci')->success()->send();
    }

    private function computePermissions(): void
    {
        if ($this->isSuperAdmin) {
            $this->canEdit = true;
            $this->showSaveButton = true;
            $this->showValidateButton = !$this->isLocked && $this->currentRecord !== null;
            return;
        }
        if ($this->isLocked || ($this->isDraftSaved && $this->isCreator)) {
            $this->canEdit = false;
            $this->showSaveButton = false;
            $this->showValidateButton = false;
            return;
        }
        $this->canEdit = true;
        $this->showSaveButton = true;
        $this->showValidateButton = $this->isDraftSaved && !$this->isCreator;
    }
}
