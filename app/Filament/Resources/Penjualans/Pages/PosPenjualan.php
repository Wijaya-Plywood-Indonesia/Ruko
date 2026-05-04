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
use Livewire\Attributes\On;

class PosPenjualan extends Page
{
    protected static string $resource = PenjualanResource::class;
    protected string $view = 'filament.resources.penjualans.pages.pos-penjualan';

    /* ================= IDENTITAS TOKO ================= */
    public ?int $toko_id = null;
    public ?string $kodeToko = null;
    public ?string $namaToko = null;

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

    public function mount(): void
    {
        $this->searchResults = collect();
        $user = auth()->user();
        $tokoUser = $user->tokoUtama()->first();

        if ($tokoUser) {
            $this->toko_id = $tokoUser->id_toko;
            $this->kodeToko = $tokoUser->toko->kode_toko;
            $this->namaToko = $tokoUser->toko->nama_toko;
            $this->no_nota = $this->generateNoNota();
        }

        $this->tanggal = now()->format('Y-m-d\TH:i');
    }

    public function generateNoNota()
    {
        if (!$this->toko_id) {
            return 'XXX-000001';
        }

        $toko = IdentitasToko::find($this->toko_id);
        $prefix = ($toko?->kode_toko ?? 'XXX') . '-';

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

        $tokoId = $this->toko_id;
        $t = (new StokBarangToko)->getTable();

        $this->searchResults = Barang::query()
            ->leftJoin($t, function ($q) use ($t, $tokoId) {
                $q->on("$t.barang_id", '=', 'barangs.id')
                    ->where("$t.toko_id", $tokoId);
            })
            ->select(
                'barangs.*',
                DB::raw("COALESCE($t.stok, 0) as stok_aktual")
            )
            ->where(function ($query) {
                $query->where('barangs.nama_barang', 'like', "%{$this->search}%")
                    ->orWhere('barangs.barcode', 'like', "%{$this->search}%");
            })
            ->limit(10)
            ->get();
        
        $this->showDropdown = true;
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
        if (!$barang) return;

        $stokToko = StokBarangToko::where('barang_id', $id)
            ->where('toko_id', $this->toko_id)
            ->first();

        $stok = $stokToko?->stok ?? 0;

        if ($stok < 0.01) {
            Notification::make()
                ->title('Stok barang habis')
                ->body("Barang {$barang->nama_barang} habis.")
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
    public function updateQty(int $id): void
    {
        if (!isset($this->cart[$id])) return;

        $stock = (float) (StokBarangToko::where('barang_id', $id)
            ->where('toko_id', $this->toko_id)
            ->value('stok') ?? 0);

        $qty = max(0.01, (float) $this->cart[$id]['qty']);

        if ($qty > $stock) {
            Notification::make()
                ->title('Stok Maksimum Tercapai')
                ->body("Jumlah maksimal adalah {$stock}.")
                ->warning()
                ->send();
            $qty = $stock;
        }

        $this->cart[$id]['qty'] = $qty;
        $this->cart[$id]['total_potongan'] = $this->cart[$id]['potongan'] * $qty;
        $this->updateSubtotal($id);
    }

    public function incrementQty(int $id): void
    {
        $this->cart[$id]['qty'] = round((float) $this->cart[$id]['qty'] + 1, 2);
        $this->updateQty($id);
    }

    public function decrementQty(int $id): void
    {
        if ((float) $this->cart[$id]['qty'] <= 1) {
            $this->removeFromCart($id);
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
        if (!isset($this->cart[$id])) return;

        $potongan = max(0, (float) $this->cart[$id]['potongan']);
        $qty = max(0.01, (float) $this->cart[$id]['qty']);

        $this->cart[$id]['potongan'] = $potongan;
        $this->cart[$id]['total_potongan'] = $potongan * $qty;
        $this->updateSubtotal($id);
    }

    public function updateHargaJual(int $id): void
    {
        if (!isset($this->cart[$id])) return;

        $harga = max(0, (int) ($this->cart[$id]['harga_jual'] ?? 0));
        $this->cart[$id]['harga_jual'] = $harga;
        $this->updateSubtotal($id);
    }

    protected function updateSubtotal(int $id): void
    {
        if (!isset($this->cart[$id])) return;

        $item = $this->cart[$id];
        $this->cart[$id]['subtotal'] = ($item['harga_jual'] * $item['qty']) - ($item['total_potongan'] ?? 0);
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

    public function setBayar($amount): void
    {
        if ($amount === 'pas') {
            $this->bayar = $this->total;
        } else {
            $this->bayar = (int) $amount;
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
        $this->is_member = (int) $value;

        // Apply/Remove retroactive discount for items already in cart
        foreach ($this->cart as $id => $item) {
            if ($this->is_member) {
                // If switching to member, add 5000 discount
                $this->cart[$id]['potongan'] += 5000;
            } else {
                // If switching to regular, subtract 5000 discount but not below 0
                $this->cart[$id]['potongan'] = max(0, $this->cart[$id]['potongan'] - 5000);
            }
            
            // Sync total_potongan and subtotal
            $this->cart[$id]['total_potongan'] = $this->cart[$id]['potongan'] * $this->cart[$id]['qty'];
            $this->updateSubtotal($id);
        }

        if (!$this->is_member) {
            $this->reset(['searchCustomer', 'customerResults', 'pembeli_id', 'nama_customer', 'alamat', 'telepon']);
        }
    }

    /* ================= PENGIRIMAN ================= */
    public function updatedMetodePengiriman(): void
    {
        if ($this->metode_pengiriman === 'DIBAWA_SENDIRI') {
            $this->reset(['kendaraan', 'plat_kendaraan', 'nama_sopir']);
        }
    }

    /* ================= SIMPAN ================= */
    public function simpanPenjualan(): void
    {
        if (empty($this->cart)) {
            Notification::make()->title('Keranjang Kosong')->danger()->send();
            return;
        }

        if ($this->bayar < $this->total) {
            Notification::make()->title('Pembayaran Kurang')->danger()->send();
            return;
        }

        try {
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
                    'tanggal' => $this->tanggal,
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
                    'toko_id' => $this->toko_id,
                ]);

                foreach ($this->cart as $item) {
                    $stokToko = StokBarangToko::where('barang_id', $item['barang_id'])
                        ->where('toko_id', $this->toko_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$stokToko || $stokToko->stok < $item['qty']) {
                        throw new \Exception("Stok {$item['nama_barang']} tidak mencukupi.");
                    }

                    $stokToko->kurang($item['qty']);

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
                ->body("Kembalian: Rp " . number_format($kembalian))
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Transaksi Gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetPos(): void
    {
        $this->reset(['cart', 'bayar', 'metode_pembayaran', 'rekening_perusahaan_id', 'rekeningPerusahaan', 'nama_customer', 'alamat', 'telepon', 'pembeli_id', 'keterangan_nota', 'keterangan_pembayaran']);
        $this->no_nota = $this->generateNoNota();
    }

    #[On('restoreCart')]
    public function restoreCart($cart): void
    {
        $this->cart = $cart;
    }
}