<?php

namespace App\Filament\Resources\Pembelians\Pages;

use App\Filament\Resources\Pembelians\PembeliansResource;
use App\Models\Barang;
use App\Models\DetailPembelian;
use App\Models\Pembelian as ModelsPembelian;
use App\Models\PembelianMetodePembayaran;
use App\Models\Supplier;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class Pembelian extends Page
{
    use WithFileUploads;

    protected static string $resource = PembeliansResource::class;

    protected string $view = 'filament.resources.pembelians.pages.pembelian';

    protected static ?string $title = '';

    // =====================================
    // 1. STATE / VARIABEL: HEADER PEMBELIAN
    // =====================================
    public $nomor_nota;
    public $created_by;
    public $created_by_name;
    public $tanggal;

    public $supplier_id = '';
    public $supplier_name;
    public $supplier_phone;
    public $supplier_address;
    public $is_new_supplier = false;

    public $status = ModelsPembelian::STATUS_DRAFT;
    public $catatan;
    public $foto_nota = [];

    // =====================================
    // 2. STATE / VARIABEL: DETAIL BARANG
    // =====================================
    public $items = [];

    // =====================================
    // 3. STATE / VARIABEL: NOMINAL GLOBAL
    // =====================================
    public $sub_total = 0;
    public $total_diskon = null;
    public $total_ppn    = null;
    public $ongkir       = null;
    public $biaya_lain   = null;

    // =====================================
    // 4. STATE / VARIABEL: PEMBAYARAN KASIR
    // =====================================
    public $payment_method    = PembelianMetodePembayaran::METODE_TUNAI;
    public $payment_amount    = null;
    public $tanggal_bayar;
    public $payment_reference = '';
    public $payment_catatan   = '';

    public function mount(): void
    {
        $this->created_by      = auth()->id();
        $this->created_by_name = auth()->user()->name ?? 'User';
        $this->tanggal         = now()->format('Y-m-d');
        $this->tanggal_bayar   = now()->format('Y-m-d');

        $this->addItem();
    }

    // =====================================
    // HANDLER SUPPLIER
    // =====================================
    public function updatedSupplierId($value): void
    {
        $supplier = Supplier::find($value);
        if ($supplier) {
            $this->supplier_name    = $supplier->nama;
            $this->supplier_phone   = $supplier->telepon;
            $this->supplier_address = $supplier->alamat;
        } else {
            $this->reset(['supplier_name', 'supplier_phone', 'supplier_address']);
        }
    }

    // =====================================
    // HANDLER BARANG (ITEMS / KERANJANG)
    // =====================================

    public function addItem(): void
    {
        $this->items[] = [
            'barang_id'   => '',
            'kode_barang' => '',
            'nama_barang' => '',
            'satuan'      => '',
            'qty'         => 1,
            'harga_beli'  => null,
            'diskon'      => null,
            'subtotal'    => 0,
            'catatan'     => '',
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) <= 1) return;

        // Kurangi sub_total dengan subtotal baris yang dihapus
        $this->sub_total = max(0, $this->sub_total - floatval($this->items[$index]['subtotal'] ?? 0));

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * Dipanggil otomatis Livewire saat data dalam $items berubah.
     * PERBAIKAN: update sub_total hanya dengan selisih (tidak loop ulang semua item).
     */
    public function updatedItems($value, $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) < 2) return;

        [$index, $field] = [$parts[0], $parts[1]];

        // Saat barang dipilih dari dropdown
        if ($field === 'barang_id' && !empty($value)) {
            $barang = Barang::with('satuan')->find($value);
            if ($barang) {
                $this->items[$index]['kode_barang'] = $barang->kode_barang;
                $this->items[$index]['nama_barang'] = $barang->nama_barang;
                $this->items[$index]['satuan']      = is_object($barang->satuan)
                    ? ($barang->satuan->nama ?? $barang->satuan->keterangan ?? 'Unit')
                    : ($barang->satuan ?? 'Unit');
                $this->items[$index]['harga_beli']  = $barang->harga_beli;
            }
        }

        $qty    = $this->parseNumber($this->items[$index]['qty']        ?? 0);
        $harga  = $this->parseNumber($this->items[$index]['harga_beli'] ?? 0);
        $diskon = $this->parseNumber($this->items[$index]['diskon']      ?? 0);

        $subtotalLama = floatval($this->items[$index]['subtotal'] ?? 0);

        // PERBAIKAN: max(0,...) agar subtotal tidak pernah negatif
        $subtotalBaru = max(0.0, ($qty * $harga) - $diskon);

        $this->items[$index]['subtotal'] = $subtotalBaru;

        // PERBAIKAN: update sub_total hanya dengan selisih, bukan loop ulang
        $this->sub_total = max(0.0, $this->sub_total - $subtotalLama + $subtotalBaru);
    }

    /**
     * Fungsi ini tetap tersedia sebagai fallback / rekonsiliasi manual.
     * Misalnya dipanggil setelah import data atau operasi bulk.
     */
    public function recalculateSubTotal(): void
    {
        $this->sub_total = array_reduce(
            $this->items,
            fn($carry, $item) => $carry + floatval($item['subtotal'] ?? 0),
            0.0
        );
    }

    // =====================================
    // KALKULASI GRAND TOTAL
    // =====================================

    /**
     * PERBAIKAN: Pakai #[Computed] agar Livewire cache hasil kalkulasi
     * per siklus render — tidak dihitung ulang tiap dipanggil di Blade.
     * Di Blade gunakan: $this->grandTotal  (bukan getGrandTotalProperty())
     */
    #[Computed]
    public function grandTotal(): float
    {
        return max(
            0.0,
            floatval($this->sub_total)
                - $this->parseNumber($this->total_diskon)
                + $this->parseNumber($this->total_ppn)
                + $this->parseNumber($this->ongkir)
                + $this->parseNumber($this->biaya_lain)
        );
    }

    /**
     * Alias agar kode lama yang memanggil getGrandTotalProperty() tetap jalan
     * tanpa perlu refactor Blade sekarang.
     */
    public function getGrandTotalProperty(): float
    {
        return $this->grandTotal();
    }

    public function setBayarPas(): void
    {
        $grand = $this->grandTotal();
        $this->payment_amount = $grand > 0 ? $grand : null;
    }

    // =====================================
    // HELPER: PARSE NUMBER
    // =====================================

    /**
     * Membersihkan format angka ribuan Indonesia (titik) & desimal (koma)
     * sehingga PHP bisa menghitungnya secara matematis.
     *
     * Contoh input yang ditangani:
     *   "1.500.000"   → 1500000.0
     *   "1.500,50"    → 1500.5
     *   "1500000"     → 1500000.0
     *   ""  / null    → 0.0
     */
    private function parseNumber(mixed $value): float
    {
        if (is_null($value) || $value === '') return 0.0;
        if (is_numeric($value)) return (float) $value;

        $str = (string) $value;

        // Deteksi apakah koma adalah desimal atau ribuan
        $lastComma = strrpos($str, ',');
        $lastDot   = strrpos($str, '.');

        if ($lastComma !== false && $lastDot !== false) {
            // Keduanya ada → yang terakhir adalah desimal
            if ($lastComma > $lastDot) {
                // Format: 1.500,50  → hapus titik, ganti koma jadi titik
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                // Format: 1,500.50  → hapus koma saja
                $str = str_replace(',', '', $str);
            }
        } elseif ($lastComma !== false) {
            // Hanya koma → cek apakah desimal atau ribuan
            $afterComma = substr($str, $lastComma + 1);
            if (strlen($afterComma) === 3 && !str_contains(substr($str, 0, $lastComma), '.')) {
                // Kemungkinan ribuan: "1,500" → hapus koma
                $str = str_replace(',', '', $str);
            } else {
                // Desimal: "1500,50" → ganti koma jadi titik
                $str = str_replace(',', '.', $str);
            }
        } elseif ($lastDot !== false) {
            // Hanya titik → cek apakah desimal atau ribuan
            $afterDot = substr($str, $lastDot + 1);
            if (strlen($afterDot) === 3 && substr_count($str, '.') === 1 && !str_contains($str, ',')) {
                // Ambigu: "1.500" bisa ribuan atau desimal.
                // Karena konteks Indonesia → anggap ribuan, hapus titik.
                $str = str_replace('.', '', $str);
            }
            // Jika "1.5" (bukan 3 digit) → biarkan, sudah format desimal valid
        }

        return (float) ($str ?: 0);
    }

    // =====================================
    // FUNGSI SIMPAN TRANSAKSI
    // =====================================
    public function simpan(): void
    {
        $this->validate([
            'nomor_nota'           => 'required',
            'tanggal'              => 'required|date',
            'supplier_id'          => 'required_without:is_new_supplier',
            'supplier_name'        => 'required_if:is_new_supplier,true',
            'items.*.barang_id'    => 'required',
            'items.*.qty'          => 'required|numeric|min:0.01',
            'items.*.harga_beli'   => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // 1. Simpan Supplier Baru (jika mode input manual)
            $final_supplier_id = $this->supplier_id;
            if ($this->is_new_supplier) {
                $newSupplier = Supplier::create([
                    'nama'       => $this->supplier_name,
                    'telepon'    => $this->supplier_phone,
                    'alamat'     => $this->supplier_address,
                    'created_by' => $this->created_by,
                ]);
                $final_supplier_id = $newSupplier->id;
            }

            // 2. Upload Foto
            $paths = [];
            foreach ($this->foto_nota as $foto) {
                $paths[] = $foto->store('pembelian', 'public');
            }

            // 3. Tentukan Status Berdasarkan Pembayaran
            $grand   = $this->grandTotal();
            $dibayar = $this->parseNumber($this->payment_amount);

            $this->status = match (true) {
                $grand > 0 && $dibayar >= $grand => ModelsPembelian::STATUS_LUNAS,
                $dibayar > 0 && $dibayar < $grand => ModelsPembelian::STATUS_CICILAN,
                default => ModelsPembelian::STATUS_HUTANG,
            };

            // 4. Simpan Header Pembelian
            $pembelian = ModelsPembelian::create([
                'nomor_nota'       => $this->nomor_nota,
                'created_by'       => $this->created_by,
                'tanggal'          => $this->tanggal,
                'supplier_id'      => $final_supplier_id,
                'supplier_name'    => $this->supplier_name,
                'supplier_phone'   => $this->supplier_phone,
                'supplier_address' => $this->supplier_address,
                'status'           => $this->status,
                'catatan'          => $this->catatan,
                'foto'             => !empty($paths) ? $paths : null,
                'sub_total'        => $this->sub_total,
                'total_diskon'     => $this->parseNumber($this->total_diskon),
                'total_ppn'        => $this->parseNumber($this->total_ppn),
                'ongkir'           => $this->parseNumber($this->ongkir),
                'biaya_lain'       => $this->parseNumber($this->biaya_lain),
                'grand_total'      => $grand,
            ]);

            // 5. Simpan Detail Barang
            $detailData = [];
            foreach ($this->items as $item) {
                if (empty($item['barang_id'])) continue;
                $detailData[] = [
                    'pembelian_id' => $pembelian->id,
                    'barang_id'    => $item['barang_id'],
                    'kode_barang'  => $item['kode_barang'],
                    'nama_barang'  => $item['nama_barang'],
                    'satuan'       => $item['satuan'],
                    'qty'          => $this->parseNumber($item['qty']),
                    'harga_beli'   => $this->parseNumber($item['harga_beli']),
                    'diskon'       => $this->parseNumber($item['diskon']),
                    'subtotal'     => $this->parseNumber($item['subtotal']),
                    'catatan'      => $item['catatan'] ?? '',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }

            // PERBAIKAN: insert sekaligus lebih cepat dari loop create()
            if (!empty($detailData)) {
                DetailPembelian::insert($detailData);
            }

            // 6. Simpan Pembayaran
            if ($dibayar > 0) {
                PembelianMetodePembayaran::create([
                    'pembelian_id'     => $pembelian->id,
                    'created_by'       => $this->created_by,
                    'tanggal_bayar'    => $this->tanggal_bayar,
                    'amount'           => $dibayar,
                    'payment_method'   => $this->payment_method,
                    'reference_number' => $this->payment_reference,
                    'catatan'          => $this->payment_catatan,
                ]);
            }

            DB::commit();

            Notification::make()
                ->title('Transaksi Berhasil!')
                ->body('Data pembelian dan pembayaran telah tercatat.')
                ->success()
                ->send();

            $this->redirect(PembeliansResource::getUrl('index'));
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
