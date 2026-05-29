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
    public int $total = 0;

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
    public int $bayar_tunai = 0;
    public int $bayar_transfer = 0;
    public ?int $rekening_perusahaan_id = null;
    public $rekeningPerusahaan = [];
    public ?RekeningPerusahaan $selectedBank = null;
    public string $kode_member = '';

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

        $this->searchResults = Barang::with('satuan')
            ->where(function ($query) {
                $query->where('barangs.nama_barang', 'like', "%{$this->search}%")
                    ->orWhere('barangs.barcode', 'like', "%{$this->search}%");
            })
            ->limit(10)
            ->get();

        foreach ($this->searchResults as $barang) {
            $barang->stok_aktual = $barang->stok_buku_besar;
        }
        
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
        $barang = Barang::with('satuan')->find($id);
        if (!$barang) return;

        $stok = $barang->stok_buku_besar;

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
                'potongan' => $this->is_member ? 0 : 0, // diskon member dinonaktifkan (sebelumnya 5000)
                'member_discount_active' => $this->is_member ? true : false,
                'total_potongan' => $this->is_member ? 0 : 0,
                'subtotal' => 0,
            ];
        }

        $this->updateSubtotal($id);
        $this->calculateTotal();
        $this->search = '';
        $this->searchResults = collect();
        $this->showDropdown = false;
    }

    protected function calculateTotal(): void
    {
        $this->total = max(0, collect($this->cart)->sum(fn($i) => $i['subtotal'] ?? 0));
    }

    /* ================= CART ================= */
    public function updateQty(int $id): void
    {
        if (!isset($this->cart[$id])) return;

        $barang = Barang::find($id);
        $stock = $barang ? $barang->stok_buku_besar : 0;

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
        $this->calculateTotal();
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
        $this->calculateTotal();
    }

    public function updatePotongan(int $id): void
    {
        if (!isset($this->cart[$id])) return;

        $potongan = max(0, (float) $this->cart[$id]['potongan']);
        $qty = max(0.01, (float) $this->cart[$id]['qty']);

        $this->cart[$id]['potongan'] = $potongan;
        $this->cart[$id]['total_potongan'] = $potongan * $qty;
        $this->updateSubtotal($id);
        $this->calculateTotal();
    }

    public function updateHargaJual(int $id): void
    {
        if (!isset($this->cart[$id])) return;

        $harga = max(0, (int) ($this->cart[$id]['harga_jual'] ?? 0));
        $this->cart[$id]['harga_jual'] = $harga;
        $this->updateSubtotal($id);
        $this->calculateTotal();
    }

    protected function updateSubtotal(int $id): void
    {
        if (!isset($this->cart[$id])) return;

        $item = $this->cart[$id];
        $this->cart[$id]['subtotal'] = max(0, ($item['harga_jual'] * $item['qty']) - ($item['total_potongan'] ?? 0));
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

    public function updatedKodeMember(): void
    {
        if (strlen($this->kode_member) < 2) {
            $this->customerResults = [];
            return;
        }

        // Exact match for auto-fill
        $pembeli = Pembeli::where('nik', $this->kode_member)->first();
        if ($pembeli) {
            $this->selectCustomer($pembeli->id);
            Notification::make()->title('Member Ditemukan')->success()->send();
            $this->customerResults = [];
            return;
        }

        // Fuzzy search for recommendations
        $this->customerResults = Pembeli::query()
            ->where('nama', 'like', "%{$this->kode_member}%")
            ->orWhere('telepon', 'like', "%{$this->kode_member}%")
            ->orWhere('nik', 'like', "%{$this->kode_member}%")
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
        $this->kode_member = $pembeli->nik ?? ''; // Set kode_member to NIK when selected
        $this->customerResults = [];
        $this->searchCustomer = '';
    }

    /* ================= PEMBAYARAN ================= */
    public function updatedMetodePembayaran(): void
    {
        if ($this->metode_pembayaran === 'TRANSFER' || $this->metode_pembayaran === 'TUNAI & TRANSFER') {
            $this->rekeningPerusahaan = RekeningPerusahaan::all();
        } else {
            $this->rekeningPerusahaan = [];
            $this->rekening_perusahaan_id = null;
            $this->selectedBank = null;
        }

        // Reset split values when changing method
        if ($this->metode_pembayaran !== 'TUNAI & TRANSFER') {
            $this->bayar_tunai = 0;
            $this->bayar_transfer = 0;
        }
    }

    public function updatedRekeningPerusahaanId(): void
    {
        if ($this->rekening_perusahaan_id) {
            $this->selectedBank = RekeningPerusahaan::find($this->rekening_perusahaan_id);
        } else {
            $this->selectedBank = null;
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
    public function getKembalianProperty(): int
    {
        $totalBayar = ($this->metode_pembayaran === 'TUNAI & TRANSFER') 
            ? ($this->bayar_tunai + $this->bayar_transfer) 
            : ($this->bayar ?? 0);
            
        return max($totalBayar - $this->total, 0);
    }

    /* ================= MEMBER ================= */
    public function toggleMember($value): void
    {
        $this->updatedIsMember($value);
    }

    public function updatedIsMember($value): void
    {
        $this->is_member = (int) $value;

        // Reset customer info on any toggle to prevent mixed/dirty state
        $this->reset(['searchCustomer', 'customerResults', 'pembeli_id', 'nama_customer', 'alamat', 'telepon', 'kode_member']);

        // Apply/Remove retroactive discount for items already in cart
        foreach ($this->cart as $id => $item) {
            if ($this->is_member) {
                // ONLY add if not already active
                if (!isset($this->cart[$id]['member_discount_active']) || !$this->cart[$id]['member_discount_active']) {
                    $this->cart[$id]['potongan'] += 0; // diskon member + 0 (sebelumnya + 5000)
                    $this->cart[$id]['member_discount_active'] = true;
                }
            } else {
                // ONLY subtract if active
                if (isset($this->cart[$id]['member_discount_active']) && $this->cart[$id]['member_discount_active']) {
                    $this->cart[$id]['potongan'] = max(0, $this->cart[$id]['potongan'] - 0); // diskon member - 0 (sebelumnya - 5000)
                    $this->cart[$id]['member_discount_active'] = false;
                }
            }
            
            // Sync total_potongan and subtotal
            $this->cart[$id]['total_potongan'] = $this->cart[$id]['potongan'] * $this->cart[$id]['qty'];
            $this->updateSubtotal($id);
        }
        $this->calculateTotal();
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

        $totalBayar = ($this->metode_pembayaran === 'TUNAI & TRANSFER') 
            ? ($this->bayar_tunai + $this->bayar_transfer) 
            : ($this->bayar ?? 0);

        if (!$this->is_member && $totalBayar < $this->total) {
            Notification::make()
                ->title('Pembayaran Kurang')
                ->body('Nominal pembayaran kurang.')
                ->danger()
                ->send();
            return;
        }

        if ($this->total <= 0) {
            Notification::make()
                ->title('Transaksi Tidak Valid')
                ->body('Total transaksi harus lebih dari 0.')
                ->danger()
                ->send();
            return;
        }

        if (($this->metode_pembayaran === 'TRANSFER' || ($this->metode_pembayaran === 'TUNAI & TRANSFER' && $this->bayar_transfer > 0)) && !$this->rekening_perusahaan_id) {
            Notification::make()
                ->title('Rekening Belum Dipilih')
                ->body('Silahkan pilih rekening perusahaan untuk pembayaran transfer.')
                ->danger()
                ->send();
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

                $rekening = ($this->metode_pembayaran === 'TRANSFER' || $this->metode_pembayaran === 'TUNAI & TRANSFER')
                    ? RekeningPerusahaan::find($this->rekening_perusahaan_id)
                    : null;

                $totalBayar = ($this->metode_pembayaran === 'TUNAI & TRANSFER') 
                    ? ($this->bayar_tunai + $this->bayar_transfer) 
                    : ($this->bayar ?? 0);

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
                    'bayar' => $totalBayar,
                    'bayar_tunai' => ($this->metode_pembayaran === 'TUNAI & TRANSFER') ? $this->bayar_tunai : ($this->metode_pembayaran === 'TUNAI' ? $this->bayar : 0),
                    'bayar_transfer' => ($this->metode_pembayaran === 'TUNAI & TRANSFER') ? $this->bayar_transfer : ($this->metode_pembayaran === 'TRANSFER' ? $this->bayar : 0),
                    'kembalian' => $this->kembalian,
                    'user_id' => auth()->id(),
                    'toko_id' => $this->toko_id,
                ]);

                foreach ($this->cart as $item) {
                    $barang = Barang::find($item['barang_id']);
                    $stokBukuBesar = $barang ? $barang->stok_buku_besar : 0;

                    if ($stokBukuBesar < $item['qty']) {
                        throw new \Exception("Stok {$item['nama_barang']} tidak mencukupi.");
                    }

                    $stokToko = StokBarangToko::where('barang_id', $item['barang_id'])
                        ->where('toko_id', $this->toko_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$stokToko) {
                        $stokToko = StokBarangToko::create([
                            'barang_id' => $item['barang_id'],
                            'toko_id' => $this->toko_id,
                            'stok' => 0,
                        ]);
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
        $this->reset(['cart', 'bayar', 'bayar_tunai', 'bayar_transfer', 'metode_pembayaran', 'rekening_perusahaan_id', 'rekeningPerusahaan', 'nama_customer', 'alamat', 'telepon', 'pembeli_id', 'keterangan_nota', 'keterangan_pembayaran', 'kode_member', 'selectedBank', 'total']);
        $this->no_nota = $this->generateNoNota();
    }

    #[On('restoreCart')]
    public function restoreCart($cart): void
    {
        $this->cart = $cart;
        $this->calculateTotal();
    }

    #[On('restoreState')]
    public function restoreState(array $state): void
    {
        $this->cart = $state['cart'] ?? [];
        $this->is_member = (int) ($state['is_member'] ?? 0);
        $this->pembeli_id = $state['pembeli_id'] ?? null;
        $this->nama_customer = $state['nama_customer'] ?? '';
        $this->alamat = $state['alamat'] ?? '';
        $this->telepon = $state['telepon'] ?? '';
        $this->kode_member = $state['kode_member'] ?? '';
        $this->metode_pembayaran = $state['metode_pembayaran'] ?? 'TUNAI';
        $this->bayar = (int) ($state['bayar'] ?? 0);
        $this->bayar_tunai = (int) ($state['bayar_tunai'] ?? 0);
        $this->bayar_transfer = (int) ($state['bayar_transfer'] ?? 0);
        $this->rekening_perusahaan_id = $state['rekening_perusahaan_id'] ?? null;
        $this->keterangan_pembayaran = $state['keterangan_pembayaran'] ?? null;
        $this->keterangan_nota = $state['keterangan_nota'] ?? null;
        $this->metode_pengiriman = $state['metode_pengiriman'] ?? 'DIBAWA_SENDIRI';
        $this->kendaraan = $state['kendaraan'] ?? null;
        $this->plat_kendaraan = $state['plat_kendaraan'] ?? null;
        $this->nama_sopir = $state['nama_sopir'] ?? null;

        // If transferring/payment split, populate company bank accounts
        if ($this->metode_pembayaran === 'TRANSFER' || $this->metode_pembayaran === 'TUNAI & TRANSFER') {
            $this->rekeningPerusahaan = RekeningPerusahaan::all();
            if ($this->rekening_perusahaan_id) {
                $this->selectedBank = RekeningPerusahaan::find($this->rekening_perusahaan_id);
            }
        }

        $this->calculateTotal();
    }
}