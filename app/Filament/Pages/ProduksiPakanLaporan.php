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
use UnitEnum;

class ProduksiPakanLaporan extends Page
{
    use HasPageShield;

    // protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string|UnitEnum|null $navigationGroup = 'Kandang';
    protected static ?string $navigationLabel  = 'Produksi Pakan';
    protected static ?string $title            = 'Laporan Produksi Pakan';
    protected static ?string $slug             = 'produksi-pakan-laporan';
    protected string $view                     = 'filament.pages.produksi-pakan-laporan';

    /* ─── State ─────────────────────────────────────────────────────────── */
    public ?string        $selectedDate  = null;
    public ?ProduksiPakan $currentRecord = null;
    public array          $mentahState   = [];
    public array          $campuranState = [];
    public array          $karungState   = [];
    public ?string        $keterangan    = '';
    public bool           $isLocked      = false;

    /* ─── Status ────────────────────────────────────────────────────────── */
    public bool $isSuperAdmin       = false;
    public bool $isCreator          = false;
    public bool $isDraftSaved       = false;
    public bool $canEdit            = true;
    public bool $showSaveButton     = true;
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
            'date' => $this->selectedDate,
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
        if (!$this->selectedDate || !strtotime($this->selectedDate)) return;

        Session::forget($this->sessionKey());

        $this->loadDataByDate();
    }

    /* ═══════════════════════════════════════════════════════════════════════
    |  DATABASE & STATE LOADERS
    ═══════════════════════════════════════════════════════════════════════ */

    public function loadDataByDate(): void
    {
        if (!$this->selectedDate) return;

        $this->logInfo('Memuat data tanggal baru');

        $this->currentRecord = ProduksiPakan::with([
            'pakanMentahs.barang.kategori',
            'pakanMentahs.barang.satuan',
            'pakanCampurans.barang.satuan',
        ])->whereDate('tanggal_produksi', $this->selectedDate)->first();

        if (!$this->currentRecord) {
            // ── Belum ada data di DB ──
            $this->logInfo('DB Kosong: Membangun state awal dari Stok Kandang');
            $this->isDraftSaved = false;
            $this->isLocked     = false;
            $this->isCreator    = false;
            $this->keterangan   = '';

            $this->buildStateFromBarang();
            $this->restoreFromSession();
        } else {
            // ── Data ada di DB ──
            $this->logInfo('DB Terisi: Memulihkan data dari database');
            $this->isDraftSaved = true;
            $this->isLocked     = !empty($this->currentRecord->validated_by);
            $this->isCreator    = ($this->currentRecord->created_by === Auth::user()->name);
            $this->keterangan   = $this->currentRecord->keterangan ?? '';

            $allMentahs = $this->currentRecord->pakanMentahs;

            $this->mentahState = $allMentahs
                ->filter(fn($i) => strtolower($i->barang?->kategori?->nama_kategori ?? '') !== 'ayam')
                ->map(fn($item) => $this->mapMentahItemFromDb($item))
                ->values()->toArray();

            $this->karungState = $allMentahs
                ->filter(fn($i) => strtolower($i->barang?->kategori?->nama_kategori ?? '') === 'ayam')
                ->map(fn($item) => $this->mapMentahItemFromDb($item))
                ->values()->toArray();

            $this->campuranState = $this->currentRecord->pakanCampurans
                ->map(fn($item) => $this->mapCampuranItemFromDb($item))
                ->toArray();

            // JANGAN restore session saat data sudah ada di DB.
            // Session hanya untuk draft yang belum disimpan.
        }

        // recalculateAll hanya menghitung sisa akhir dari p/l1/l2 yang sudah ada,
        // TIDAK menimpa nilai p/l1/l2 itu sendiri.
        $this->recalculateAll();
        $this->computePermissions();
    }

    /**
     * Map baris mentah dari DB ke format state.
     * Kunci: p/l1/l2 diambil langsung dari DB (sudah dalam satuan dasar kg/pcs).
     * p_sak/l1_sak/l2_sak hanya untuk tampilan input sak — dihitung balik dari kg.
     */
    private function mapMentahItemFromDb($item): array
    {
        $konversi = $this->getKonversiSak($item->id_barang);
        $p  = (float) $item->keluar_pullet;
        $l1 = (float) $item->keluar_l1;
        $l2 = (float) $item->keluar_l2;

        // Hitung balik ke sak untuk tampilan input
        if ($konversi > 1) {
            $pSak  = round($p  / $konversi, 4);
            $l1Sak = round($l1 / $konversi, 4);
            $l2Sak = round($l2 / $konversi, 4);
        } else {
            // Tidak ada konversi sak, input langsung pakai p/l1/l2
            $pSak  = $p;
            $l1Sak = $l1;
            $l2Sak = $l2;
        }

        return [
            'id'           => $item->id,
            'barang_id'    => $item->id_barang,
            'nama'         => $item->barang?->nama_barang . ' (' . ($item->barang?->satuan?->nama_satuan ?? '-') . ')',
            'awal'         => (float) $item->stok_awal,
            'konversi_sak' => $konversi,
            'p_sak'        => $pSak,
            'l1_sak'       => $l1Sak,
            'l2_sak'       => $l2Sak,
            // p/l1/l2 = nilai final dalam satuan dasar (kg/pcs) — ini yang dipakai kalkulasi
            'p'            => $p,
            'l1'           => $l1,
            'l2'           => $l2,
            'akhir'        => (float) $item->stok_akhir,
        ];
    }

    private function mapCampuranItemFromDb($item): array
    {
        return [
            'id'        => $item->id,
            'barang_id' => $item->id_barang,
            'nama'      => $item->barang?->nama_barang . ' (' . ($item->barang?->satuan?->nama_satuan ?? '-') . ')',
            'awal'      => (float) $item->stok_awal,
            'masuk'     => (float) $item->masuk,
            'p'         => (float) $item->keluar_pullet,
            'l1'        => (float) $item->keluar_l1,
            'l2'        => (float) $item->keluar_l2,
            'akhir'     => (float) $item->stok_akhir,
        ];
    }

    private function buildStateFromBarang(): void
    {
        $kandangToko = IdentitasToko::whereRaw('LOWER(nama_toko) LIKE ?', ['%kandang%'])->first();

        $semuaBarang = Barang::with(['satuan', 'kategori'])
            ->whereHas('kategori', function ($query) {
                $query->where(function ($q) {
                    $q->whereRaw('LOWER(nama_kategori) LIKE ?', ['%pakan%'])
                        ->orWhereRaw('LOWER(nama_kategori) LIKE ?', ['%ayam%']);
                });
            })->get();

        $stokMap = [];
        if ($kandangToko) {
            $stokMap = StokBarangToko::where('toko_id', $kandangToko->id)
                ->whereIn('barang_id', $semuaBarang->pluck('id'))
                ->get()
                ->pluck('stok', 'barang_id')
                ->toArray();
        }

        $this->mentahState   = [];
        $this->campuranState = [];
        $this->karungState   = [];

        foreach ($semuaBarang as $b) {
            $namaUpper     = strtoupper($b->nama_barang);
            $kategoriLower = strtolower($b->kategori->nama_kategori ?? '');
            $isCampuran    = str_contains($namaUpper, 'PULLET')
                || str_contains($namaUpper, 'PULET')
                || str_contains($namaUpper, 'LAYER');
            $stokAwal = (float) ($stokMap[$b->id] ?? 0);

            $base = [
                'id'        => null,
                'barang_id' => $b->id,
                'nama'      => $b->nama_barang . ' (' . ($b->satuan?->nama_satuan ?? '-') . ')',
                'awal'      => $stokAwal,
                'p'         => 0.0,
                'l1'        => 0.0,
                'l2'        => 0.0,
                'akhir'     => $stokAwal,
            ];

            if ($kategoriLower === 'ayam') {
                $this->karungState[] = array_merge($base, [
                    'konversi_sak' => $this->getKonversiSak($b->id),
                    'p_sak'        => 0,
                    'l1_sak'       => 0,
                    'l2_sak'       => 0,
                ]);
            } elseif ($isCampuran) {
                $this->campuranState[] = array_merge($base, ['masuk' => 0.0]);
            } else {
                $this->mentahState[] = array_merge($base, [
                    'konversi_sak' => $this->getKonversiSak($b->id),
                    'p_sak'        => 0,
                    'l1_sak'       => 0,
                    'l2_sak'       => 0,
                ]);
            }
        }
    }

    private function getKonversiSak($barangId): float
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

        // ── CRITICAL FIX: Bail out jika yang berubah adalah selectedDate.
        //
        // Kenapa ini penting? Livewire memanggil updated() SEBELUM updatedSelectedDate().
        // Urutan eksekusinya:
        //   1. updated('selectedDate') → $this->selectedDate sudah = tanggal BARU
        //   2. Tanpa guard ini, saveToSession() akan menulis STATE LAMA ke KEY TANGGAL BARU
        //   3. updatedSelectedDate() → loadDataByDate() → restoreFromSession()
        //   4. Session tanggal baru "kebetulan" ada isinya (data lama) → state tercemar
        //
        // Dengan return di sini, kita serahkan sepenuhnya ke updatedSelectedDate().
        if ($propertyName === 'selectedDate') return;

        // ── Fix Minor: keterangan tidak perlu trigger recalculate,
        // cukup simpan ke session saja lalu selesai.
        if ($propertyName === 'keterangan') {
            $this->saveToSession();
            return;
        }

        // ── Mentah: input sak → konversi ke kg ──
        if (preg_match('/^mentahState\.(\d+)\.(p|l1|l2)_sak$/', $propertyName, $m)) {
            $idx    = (int) $m[1];
            $field  = $m[2];
            $faktor = (float) ($this->mentahState[$idx]['konversi_sak'] ?? 1);
            $this->mentahState[$idx][$field] = (float) ($this->mentahState[$idx][$field . '_sak'] ?? 0) * $faktor;
        }

        // ── Mentah: input langsung (konversi = 1) → sync ke _sak ──
        if (preg_match('/^mentahState\.(\d+)\.(p|l1|l2)$/', $propertyName, $m)) {
            $idx    = (int) $m[1];
            $field  = $m[2];
            $faktor = (float) ($this->mentahState[$idx]['konversi_sak'] ?? 1);
            if ($faktor <= 1) {
                $this->mentahState[$idx][$field . '_sak'] = (float) ($this->mentahState[$idx][$field] ?? 0);
            }
        }

        // ── Karung: input sak → konversi ke kg ──
        if (preg_match('/^karungState\.(\d+)\.(p|l1|l2)_sak$/', $propertyName, $m)) {
            $idx    = (int) $m[1];
            $field  = $m[2];
            $faktor = (float) ($this->karungState[$idx]['konversi_sak'] ?? 1);
            $this->karungState[$idx][$field] = (float) ($this->karungState[$idx][$field . '_sak'] ?? 0) * $faktor;
        }

        // ── Karung: input langsung → sync ke _sak ──
        if (preg_match('/^karungState\.(\d+)\.(p|l1|l2)$/', $propertyName, $m)) {
            $idx    = (int) $m[1];
            $field  = $m[2];
            $faktor = (float) ($this->karungState[$idx]['konversi_sak'] ?? 1);
            if ($faktor <= 1) {
                $this->karungState[$idx][$field . '_sak'] = (float) ($this->karungState[$idx][$field] ?? 0);
            }
        }

        $this->isRecalculating = true;
        $this->recalculateAll();
        $this->saveToSession();
        $this->isRecalculating = false;
    }

    /**
     * Hitung ulang sisa akhir (akhir) dari nilai p/l1/l2 yang SUDAH ADA di state.
     *
     * PENTING: fungsi ini TIDAK boleh menimpa nilai p/l1/l2 itu sendiri.
     * Konversi sak → kg dilakukan di updated(), bukan di sini.
     * Dengan begitu, saat loadDataByDate() mengisi p/l1/l2 dari DB lalu
     * memanggil recalculateAll(), nilai dari DB tidak akan tertimpa.
     */
    private function recalculateAll(): void
    {
        $totalP  = 0.0;
        $totalL1 = 0.0;
        $totalL2 = 0.0;

        // 1. Hitung sisa akhir mentah — baca p/l1/l2, JANGAN tulis ulang
        foreach ($this->mentahState as $idx => $item) {
            $p  = (float) ($item['p']  ?? 0);
            $l1 = (float) ($item['l1'] ?? 0);
            $l2 = (float) ($item['l2'] ?? 0);

            $this->mentahState[$idx]['akhir'] = max(0, (float) $item['awal'] - ($p + $l1 + $l2));

            $totalP  += $p;
            $totalL1 += $l1;
            $totalL2 += $l2;
        }

        // 2. Hitung sisa akhir karung — baca p/l1/l2, JANGAN tulis ulang
        foreach ($this->karungState as $idx => $item) {
            $p  = (float) ($item['p']  ?? 0);
            $l1 = (float) ($item['l1'] ?? 0);
            $l2 = (float) ($item['l2'] ?? 0);

            $this->karungState[$idx]['akhir'] = max(0, (float) $item['awal'] - ($p + $l1 + $l2));
        }

        // 3. Distribusi masuk ke pakan campuran & hitung sisa akhirnya
        foreach ($this->campuranState as $idx => $item) {
            $nama  = strtoupper($item['nama']);
            $masuk = 0.0;

            if (str_contains($nama, 'PULLET') || str_contains($nama, 'PULET')) {
                $masuk = $totalP;
            } elseif (str_contains($nama, 'LAYER 1') || str_contains($nama, 'L1')) {
                $masuk = $totalL1;
            } elseif (str_contains($nama, 'LAYER 2') || str_contains($nama, 'L2')) {
                $masuk = $totalL2;
            }

            $this->campuranState[$idx]['masuk'] = $masuk;

            $keluar = (float) ($item['p']  ?? 0)
                + (float) ($item['l1'] ?? 0)
                + (float) ($item['l2'] ?? 0);

            $this->campuranState[$idx]['akhir'] = max(0, (float) $item['awal'] + $masuk - $keluar);
        }

        $this->logInfo('Rekalkulasi selesai', [
            'totalP'  => $totalP,
            'totalL1' => $totalL1,
            'totalL2' => $totalL2,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════════════
    |  DRAFT & PERSISTENCE
    ═══════════════════════════════════════════════════════════════════════ */

    private function saveToSession(): void
    {
        Session::put($this->sessionKey(), [
            'mentah'     => collect($this->mentahState)->keyBy('barang_id')->toArray(),
            'campuran'   => collect($this->campuranState)->keyBy('barang_id')->toArray(),
            'karung'     => collect($this->karungState)->keyBy('barang_id')->toArray(),
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
                $this->mentahState[$idx]['p']      = (float) ($saved['p']      ?? 0);
                $this->mentahState[$idx]['l1']     = (float) ($saved['l1']     ?? 0);
                $this->mentahState[$idx]['l2']     = (float) ($saved['l2']     ?? 0);
                $this->mentahState[$idx]['p_sak']  = (float) ($saved['p_sak']  ?? 0);
                $this->mentahState[$idx]['l1_sak'] = (float) ($saved['l1_sak'] ?? 0);
                $this->mentahState[$idx]['l2_sak'] = (float) ($saved['l2_sak'] ?? 0);
            }
        }

        foreach ($this->campuranState as $idx => $item) {
            $saved = $draft['campuran'][$item['barang_id']] ?? null;
            if ($saved) {
                $this->campuranState[$idx]['p']  = (float) ($saved['p']  ?? 0);
                $this->campuranState[$idx]['l1'] = (float) ($saved['l1'] ?? 0);
                $this->campuranState[$idx]['l2'] = (float) ($saved['l2'] ?? 0);
            }
        }

        foreach ($this->karungState as $idx => $item) {
            $saved = $draft['karung'][$item['barang_id']] ?? null;
            if ($saved) {
                $this->karungState[$idx]['p_sak']  = (float) ($saved['p_sak']  ?? 0);
                $this->karungState[$idx]['l1_sak'] = (float) ($saved['l1_sak'] ?? 0);
                $this->karungState[$idx]['l2_sak'] = (float) ($saved['l2_sak'] ?? 0);
                $this->karungState[$idx]['p']      = (float) ($saved['p']      ?? 0);
                $this->karungState[$idx]['l1']     = (float) ($saved['l1']     ?? 0);
                $this->karungState[$idx]['l2']     = (float) ($saved['l2']     ?? 0);
            }
        }

        $this->keterangan = $draft['keterangan'] ?? $this->keterangan;
    }

    public function save(): void
    {
        if (!$this->canEdit) return;

        try {
            DB::transaction(function () {
                if (!$this->currentRecord) {
                    $this->currentRecord = ProduksiPakan::create([
                        'tanggal_produksi' => $this->selectedDate,
                        'created_by'       => Auth::user()->name,
                        'keterangan'       => $this->keterangan,
                    ]);
                } else {
                    $this->currentRecord->update(['keterangan' => $this->keterangan]);
                }

                $semuaInputMentah = array_merge($this->mentahState, $this->karungState);

                foreach ($semuaInputMentah as $data) {
                    ProduksiPakanMentah::updateOrCreate(
                        [
                            'id_produksi_pakan' => $this->currentRecord->id,
                            'id_barang'         => $data['barang_id'],
                        ],
                        [
                            'stok_awal'     => (float) ($data['awal']  ?? 0),
                            'keluar_pullet' => (float) ($data['p']     ?? 0),
                            'keluar_l1'     => (float) ($data['l1']    ?? 0),
                            'keluar_l2'     => (float) ($data['l2']    ?? 0),
                            'stok_akhir'    => (float) ($data['akhir'] ?? 0),
                        ]
                    );
                }

                foreach ($this->campuranState as $data) {
                    ProduksiPakanCampuran::updateOrCreate(
                        [
                            'id_produksi_pakan' => $this->currentRecord->id,
                            'id_barang'         => $data['barang_id'],
                        ],
                        [
                            'stok_awal'     => (float) ($data['awal']  ?? 0),
                            'masuk'         => (float) ($data['masuk'] ?? 0),
                            'keluar_pullet' => (float) ($data['p']     ?? 0),
                            'keluar_l1'     => (float) ($data['l1']    ?? 0),
                            'keluar_l2'     => (float) ($data['l2']    ?? 0),
                            'stok_akhir'    => (float) ($data['akhir'] ?? 0),
                        ]
                    );
                }
            });

            $this->logInfo('Berhasil simpan ke database');

            // Hapus session SEBELUM reload agar loadDataByDate() baca dari DB
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
        $this->currentRecord->update([
            'validated_by' => Auth::user()->name,
            'validated_at' => now(),
        ]);
        $this->loadDataByDate();
        Notification::make()->title('Laporan Divalidasi & Terkunci')->success()->send();
    }

    private function computePermissions(): void
    {
        // ── Super Admin: selalu bisa edit & validasi apapun kondisinya ──
        // Tidak ada batasan untuk super admin, termasuk data yang sudah terkunci.
        if ($this->isSuperAdmin) {
            $this->canEdit            = true;
            $this->showSaveButton     = true;
            $this->showValidateButton = !$this->isLocked && $this->currentRecord !== null;
            return;
        }

        // ── Data sudah divalidasi (terkunci permanen) ──
        // Tidak ada user biasa yang bisa mengubah apapun setelah ini.
        if ($this->isLocked) {
            $this->canEdit            = false;
            $this->showSaveButton     = false;
            $this->showValidateButton = false;
            return;
        }

        // ── Data sudah disimpan sebagai draft (status: menunggu validasi) ──
        // Di sinilah inti perubahan:
        //   - Creator (yang menginput) → TIDAK bisa edit lagi.
        //     Alasannya: data sudah "diserahkan" ke validator, tidak etis
        //     jika creator bisa diam-diam mengubah data tanpa sepengetahuan validator.
        //   - Non-creator (validator) → bisa edit & bisa klik tombol validasi.
        //     Validator perlu bisa koreksi jika ada kesalahan sebelum mengunci.
        if ($this->isDraftSaved) {
            if ($this->isCreator) {
                // Creator hanya bisa lihat, tidak bisa ubah apapun
                $this->canEdit            = false;
                $this->showSaveButton     = false;
                $this->showValidateButton = false;
            } else {
                // Validator bisa edit dan kunci data
                $this->canEdit            = true;
                $this->showSaveButton     = true;
                $this->showValidateButton = true;
            }
            return;
        }

        // ── Belum ada data tersimpan (canvas kosong / baru diisi) ──
        // Siapapun yang membuka halaman ini bisa mengisi dan menyimpan.
        $this->canEdit            = true;
        $this->showSaveButton     = true;
        $this->showValidateButton = false; // belum bisa validasi sebelum disimpan
    }
}
