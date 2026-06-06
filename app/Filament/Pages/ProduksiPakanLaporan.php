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
    protected static string|UnitEnum|null $navigationGroup = 'Produksi & Kandang';
    protected static ?string $navigationLabel  = 'Produksi Pakan';
    protected static ?string $title            = 'Laporan Produksi Pakan';
    protected static ?string $slug             = 'produksi-pakan-laporan';
    protected string $view                     = 'filament.pages.produksi-pakan-laporan';
    protected static ?int $navigationSort = 3;

    /* ─── State ─────────────────────────────────────────────────────────── */
    public ?string        $selectedDate  = null;
    public ?ProduksiPakan $currentRecord = null;
    public array          $mentahState   = [];
    public array          $campuranState = [];
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

            $this->campuranState = $this->currentRecord->pakanCampurans
                ->map(fn($item) => $this->mapCampuranItemFromDb($item))
                ->toArray();

            $this->sortCampuranState();

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
            'nama_barang' => $item->barang?->nama_barang,
            'satuan'      => $item->barang?->satuan?->nama_satuan ?? '-',
            'nama'        => $item->barang?->nama_barang,
            'awal'         => (float) $item->stok_awal,
            'masuk'        => (float) $item->masuk,
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
            'nama' => $item->barang?->nama_barang,
            'satuan' => $item->barang?->satuan?->nama_satuan ?? 'kg',
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
        $semuaBarang = Barang::with(['satuan', 'kategori', 'subAnakAkun'])
            ->whereHas('kategori', function ($query) {
                $query->where(function ($q) {
                    $q->whereRaw('LOWER(nama_kategori) LIKE ?', ['%pakan%'])
                        ->orWhereRaw('LOWER(nama_kategori) LIKE ?', ['%pakan mentah%']);
                });
            })->get();

        // ── Cari produksi terakhir SEBELUM tanggal yang dipilih ──
        $produksiKemarin = ProduksiPakan::with([
            'pakanMentahs',
            'pakanCampurans',
        ])
            ->whereDate('tanggal_produksi', '<', $this->selectedDate)
            ->orderByDesc('tanggal_produksi')
            ->first();

        // Buat map: barang_id => stok_akhir dari produksi terakhir
        $stokAkhirKemarin = [];

        if ($produksiKemarin) {
            foreach ($produksiKemarin->pakanMentahs as $item) {
                $stokAkhirKemarin[$item->id_barang] = (float) $item->stok_akhir;
            }
            foreach ($produksiKemarin->pakanCampurans as $item) {
                $stokAkhirKemarin[$item->id_barang] = (float) $item->stok_akhir;
            }
        }

        // ── Fallback ke JurnalUmum jika belum pernah ada produksi sama sekali ──
        $stokMap = [];
        $belumAdaProduksi = empty($stokAkhirKemarin);

        if ($belumAdaProduksi) {
            // Pakai JurnalUmum sebagai stok awal pertama kali
            $kodeAkuns = $semuaBarang
                ->map(fn($b) => $b->subAnakAkun?->kode_sub_anak_akun)
                ->filter()->unique()->toArray();

            if (!empty($kodeAkuns)) {
                $transaksisGrouped = \App\Models\JurnalUmum::select(
                    'no_akun',
                    'map',
                    DB::raw('SUM(COALESCE(banyak, 0)) as total_qty')
                )
                    ->whereIn('no_akun', $kodeAkuns)
                    ->groupBy('no_akun', 'map')
                    ->get()
                    ->groupBy('no_akun');

                foreach ($semuaBarang as $barang) {
                    $kodeAkun = $barang->subAnakAkun?->kode_sub_anak_akun;
                    $totalQty = 0.0;

                    if ($kodeAkun && isset($transaksisGrouped[$kodeAkun])) {
                        foreach ($transaksisGrouped[$kodeAkun] as $trx) {
                            $isDebit = in_array(strtolower($trx->map), ['d', 'debit']);
                            $totalQty += $isDebit ? (float) $trx->total_qty : -(float) $trx->total_qty;
                        }
                    }

                    $stokMap[$barang->id] = max(0, $totalQty);
                }
            }
        }

        // ── Bangun state ──
        $this->mentahState   = [];
        $this->campuranState = [];

        foreach ($semuaBarang as $b) {
            $namaUpper     = strtoupper($b->nama_barang);
            $kategoriLower = strtolower($b->kategori->nama_kategori ?? '');
            $isCampuran    = str_contains($namaUpper, 'PULLET')
                || str_contains($namaUpper, 'PULET')
                || str_contains($namaUpper, 'LAYER');

            // Prioritas: stok akhir kemarin → fallback JurnalUmum → 0
            $stokAwal = (float) (
                $stokAkhirKemarin[$b->id]   // ada produksi kemarin
                ?? $stokMap[$b->id]          // fallback jurnal umum
                ?? 0
            );

            $base = [
                'id'        => null,
                'barang_id' => $b->id,
                'nama_barang' => $b->nama_barang,
                'satuan'      => $b->satuan?->nama_satuan ?? '-',
                'nama'        => $b->nama_barang,
                'awal'      => $stokAwal,
                'p'         => 0.0,
                'l1'        => 0.0,
                'l2'        => 0.0,
                'akhir'     => $stokAwal,
            ];

            if ($isCampuran) {
                $this->campuranState[] = array_merge($base, [
                    'masuk'  => 0.0,
                    'satuan' => $b->satuan?->nama_satuan ?? 'kg', // ← tambah ini
                ]);
            } else {
                $this->mentahState[] = array_merge($base, [
                    'masuk'        => 0.0,
                    'konversi_sak' => $this->getKonversiSak($b->id),
                    'p_sak'        => 0,
                    'l1_sak'       => 0,
                    'l2_sak'       => 0,
                ]);
            }
        }
        $this->sortCampuranState();
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
        if ($propertyName === 'selectedDate') return;
        if ($propertyName === 'keterangan') {
            $this->saveToSession();
            return;
        }

        // ── Mentah: input sak → konversi ke kg ──
        if (preg_match('/^mentahState\.(\d+)\.(p|l1|l2)_sak$/', $propertyName, $m)) {
            $idx     = (int) $m[1];
            $field   = $m[2];
            $faktor  = (float) ($this->mentahState[$idx]['konversi_sak'] ?? 1);
            $nilaiKg = (float) ($this->mentahState[$idx][$field . '_sak'] ?? 0) * $faktor;

            $stokAwal    = (float) ($this->mentahState[$idx]['awal']  ?? 0);
            $masukMentah = (float) ($this->mentahState[$idx]['masuk'] ?? 0);

            // ✅ masuk (kg) → sak agar satuan konsisten dengan stokAwal (sak)
            $masukSak = $faktor > 1 ? $masukMentah / $faktor : $masukMentah;

            $sudahTerpakai = 0.0;
            foreach (['p', 'l1', 'l2'] as $k) {
                if ($k !== $field) {
                    $sudahTerpakai += (float) ($this->mentahState[$idx][$k] ?? 0);
                }
            }

            // sudahTerpakai dalam kg, konversi ke sak
            $sudahTerpakaiSak = $faktor > 1 ? $sudahTerpakai / $faktor : $sudahTerpakai;
            $stokTersedia     = $stokAwal + $masukSak - $sudahTerpakaiSak;

            if ($nilaiKg > $stokTersedia * $faktor) {
                // batas dalam kg = stokTersedia (sak) × faktor
                $nilaiKgFinal  = max(0, $stokTersedia * $faktor);
                $nilaiSakFinal = $faktor > 1
                    ? round($nilaiKgFinal / $faktor, 4)
                    : $nilaiKgFinal;

                $this->mentahState[$idx][$field]          = $nilaiKgFinal;
                $this->mentahState[$idx][$field . '_sak'] = $nilaiSakFinal;

                Notification::make()
                    ->title('Stok Tidak Mencukupi')
                    ->body(
                        ($this->mentahState[$idx]['nama'] ?? 'Bahan') .
                            ": nilai dikoreksi ke " .
                            number_format($nilaiSakFinal, 2) .
                            " sak (maks stok tersedia). Silakan cek stok."
                    )
                    ->warning()
                    ->persistent()
                    ->send();
            } else {
                $this->mentahState[$idx][$field] = $nilaiKg;
            }
        }

        // ── Mentah: input langsung (konversi = 1) → sync ke _sak ──
        if (preg_match('/^mentahState\.(\d+)\.(p|l1|l2)$/', $propertyName, $m)) {
            $idx    = (int) $m[1];
            $field  = $m[2];
            $faktor = (float) ($this->mentahState[$idx]['konversi_sak'] ?? 1);

            if ($faktor <= 1) {
                $nilaiKg     = (float) ($this->mentahState[$idx][$field] ?? 0);
                $stokAwal    = (float) ($this->mentahState[$idx]['awal']  ?? 0);
                $masukMentah = (float) ($this->mentahState[$idx]['masuk'] ?? 0);
                // faktor = 1, jadi masukSak = masukMentah (tidak perlu konversi)

                $sudahTerpakai = 0.0;
                foreach (['p', 'l1', 'l2'] as $k) {
                    if ($k !== $field) {
                        $sudahTerpakai += (float) ($this->mentahState[$idx][$k] ?? 0);
                    }
                }
                $stokTersedia = $stokAwal + $masukMentah - $sudahTerpakai;

                if ($nilaiKg > $stokTersedia) {
                    $nilaiKgFinal = max(0, $stokTersedia);
                    $this->mentahState[$idx][$field]          = $nilaiKgFinal;
                    $this->mentahState[$idx][$field . '_sak'] = $nilaiKgFinal;

                    Notification::make()
                        ->title('Stok Tidak Mencukupi')
                        ->body(
                            ($this->mentahState[$idx]['nama'] ?? 'Bahan') .
                                ": nilai dikoreksi ke " .
                                number_format($nilaiKgFinal, 2) .
                                " (maks stok tersedia). Silakan cek stok."
                        )
                        ->warning()
                        ->persistent()
                        ->send();
                } else {
                    $this->mentahState[$idx][$field . '_sak'] = $nilaiKg;
                }
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

        foreach ($this->mentahState as $idx => $item) {
            $p     = (float) ($item['p']     ?? 0);
            $l1    = (float) ($item['l1']    ?? 0);
            $l2    = (float) ($item['l2']    ?? 0);
            $masuk = (float) ($item['masuk'] ?? 0);

            $konversi = (float) ($item['konversi_sak'] ?? 1);

            $pSak  = $konversi > 1 ? $p  / $konversi : $p;
            $l1Sak = $konversi > 1 ? $l1 / $konversi : $l1;
            $l2Sak = $konversi > 1 ? $l2 / $konversi : $l2;

            // ✅ masuk (kg) dikonversi ke sak agar satuan konsisten dengan awal (sak)
            $masukSak = $konversi > 1 ? $masuk / $konversi : $masuk;

            $this->mentahState[$idx]['akhir'] = max(0, (float) $item['awal'] + $masukSak - ($pSak + $l1Sak + $l2Sak));

            // totalP/L1/L2 tetap dalam kg — dipakai untuk mengisi masuk di campuran
            $totalP  += $p;
            $totalL1 += $l1;
            $totalL2 += $l2;
        }

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
                $this->mentahState[$idx]['masuk']  = (float) ($saved['masuk']  ?? 0);
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
        $this->keterangan = $draft['keterangan'] ?? $this->keterangan;
    }

    public function save(): void
    {
        if (!$this->canEdit) return;

        $adaNilaiMentah = collect($this->mentahState)->contains(
            fn($item) =>
            (float) ($item['p']  ?? 0) > 0 ||
                (float) ($item['l1'] ?? 0) > 0 ||
                (float) ($item['l2'] ?? 0) > 0
        );

        // ── Cek: apakah ADA SATU PUN nilai > 0 di seluruh campuranState? ──
        $adaNilaiCampuran = collect($this->campuranState)->contains(
            fn($item) =>
            (float) ($item['p']  ?? 0) > 0 ||
                (float) ($item['l1'] ?? 0) > 0 ||
                (float) ($item['l2'] ?? 0) > 0
        );

        // Tolak hanya jika KEDUANYA kosong total
        if (!$adaNilaiMentah && !$adaNilaiCampuran) {
            Notification::make()
                ->title('Data Masih Kosong')
                ->body('Harap isi minimal satu nilai di bahan mentah atau pakan campuran sebelum menyimpan.')
                ->warning()
                ->persistent()
                ->send();
            return;
        }


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
                $semuaInputMentah = $this->mentahState;

                foreach ($semuaInputMentah as $data) {
                    ProduksiPakanMentah::updateOrCreate(
                        [
                            'id_produksi_pakan' => $this->currentRecord->id,
                            'id_barang'         => $data['barang_id'],
                        ],
                        [
                            'stok_awal'     => (float) ($data['awal']  ?? 0),
                            'masuk'         => (float) ($data['masuk']  ?? 0),  // ← tambah
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

        // ── Tolak hanya jika TIDAK ADA SATUPUN nilai > 0 ──
        $adaNilaiMentah = collect($this->mentahState)->contains(
            fn($item) =>
            (float) ($item['p']  ?? 0) > 0 ||
                (float) ($item['l1'] ?? 0) > 0 ||
                (float) ($item['l2'] ?? 0) > 0
        );

        $adaNilaiCampuran = collect($this->campuranState)->contains(
            fn($item) =>
            (float) ($item['p']  ?? 0) > 0 ||
                (float) ($item['l1'] ?? 0) > 0 ||
                (float) ($item['l2'] ?? 0) > 0
        );

        if (!$adaNilaiMentah && !$adaNilaiCampuran) {
            Notification::make()
                ->title('Data Masih Kosong')
                ->body('Harap isi minimal satu nilai sebelum melakukan validasi.')
                ->warning()
                ->persistent()
                ->send();
            return;
        }

        // ── Semua lolos, lanjut simpan & kunci ──
        $this->save();

        $userId = Auth::id();

        // Buat jurnal pembantu otomatis saat divalidasi
        app(\App\Services\ProduksiPakanService::class)
            ->buatJurnalDariProduksi($this->currentRecord, $userId);

        $this->currentRecord->update([
            'validated_by' => Auth::user()->name,
            'validated_at' => now(),
        ]);

        $this->loadDataByDate();

        Notification::make()
            ->title('Laporan Divalidasi & Terkunci')
            ->success()
            ->send();
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

    public function autoFillKolom(string $jenis): void
    {
        if (!$this->canEdit) return;
        if (!in_array($jenis, ['p', 'l1', 'l2'])) return;

        $namaKandang = match ($jenis) {
            'p'  => ['PULLET', 'PULET'],
            'l1' => ['LAYER 1', 'L1'],
            'l2' => ['LAYER 2', 'L2'],
        };

        $komposisi = \App\Models\Komposisi::with('detailKomposisi')
            ->whereHas('barang', function ($q) use ($namaKandang) {
                $q->where(function ($q2) use ($namaKandang) {
                    foreach ($namaKandang as $nama) {
                        $q2->orWhereRaw('UPPER(nama_barang) LIKE ?', ["%{$nama}%"]);
                    }
                });
            })
            ->first();

        if (!$komposisi) {
            Notification::make()
                ->title('Komposisi Tidak Ditemukan')
                ->body('Belum ada komposisi untuk ' . strtoupper($jenis) . ' di master data.')
                ->warning()
                ->send();
            return;
        }

        $kuantitasMap = $komposisi->detailKomposisi
            ->pluck('kuantitas', 'id_barang')
            ->toArray();

        $adaYangDiisi    = false;
        $stokKurangItems = [];

        foreach ($this->mentahState as $idx => $item) {
            $kuantitasSak = (float) ($kuantitasMap[$item['barang_id']] ?? 0);
            if ($kuantitasSak <= 0) continue;

            if ((float) ($this->mentahState[$idx][$jenis . '_sak'] ?? 0) !== 0.0) continue;

            $konversi    = (float) ($this->mentahState[$idx]['konversi_sak'] ?? 1);
            $stokAwal    = (float) ($this->mentahState[$idx]['awal']         ?? 0);
            $masukMentah = (float) ($this->mentahState[$idx]['masuk']        ?? 0); // ← tambah

            $sudahTerpakaiSak = 0.0;
            foreach (['p', 'l1', 'l2'] as $k) {
                if ($k !== $jenis) {
                    $nilaiKgLain = (float) ($this->mentahState[$idx][$k] ?? 0);
                    $sudahTerpakaiSak += $konversi > 1 ? $nilaiKgLain / $konversi : $nilaiKgLain;
                }
            }

            // masuk juga dalam satuan sak (unit dasar mentah), ikut menambah stok tersedia
            $masukSak        = $konversi > 1 ? $masukMentah / $konversi : $masukMentah;
            $stokTersediaSak = $stokAwal + $masukSak - $sudahTerpakaiSak; // ← masuk ikut

            if ($kuantitasSak > $stokTersediaSak) {
                $nilaiSakFinal = max(0, $stokTersediaSak);
                $nilaiKgFinal  = $konversi > 1 ? $nilaiSakFinal * $konversi : $nilaiSakFinal;

                $stokKurangItems[] = sprintf(
                    '%s (butuh %s sak, tersedia %s sak)',
                    $item['nama'],
                    number_format($kuantitasSak, 2),
                    number_format($stokTersediaSak, 2)
                );
            } else {
                $nilaiSakFinal = $kuantitasSak;
                $nilaiKgFinal  = $konversi > 1 ? $kuantitasSak * $konversi : $kuantitasSak;
            }

            $this->mentahState[$idx][$jenis]          = $nilaiKgFinal;
            $this->mentahState[$idx][$jenis . '_sak'] = $nilaiSakFinal;

            $adaYangDiisi = true;
        }

        $this->recalculateAll();
        $this->saveToSession();

        $labelJenis = match ($jenis) {
            'p'  => 'Pullet',
            'l1' => 'Layer 1',
            'l2' => 'Layer 2',
        };

        if (!empty($stokKurangItems)) {
            Notification::make()
                ->title("⚠ Stok Tidak Mencukupi — {$labelJenis}")
                ->body(
                    "Bahan berikut stoknya kurang, diisi sesuai sisa stok:\n" .
                        implode("\n", $stokKurangItems) .
                        "\n\nSilakan cek dan tambah stok terlebih dahulu."
                )
                ->warning()
                ->persistent()
                ->send();
        }

        if ($adaYangDiisi) {
            Notification::make()
                ->title("Kolom {$labelJenis} Berhasil Diisi")
                ->body(empty($stokKurangItems)
                    ? "Semua bahan terisi sesuai komposisi."
                    : "Sebagian bahan diisi dengan stok yang tersedia.")
                ->color(empty($stokKurangItems) ? 'success' : 'info')
                ->send();
        } else {
            Notification::make()
                ->title("Kolom {$labelJenis} Sudah Terisi")
                ->body('Semua baris sudah diisi manual, tidak ada yang ditimpa.')
                ->info()
                ->send();
        }
    }

    public function incrementMentah(int $idx, string $field): void
    {
        if (!$this->canEdit) return;
        $step = 1; // kelipatan 1 sak
        $this->mentahState[$idx][$field] = (float)($this->mentahState[$idx][$field] ?? 0) + $step;
        $this->updated("mentahState.{$idx}.{$field}");
    }

    public function decrementMentah(int $idx, string $field): void
    {
        if (!$this->canEdit) return;
        $step = 1;
        $current = (float)($this->mentahState[$idx][$field] ?? 0);
        $this->mentahState[$idx][$field] = max(0, $current - $step);
        $this->updated("mentahState.{$idx}.{$field}");
    }

    private function sortCampuranState(): void
    {
        usort($this->campuranState, function ($a, $b) {
            return $this->urutanCampuran($a['nama']) <=> $this->urutanCampuran($b['nama']);
        });
    }

    private function urutanCampuran(string $nama): int
    {
        $nama = strtoupper($nama);
        if (str_contains($nama, 'PULLET') || str_contains($nama, 'PULET')) return 1;
        if (str_contains($nama, 'LAYER 1') || str_contains($nama, 'L1'))   return 2;
        if (str_contains($nama, 'LAYER 2') || str_contains($nama, 'L2'))   return 3;
        return 99;
    }
}
