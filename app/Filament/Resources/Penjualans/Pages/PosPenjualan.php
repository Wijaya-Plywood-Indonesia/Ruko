<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Filament\Resources\Penjualans\PenjualanResource;
use App\Models\Barang;
use App\Models\Pembeli;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\RekeningPerusahaan;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PosPenjualan extends Page
{
    protected static string $resource = PenjualanResource::class;
    protected string $view = 'filament.resources.penjualans.pages.pos-penjualan';

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

    /* ================= MOUNT ================= */
    public function mount(): void
    {
        $this->searchResults = collect();
    }

    /* ================= SEARCH BARANG ================= */
    public function updatedSearch(): void
    {
        if (strlen($this->search) < 1) {
            $this->searchResults = collect();
            return;
        }

        $this->searchResults = Barang::query()
            ->where('nama_barang', 'like', "%{$this->search}%")
            ->orWhere('barcode', 'like', "%{$this->search}%")
            ->limit(8)
            ->get();
    }

    public function selectBarang(int $id): void
    {
        $barang = Barang::with('satuan')->find($id);
        if (!$barang)
            return;

        if (isset($this->cart[$id])) {
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
            ];
        }

        $this->updateSubtotal($id);
        $this->search = '';
        $this->searchResults = collect();
    }

    /* ================= CART ================= */
    public function updateQty(int $id): void
    {
        $qty = max(1, (int) $this->cart[$id]['qty']);
        $this->cart[$id]['qty'] = $qty;
        $this->cart[$id]['total_potongan'] = $this->cart[$id]['potongan'] * $qty;
        $this->updateSubtotal($id);
    }

    public function incrementQty(int $id): void
    {
        $this->cart[$id]['qty']++;
        $this->updateQty($id);
    }

    public function decrementQty(int $id): void
    {
        if ($this->cart[$id]['qty'] > 1) {
            $this->cart[$id]['qty']--;
            $this->updateQty($id);
        }
    }

    public function removeFromCart(int $id): void
    {
        unset($this->cart[$id]);
    }

    protected function updateSubtotal(int $id): void
    {
        $item = $this->cart[$id];
        $this->cart[$id]['subtotal'] =
            ($item['harga_jual'] * $item['qty']) - ($item['total_potongan'] ?? 0);
    }

    /* ================= MEMBER ================= */
    public function updatedIsMember($value): void
    {
        $this->is_member = (bool) $value;
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
        return collect($this->cart)->sum('subtotal');
    }

    public function getKembalianProperty(): int
    {
        return max($this->bayar - $this->total, 0);
    }

    /* ================= SIMPAN ================= */
    public function simpanPenjualan(): void
    {
        if (empty($this->cart))
            return;

        DB::transaction(function () {

            $pembeli = $this->pembeli_id
                ? Pembeli::find($this->pembeli_id)
                : Pembeli::firstOrCreate(
                    ['nama' => $this->nama_customer],
                    ['alamat' => $this->alamat, 'telepon' => $this->telepon]
                );

            $rekening = null;
            if ($this->metode_pembayaran === 'TRANSFER') {
                $rekening = RekeningPerusahaan::find($this->rekening_perusahaan_id);
            }

            $penjualan = Penjualan::create([
                'no_nota' => 'INV-' . now()->format('YmdHis'),
                'tanggal' => now(),

                'pembeli_id' => $pembeli->id,
                'rekening_perusahaan_id' => $rekening?->id,

                'nama_customer' => $this->nama_customer,
                'alamat' => $this->alamat,
                'is_member' => (bool) $this->is_member,

                'metode_pembayaran' => $this->metode_pembayaran,
                'bank' => $rekening?->nama_bank,
                'no_rekening' => $rekening?->no_rekening,

                'kendaraan' => $this->metode_pengiriman === 'DIKIRIM'
                    ? $this->kendaraan
                    : null,
                'plat_kendaraan' => $this->metode_pengiriman === 'DIKIRIM'
                    ? $this->plat_kendaraan
                    : null,
                'nama_sopir' => $this->metode_pengiriman === 'DIKIRIM'
                    ? $this->nama_sopir
                    : null,

                'total' => $this->total,
                'bayar' => $this->bayar,
                'kembalian' => $this->kembalian,

                'user_id' => auth()->id(),
            ]);

            foreach ($this->cart as $item) {
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
    }
}
