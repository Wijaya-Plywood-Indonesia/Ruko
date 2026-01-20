<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Filament\Resources\Penjualans\PenjualanResource;
use App\Models\Barang;
use App\Models\Pembeli;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\RekeningPembeli;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PosPenjualan extends Page
{
    protected static string $resource = PenjualanResource::class;
    protected string $view = 'filament.resources.penjualans.pages.pos-penjualan';

    public string $search = '';
    public Collection $searchResults;
    public array $cart = [];

    // pelanggan & pembayaran

    public string $metode_pembayaran = 'TUNAI';
    public int $bayar = 0;

    public $bank;
    public $no_rekening;
    public string $metode_pengiriman = 'DIBAWA_SENDIRI';
    public ?string $kendaraan = null;
    public ?string $plat_kendaraan = null;
    public ?string $nama_sopir = null;
    //buat pembeli
    public string $searchCustomer = '';
    public $customerResults = [];
    public ?int $pembeli_id = null;
    public string $nama_customer = '';
    public string $alamat = '';
    public string $telepon = '';
    public ?int $rekening_id = null;
    public $rekeningCustomer = [];

    public function mount(): void
    {
        $this->searchResults = collect();
    }

    /* ================= Pembeli/Customer ================= */
    public function updatedSearchCustomer()
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
    public function selectCustomer(int $id)
    {
        $pembeli = Pembeli::with('rekening')->findOrFail($id);

        $this->pembeli_id = $pembeli->id;
        $this->nama_customer = $pembeli->nama;
        $this->alamat = $pembeli->alamat;
        $this->telepon = $pembeli->telepon;

        // 👇 load rekening
        $this->rekeningCustomer = $pembeli->rekening;

        // reset
        $this->rekening_id = null;
        $this->customerResults = [];
        $this->searchCustomer = '';
    }
    public function updatedMetodePembayaran()
    {
        if ($this->metode_pembayaran !== 'TRANSFER') {
            $this->rekening_id = null;
        }
    }


    /* ================= AUTOCOMPLETE ================= */
    public function updatedSearch(): void
    {
        if (strlen($this->search) < 1) {
            $this->searchResults = collect();
            return;
        }

        $this->searchResults = Barang::query()
            ->where(function ($q) {
                $q->where('nama_barang', 'like', "%{$this->search}%")
                    ->orWhere('barcode', 'like', "%{$this->search}%");
            })
            ->limit(8)
            ->get();
    }

    public bool $showDropdown = false;

    public function openDropdown()
    {
        $this->showDropdown = true;
    }

    public function closeDropdown()
    {
        $this->showDropdown = false;
    }

    public function selectBarang(int $id): void
    {

        $barang = Barang::with('satuan')->find($id);
        if (!$barang)
            return;

        if($barang->stok_minimum < 1){
            Notification::make()
                ->title('Stok barang habis')
                ->body("Barang tidak bisa ditambahkan ke keranjang.")
                ->danger()
                ->send();
            return;
        }

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

                // ===== POTONGAN =====
                'potongan' => $this->is_member === 1 ? 5000 : 0,
                'total_potongan' => $this->is_member === 1 ? 5000 : 0,
            ];
        }

        $this->updateSubtotal($id);

        $this->search = '';
        $this->searchResults = collect();
        $this->showDropdown = false;
    }

    /* ================= CART ================= */
    public function updateQty($id): void
    {
        if (!isset($this->cart[$id]))
            return;

        $stock = Barang::find($id)?->stok_minimum ?? 0;
        $qty = $this->cart[$id]['qty'] ?? 0;

        if ($qty > $stock) {
            Notification::make()
                ->title('Stok Maksimum Tercapai')
                ->body("Jumlah maksimal untuk barang ini adalah {$stock}.")
                ->warning()
                ->persistent() // opsional
                ->send();
            $qty_a = max(1, (int) $stock);
            $this->cart[$id]['qty'] = $qty_a;
            return;
        }


        $qty_a = max(1, (int) $qty);
        $this->cart[$id]['qty'] = $qty_a;

        $this->cart[$id]['total_potongan'] =
            $this->cart[$id]['potongan'] * $qty_a;

        $this->updateSubtotal($id);
    }

    public function incrementQty(int $id): void
    {
        $stock = Barang::find($id)?->stok_minimum ?? 0;
        $qty = $this->cart[$id]['qty'] ?? 0;

        if ($qty >= $stock) {
            Notification::make()
                ->title('Stok Maksimum Tercapai')
                ->body("Jumlah maksimal untuk barang ini adalah {$stock}.")
                ->warning()
                ->persistent() // opsional
                ->send();

            return;
        }

        $this->cart[$id]['qty']++;
        $this->updateQty($id);
    }


    public function decrementQty(int $id): void
    {
        if($this->cart[$id]['qty'] <= 1){
            Notification::make()
                ->title('Minimal Jumlah Barang')
                ->body("Minimal jumlah barang adalah 1.")
                ->danger()
                ->persistent() // opsional
                ->send();

            return;
        }

        $this->cart[$id]['qty']--;
        $this->updateQty($id);
    }

    public function removeFromCart(int $id): void
    {
        unset($this->cart[$id]);
    }

    // public function updatePotongan($id)
    // {
    //     if (!isset($this->cart[$id]))
    //         return;

    //     $potongan = (int) ($this->cart[$id]['potongan'] ?? 0);
    //     $qty = (int) ($this->cart[$id]['qty'] ?? 1);

    //     $this->cart[$id]['potongan'] = max(0, $potongan);
    //     $this->cart[$id]['total_potongan'] = $this->cart[$id]['potongan'] * $qty;

    //     $this->hitungUlangTotal();
    // }

    protected function updateSubtotal(int $id): void
    {
        $item = $this->cart[$id];

        $this->cart[$id]['subtotal'] =
            ($item['harga_jual'] * $item['qty'])
            - ($item['total_potongan'] ?? 0);
    }


    /* ================= COMPUTED ================= */
    /* ================= TOTAL ================= */
    public function getTotalProperty(): int
    {
        return collect($this->cart)->sum('subtotal');
    }

    public function getKembalianProperty(): int
    {
        return max($this->bayar ?? 0 - $this->total, 0);
    }

    public int $is_member = 0;


    public function updatedIsMember($value)
    {
        $this->is_member = (bool) $value;

        if (!$this->is_member) {
            $this->reset([
                'searchCustomer',
                'customerResults',
                'rekeningCustomer',
            ]);
        }
    }

    /* ================= PENGIRIMAN ================= */
    public function updatedMetodePengiriman()
    {
        if ($this->metode_pengiriman === 'DIBAWA_SENDIRI') {
            $this->kendaraan = null;
            $this->plat_kendaraan = null;
            $this->nama_sopir = null;
        }
    }

    public int $kembalian = 0;

    /* ================= SIMPAN ================= */
    public function simpanPenjualan(): void
    {
        //! Validasi Pembayaran
        $total_pembayaran = $this->total;
        $nomimal_disetorkan = $this->bayar;
        
        if($nomimal_disetorkan < $total_pembayaran || $total_pembayaran <= 0){
            Notification::make()
                ->title('Pembayaran Kurang')
                ->body("Nominal pembayaran kurang.")
                ->danger()
                ->persistent() // opsional
                ->send();
            return;
        }
        

        //     dd([
        //         'nama_customer' => $this->nama_customer,
        //         'alamat' => $this->alamat,
        //         'metode_pembayaran' => $this->metode_pembayaran,
        //         'bank' => $this->bank,
        //         'no_rekening' => $this->no_rekening,
        //         'kendaraan' => $this->kendaraan,
        //         'nama_sopir' => $this->nama_sopir,
        //         'plat_kendaraan' => $this->plat_kendaraan,
        //         'total' => $this->total,
        //         'bayar' => $this->bayar,
        //         'kembalian' => $this->kembalian,
        //         'user_id' => auth()->id(),
        //         'cart' => $this->cart,
        //     ]);


        // $this->validate();

        if (empty($this->cart)) {
            return;
        }

        DB::transaction(function () {

            // =========================
            // 1. CUSTOMER
            // =========================
            $pembeli = $this->pembeli_id
                ? Pembeli::find($this->pembeli_id)
                : Pembeli::firstOrCreate(
                    ['nama' => $this->nama_customer],
                    [
                        'alamat' => $this->alamat,
                        'telepon' => $this->telepon,
                    ]
                );

            // =========================
            // 2. REKENING (JIKA TRANSFER)
            // =========================
            $rekening = null;

            if ($this->metode_pembayaran === 'TRANSFER') {

                // pilih rekening lama
                if ($this->rekening_id) {
                    $rekening = RekeningPembeli::find($this->rekening_id);

                    // atau buat baru
                } else {
                    $rekening = RekeningPembeli::create([
                        'pembeli_id' => $pembeli->id,
                        'jenis' => 'BANK',
                        'nama_bank' => $this->bank,
                        'no_rekening' => $this->no_rekening,
                        'atas_nama' => $pembeli->nama,
                    ]);
                }
            }

            // =========================
            // 3. PENJUALAN
            // =========================
            $penjualan = Penjualan::create([
                'no_nota' => 'INV-' . now()->format('YmdHis'),
                'tanggal' => now(),

                'pembeli_id' => $pembeli->id,
                'rekening_pembeli_id' => $rekening?->id,

                // BACKWARD COMPATIBLE
                'nama_customer' => $this->nama_customer,
                'is_member' => (bool) $this->is_member,
                'alamat' => $this->alamat,

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

            // =========================
            // 4. DETAIL
            // =========================
            foreach ($this->cart as $item) {
                $stock = Barang::find($item['barang_id'])?->stok_minimum ?? 0;

                Barang::where('id', $item['barang_id'])
                    ->update([
                        'stok_minimum' => $stock - $item['qty'],
                    ]);

                DetailPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $item['barang_id'],
                    'nama_barang' => $item['nama_barang'],
                    'satuan' => $item['satuan'],
                    'qty' => $item['qty'],
                    'harga_awal' => $item['harga_awal'],
                    'harga_jual' => $item['harga_jual'],

                    // ===== POTONGAN =====
                    'potongan' => $item['potongan'],
                    //   'total_potongan' => $item['total_potongan'],

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
            ->persistent()
            ->send();
    }

    public function resetPos(): void
    {
        $this->cart = [];
        $this->bayar = 0;
        $this->metode_pembayaran = 'TUNAI';

        $this->bank = null;
        $this->no_rekening = null;

        $this->kendaraan = null;
        $this->nama_sopir = null;
        $this->plat_kendaraan = null;


        $this->nama_customer = '';
        $this->alamat = '';
    }
    /* ================= CART : HARGA ================= */
    public function updateHargaJual($id): void
    {
        if (!isset($this->cart[$id]))
            return;

        $this->updateSubtotal($id);
    }
    /* ================= CART : POTONGAN ================= */
    public function updatePotongan($id): void
    {
        if (!isset($this->cart[$id]))
            return;

        $potongan = max(0, (int) $this->cart[$id]['potongan']);
        $qty = $this->cart[$id]['qty'];

        $this->cart[$id]['potongan'] = $potongan;
        $this->cart[$id]['total_potongan'] = $potongan * $qty;

        $this->updateSubtotal($id);
    }

    /* ===== RESTORE CART (OFFLINE) ===== */
    #[\Livewire\Attributes\On('restoreCart')]
    public function restoreCart(array $cart): void
    {
        $this->cart = $cart;
    }
    protected function rules(): array
    {
        return [
            'nama_customer' => 'nullable|string|max:100',
            'alamat' => 'nullable|string|max:255',

            'metode_pembayaran' => 'required|in:TUNAI,TRANSFER',

            'bank' => $this->metode_pembayaran === 'TRANSFER'
                ? 'required|string|max:50'
                : 'nullable',

            'no_rekening' => $this->metode_pembayaran === 'TRANSFER'
                ? 'required|string|max:50'
                : 'nullable',

            'bayar' => 'required|numeric|min:' . $this->total,
        ];
    }
}
