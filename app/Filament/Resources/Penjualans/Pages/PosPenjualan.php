<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Filament\Resources\Penjualans\PenjualanResource;
use App\Models\Barang;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
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
    public string $nama_customer = '';
    public string $alamat = '';
    public string $metode_pembayaran = 'TUNAI';
    public int $bayar = 0;


    public $bank;
    public $no_rekening;

    public $kendaraan;
    public $nama_sopir;

    public function mount(): void
    {
        $this->searchResults = collect();
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
            ];
        }

        $this->updateSubtotal($id);

        $this->search = '';
        $this->searchResults = collect();
    }

    /* ================= CART ================= */
    public function incrementQty(int $id): void
    {
        $this->cart[$id]['qty']++;
        $this->cart[$id]['subtotal'] =
            $this->cart[$id]['qty'] * $this->cart[$id]['harga_jual'];

    }


    public function decrementQty(int $id): void
    {
        if ($this->cart[$id]['qty'] > 1) {
            $this->cart[$id]['qty']--;
            $this->cart[$id]['subtotal'] =
                $this->cart[$id]['qty'] * $this->cart[$id]['harga_jual'];
        }
    }

    public function removeFromCart(int $id): void
    {
        unset($this->cart[$id]);
    }

    protected function updateSubtotal(int $id): void
    {
        $this->cart[$id]['subtotal'] =
            $this->cart[$id]['qty'] * $this->cart[$id]['harga_jual'];
    }

    /* ================= COMPUTED ================= */
    public function getTotalProperty(): int
    {
        return collect($this->cart)->sum('subtotal');
    }

    public function getKembalianProperty(): int
    {
        return max(((int) $this->bayar) - $this->total, 0);
    }


    /* ================= SIMPAN ================= */
    public function simpanPenjualan(): void
    {
        // dd([
        //     'nama_customer' => $this->nama_customer,
        //     'alamat' => $this->alamat,
        //     'metode_pembayaran' => $this->metode_pembayaran,
        //     'bank' => $this->bank,
        //     'no_rekening' => $this->no_rekening,
        //     'kendaraan' => $this->kendaraan,
        //     'nama_sopir' => $this->nama_sopir,
        //     'total' => $this->total,
        //     'bayar' => $this->bayar,
        //     'kembalian' => $this->kembalian,
        //     'user_id' => auth()->id(),
        //     'cart' => $this->cart,
        // ]);


        // $this->validate();

        if (empty($this->cart)) {
            return;
        }

        DB::transaction(function () {

            $penjualan = Penjualan::create([
                'no_nota' => 'INV-' . now()->format('YmdHis'),
                'tanggal' => now(),

                'nama_customer' => $this->nama_customer,
                'alamat' => $this->alamat,

                'metode_pembayaran' => $this->metode_pembayaran,
                'bank' => $this->metode_pembayaran === 'TRANSFER' ? $this->bank : null,
                'no_rekening' => $this->metode_pembayaran === 'TRANSFER' ? $this->no_rekening : null,

                'kendaraan' => $this->kendaraan,
                'nama_sopir' => $this->nama_sopir,

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
                    'subtotal' => $item['subtotal'],
                ]);
            }
        });

        $this->resetPos();
        // ⬅️ SIMPAN NILAI SEBELUM RESET
        $kembalian = $this->kembalian;
        Notification::make()
            ->title('Transaksi Berhasil')
            ->body("Kembalian: Rp {$kembalian}")
            ->success()
            ->persistent() // tidak hilang sampai ditekan OK
            ->actions([
                Action::make('ok')
                    ->label('OK')
                    ->close(),
            ])
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

        $this->nama_customer = '';
        $this->alamat = '';
    }
    public function updateHargaJual($id)
    {
        if (!isset($this->cart[$id]))
            return;

        $harga = (int) $this->cart[$id]['harga_jual'];
        $qty = (int) $this->cart[$id]['qty'];

        $this->cart[$id]['subtotal'] = $harga * $qty;
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
