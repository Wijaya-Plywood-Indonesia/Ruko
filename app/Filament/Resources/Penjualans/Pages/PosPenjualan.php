<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Filament\Resources\Penjualans\PenjualanResource;
use App\Models\Barang;
use App\Models\IdentitasToko;
use App\Models\Pembeli;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\RekeningPerusahaan;
use App\Models\StokBarangToko;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class PosPenjualan extends Page
{
    protected static string $resource = PenjualanResource::class;
    protected string $view = 'filament.resources.penjualans.pages.pos-penjualan';

    /* ================= identitas Toko ================= */
    public ?int $toko_id = null;
    public ?string $kodeToko = null;
    public $daftarToko = [];

    /* ================= STATE ================= */

    public string $search = '';
    public Collection $searchResults;
    public array $cart = [];

    public int $is_member = 0;

    /* ================= CUSTOMER ================= */
    public string $searchCustomer = '';
    public $customerResults = [];
    public ?int $pembeli_id = null;
    public string $nama_customer = '';
    public string $alamat = '';
    public string $telepon = '';

    /* ================= PEMBAYARAN ================= */
    public string $metode_pembayaran = 'TUNAI';
    public int $bayar = 0;

    public ?int $rekening_perusahaan_id = null;
    public $rekeningPerusahaan = [];

    /* ================= PENGIRIMAN ================= */
    public string $metode_pengiriman = 'DIBAWA_SENDIRI';
    public ?string $kendaraan = null;
    public ?string $plat_kendaraan = null;
    public ?string $nama_sopir = null;
    public $no_nota;
    public ?string $tanggal = null;
    public ?string $keterangan_pembayaran = null;
    public ?string $keterangan_nota = null;

    /* ================= MOUNT ================= */
    public function mount(): void
    {
        $this->searchResults = collect();
        $user = auth()->user();

        // Ambil toko dari tabel list_akun
        $tokoUser = $user->tokoUtama()->first();

        if ($tokoUser) {
            $this->toko_id = $tokoUser->id_toko;
            $this->kodeToko = $tokoUser->toko->kode_toko;
            $this->no_nota = $this->generateNoNota();
        }

        $this->tanggal = now();
        // dd($this->tanggal);
    }


    public function generateNoNota()
    {
        // Jika belum pilih toko → aman fallback
        if (!$this->toko_id) {
            return 'XXX-000001';
        }

        // Ambil data toko
        $toko = IdentitasToko::find($this->toko_id);

        // Jika tidak ada kode_toko → fallback
        $prefix = ($toko?->kode_toko ?? 'XXX') . '-';

        // Ambil nota terakhir untuk toko ini saja
        $last = Penjualan::where('no_nota', 'LIKE', $prefix . '%')
            ->orderBy('id', 'DESC')
            ->first();

        if (!$last) {
            return $prefix . '000001';
        }

        $lastNumber = (int) str_replace($prefix, '', $last->no_nota);
        $newNumber = $lastNumber + 1;

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }
    public function updatedTokoId()
    {
        $this->no_nota = $this->generateNoNota();
    }
    /* ================= SEARCH BARANG ================= */
    public bool $showDropdown = false;

    public function updatedSearch(): void
    {
        if (strlen($this->search) < 1) {
            $this->searchResults = collect();
            return;
        }

        $toko = $this->toko_id;

        $t = (new StokBarangToko)->getTable(); // otomatis ambil 'stok_barang_toko'

        $this->searchResults = Barang::query()
            ->leftJoin($t, function ($q) use ($t, $toko) {
                $q->on("$t.barang_id", '=', 'barangs.id')
                    ->where("$t.toko_id", $toko);
            })
            ->select(
                'barangs.*',
                DB::raw("COALESCE($t.stok, 0) as stok_aktual")
            )
            ->where(function ($query) {
                $query->where('barangs.nama_barang', 'like', "%{$this->search}%")
                    ->orWhere('barangs.barcode', 'like', "%{$this->search}%");
            })
            ->limit(8)
            ->get();
    }

    public function openDropdown(): void
    {
        $this->showDropdown = true;
    }

    public function closeDropdown(): void
    {
        $this->showDropdown = false;
    }

    public function selectBarang(int $id): void
    {
        $barang = Barang::find($id);
        if (!$barang)
            return;

        $stokToko = StokBarangToko::where('barang_id', $id)
            ->where('toko_id', $this->toko_id)
            ->first();

        $stok = $stokToko?->stok ?? 0;

        if ($stok < 0.01) {
            $namaToko = IdentitasToko::find($this->toko_id)?->nama_toko ?? 'Toko';
            Notification::make()
                ->title('Stok barang habis')
                ->body("Barang {$barang->nama_barang} habis di {$namaToko}")
                ->danger()
                ->send();
            return;
        }

        if (isset($this->cart[$id])) {
            if ($this->cart[$id]['qty'] + 1 > $stok) {
                Notification::make()
                    ->title('Stok tidak mencukupi')
                    ->warning()
                    ->send();
                return;
            }
            $this->cart[$id]['qty']++;
        } else {
            $this->cart[$id] = [
                'barang_id' => $barang->id,
                'nama_barang' => $barang->nama_barang,
                'satuan' => $barang->satuan?->nama_satuan ?? '-',
                'qty' => 1,
                'harga_awal' => (int) $barang->harga_jual,
                'harga_jual' => (int) $barang->harga_jual,
                'potongan' => $this->is_member ? 5000 : 0,
                'total_potongan' => $this->is_member ? 5000 : 0,
                'subtotal' => 0,
            ];
        }

        $this->updateSubtotal($id);
        $this->search = '';
        $this->searchResults = collect();
        $this->showDropdown = false;
    }

    /* ================= CART ================= */
    // public function updateQty(int $id): void
    public function updateQty(int $id): void
    {
        if (!isset($this->cart[$id])) {
            return;
        }

        $stock = (float) (StokBarangToko::where('barang_id', $id)
            ->where('toko_id', $this->toko_id)
            ->value('stok') ?? 0);

        // $stock = Barang::find($id)?->stok_toko?->stok ?? 0;
        $qty = max(0.01, (float) $this->cart[$id]['qty']);

        if ($qty > $stock) {
            Notification::make()
                ->title('Stok Maksimum Tercapai')
                ->body("Jumlah maksimal untuk barang ini adalah {$stock}.")
                ->warning()
                ->send();

            $qty = $stock;
        }

        $this->cart[$id]['qty'] = $qty;
        $this->cart[$id]['total_potongan'] =
            $this->cart[$id]['potongan'] * $qty;

        $this->updateSubtotal($id);
    }

    public function incrementQty(int $id): void
    {
        $this->cart[$id]['qty'] = round((float) $this->cart[$id]['qty'] + 1, 2);
        $this->updateQty($id);
    }

    public function decrementQty(int $id): void
    {
        if ((float) $this->cart[$id]['qty'] <= 0.01) {
            Notification::make()
                ->title('Minimal Jumlah Barang')
                ->body('Minimal jumlah barang adalah 0.01.')
                ->danger()
                ->send();
            return;
        }

        $this->cart[$id]['qty'] = round((float) $this->cart[$id]['qty'] - 1, 2);
        $this->updateQty($id);
    }

    public function removeFromCart(int $id): void
    {
        unset($this->cart[$id]);
    }

    public function updatePotongan(int $id): void
    {
        if (!isset($this->cart[$id])) {
            return;
        }

        $potongan = max(0, (float) $this->cart[$id]['potongan']);
        $qty = max(0.01, (float) $this->cart[$id]['qty']);

        $this->cart[$id]['potongan'] = $potongan;
        $this->cart[$id]['total_potongan'] = $potongan * $qty;

        $this->updateSubtotal($id);
    }

    protected function updateSubtotal(int $id): void
    {
        if (!isset($this->cart[$id])) {
            return;
        }

        $item = $this->cart[$id];

        $this->cart[$id]['subtotal'] =
            ($item['harga_jual'] * $item['qty']) - ($item['total_potongan'] ?? 0);
    }

    /* ================= CUSTOMER SEARCH ================= */
    public function updatedSearchCustomer(): void
    {
        if (strlen($this->searchCustomer) < 2) {
            $this->customerResults = [];
            return;
        }

        $this->customerResults = Pembeli::query()
            ->where('nama', 'like', "%{$this->searchCustomer}%")
            ->orWhere('telepon', 'like', "%{$this->searchCustomer}%")
            ->orWhere('nik', 'like', "%{$this->searchCustomer}%")
            ->limit(5)
            ->get();
    }

    public function selectCustomer(int $id): void
    {
        $pembeli = Pembeli::findOrFail($id);

        $this->pembeli_id = $pembeli->id;
        $this->nama_customer = $pembeli->nama;
        $this->alamat = $pembeli->alamat;
        $this->telepon = $pembeli->telepon;

        $this->customerResults = [];
        $this->searchCustomer = '';
    }

    /* ================= PEMBAYARAN ================= */
    public function updatedMetodePembayaran(): void
    {
        if ($this->metode_pembayaran === 'TRANSFER') {
            $this->rekeningPerusahaan = RekeningPerusahaan::all();
        } else {
            $this->rekeningPerusahaan = [];
            $this->rekening_perusahaan_id = null;
        }
    }

    /* ================= COMPUTED ================= */
    public function getTotalProperty(): int
    {
        return collect($this->cart)->sum(fn($i) => $i['subtotal'] ?? 0);
    }

    public function getKembalianProperty(): int
    {
        return max(($this->bayar ?? 0) - $this->total, 0);
    }

    /* ================= MEMBER ================= */
    public function updatedIsMember($value): void
    {
        $this->is_member = (bool) $value;

        if (!$this->is_member) {
            $this->reset([
                'searchCustomer',
                'customerResults',
                'pembeli_id',
                'nama_customer',
                'alamat',
                'telepon',
            ]);
        }
    }

    /* ================= PENGIRIMAN ================= */
    public function updatedMetodePengiriman(): void
    {
        if ($this->metode_pengiriman === 'DIBAWA_SENDIRI') {
            $this->kendaraan = null;
            $this->plat_kendaraan = null;
            $this->nama_sopir = null;
        }
    }
    public function updateHargaJual(int $id): void
    {
        if (!isset($this->cart[$id])) {
            return;
        }

        $harga = (int) ($this->cart[$id]['harga_jual'] ?? 0);

        // minimal 0 biar aman
        $harga = max(0, $harga);

        $this->cart[$id]['harga_jual'] = $harga;

        // hitung ulang subtotal
        $this->updateSubtotal($id);
    }

    /* ================= SIMPAN ================= */
    public function simpanPenjualan(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Keranjang Kosong')
                ->body('Silahkan isi keranjang terlebih dahulu')
                ->danger()
                ->send();
            return;
        }

        if ($this->bayar < $this->total || $this->total <= 0) {
            Notification::make()
                ->title('Pembayaran Kurang')
                ->body('Nominal pembayaran kurang.')
                ->danger()
                ->send();
            return;
        }

        DB::transaction(function () {

            $pembeli = $this->pembeli_id
                ? Pembeli::find($this->pembeli_id)
                : Pembeli::firstOrCreate(
                    ['nama' => $this->nama_customer],
                    ['alamat' => $this->alamat, 'telepon' => $this->telepon]
                );

            $rekening = $this->metode_pembayaran === 'TRANSFER'
                ? RekeningPerusahaan::find($this->rekening_perusahaan_id)
                : null;

            $penjualan = Penjualan::create([
                'no_nota' => $this->no_nota,
                'tanggal' => $this->tanggal, //! Disini ubah coy
                'pembeli_id' => $pembeli->id,
                'rekening_perusahaan_id' => $rekening?->id,
                'nama_customer' => $this->nama_customer,
                'alamat' => $this->alamat,
                'is_member' => (bool) $this->is_member,
                'metode_pembayaran' => $this->metode_pembayaran,
                'keterangan' => $this->keterangan_nota,
                'keterangan_pembayaran' => $this->keterangan_pembayaran,
                'bank' => $rekening?->nama_bank,
                'no_rekening' => $rekening?->no_rekening,
                'kendaraan' => $this->metode_pengiriman === 'DIKIRIM' ? $this->kendaraan : null,
                'plat_kendaraan' => $this->metode_pengiriman === 'DIKIRIM' ? $this->plat_kendaraan : null,
                'nama_sopir' => $this->metode_pengiriman === 'DIKIRIM' ? $this->nama_sopir : null,
                'total' => $this->total,
                'bayar' => $this->bayar,
                'kembalian' => $this->kembalian,
                'user_id' => auth()->id(),
                'toko_id' => $this->toko_id,   // <—— TAMBAHKAN INI
            ]);

            // foreach ($this->cart as $item) {

            //     // Ambil stok per toko
            //     // $stokToko = StokBarangToko::firstOrCreate(
            //     //     [
            //     //         'barang_id' => $item['barang_id'],
            //     //         'toko_id' => $this->toko_id,
            //     //     ],
            //     //     [
            //     //         'stok' => 0, // jika belum ada record
            //     //     ]
            //     // );
            // // ! SERVICE KURANGI STOCK
            //     // $stock = StokBarangToko::find($item['barang_id'])?->stok ?? 0;

            //     // StokBarangToko::where('id', $item['barang_id'])
            //     //     ->update(['stok' => $stock - $item['qty']]);

            //     // Cek stok cukup atau tidak
            //     if ($stokToko->stok < $item['qty']) {
            //         throw new \Exception("Stok barang '{$item['nama_barang']}' tidak mencukupi.");
            //     }

            //     // Kurangi stok menggunakan helper model
            //     $stokToko->kurang($item['qty']);

            //     // Simpan detail penjualan
            //     DetailPenjualan::create([
            //         'penjualan_id' => $penjualan->id,
            //         'barang_id' => $item['barang_id'],
            //         'nama_barang' => $item['nama_barang'],
            //         'satuan' => $item['satuan'],
            //         'qty' => $item['qty'],
            //         'harga_awal' => $item['harga_awal'],
            //         'harga_jual' => $item['harga_jual'],
            //         'potongan' => $item['potongan'],
            //         'subtotal' => $item['subtotal'],
            //     ]);
            // }
            foreach ($this->cart as $item) {
                $stokToko = StokBarangToko::where('barang_id', $item['barang_id'])
                    ->where('toko_id', $this->toko_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stokToko || $stokToko->stok < $item['qty']) {
                    Notification::make()
                        ->title('Simpan Penjualan Gagal')
                        ->body("Stok {$item['nama_barang']} tidak mencukupi.")
                        ->danger()
                        ->send();

                    return;
                }

                //! Kurangi stok
                // $stokToko->decrement('stok', $item['qty']);

                DetailPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $item['barang_id'],
                    'nama_barang' => $item['nama_barang'],
                    'satuan' => $item['satuan'],
                    'qty' => $item['qty'],
                    'harga_awal' => $item['harga_awal'],
                    'harga_jual' => $item['harga_jual'],
                    'potongan' => $item['potongan'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

        });

        $kembalian = $this->kembalian;
        $this->resetPos();

        Notification::make()
            ->title('Transaksi Berhasil')
            ->body("Kembalian: Rp {$kembalian}")
            ->success()
            ->send();
    }

    public function resetPos(): void
    {
        $this->cart = [];
        $this->bayar = 0;
        $this->metode_pembayaran = 'TUNAI';
        $this->rekening_perusahaan_id = null;
        $this->rekeningPerusahaan = [];
        $this->nama_customer = '';
        $this->alamat = '';
        $this->telepon = '';
        $this->pembeli_id = null;
        $this->no_nota = $this->generateNoNota();
    }
}