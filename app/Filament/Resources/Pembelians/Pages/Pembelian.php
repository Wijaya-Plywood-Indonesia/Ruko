<?php

namespace App\Filament\Resources\Pembelians\Pages;

use App\Filament\Resources\Pembelians\PembeliansResource;
use App\Models\Pembelian as ModelsPembelian;
use App\Models\Supplier;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class Pembelian extends Page
{
    protected static string $resource = PembeliansResource::class;

    protected string $view = 'filament.resources.pembelians.pages.pembelian';

    // State / Variabel Form 
    public $nomor_nota;
    public $created_by;
    public $created_by_name;
    public $tanggal;
    public $supplier_id = '';
    public $supplier_name;
    public $supplier_phone;
    public $supplier_address;
    public $is_new_supplier = false;
    public $supplier_npwp;
    public $status = ModelsPembelian::STATUS_DRAFT;
    public $catatan;
    public $foto_nota = []; // Untuk multi-upload

    // State Nominal
    public $sub_total = null;
    public $total_diskon = null;
    public $total_ppn = null;
    public $ongkir = null;
    public $biaya_lain = null;

    public function mount()
    {
        $this->created_by = auth()->id();
        $this->created_by_name = auth()->user()->name ?? 'User';
        $this->tanggal = now()->format('Y-m-d');
    }

    public function updatedSupplierId($value)
    {
        $supplier = Supplier::find($value);
        if ($supplier) {
            $this->supplier_name = $supplier->nama;
            $this->supplier_phone = $supplier->telepon;
            $this->supplier_address = $supplier->alamat;
            $this->supplier_npwp = $supplier->npwp;
        } else {
            $this->supplier_name = null;
            $this->supplier_phone = null;
            $this->supplier_address = null;
            $this->supplier_npwp = null;
        }
    }

    public function getGrandTotalProperty()
    {
        return (floatval($this->sub_total) ?: 0)
            - (floatval($this->total_diskon) ?: 0)
            + (floatval($this->total_ppn) ?: 0)
            + (floatval($this->ongkir) ?: 0)
            + (floatval($this->biaya_lain) ?: 0);
    }

    public function simpan()
    {
        $this->validate([
            'nomor_nota' => 'required',
            'tanggal' => 'required',
            'supplier_id' => 'required',
            'supplier_name' => $this->is_new_supplier ? 'required' : '',
        ]);

        $final_supplier_id = $this->supplier_id;

        if ($this->is_new_supplier) {
            // Masukkan data ke table Supplier
            $newSupplier = Supplier::create([
                'nama'    => $this->supplier_name,
                'telepon' => $this->supplier_phone,
                'alamat'  => $this->supplier_address,
            ]);
            $final_supplier_id = $newSupplier->id;
        } else {
            // Jika bukan supplier baru, pastikan ID sudah terpilih
            if (empty($final_supplier_id)) {
                $this->addError('supplier_id', 'Pilih supplier dari daftar atau buat baru.');
                return;
            }
        }

        // 3. Simpan ke Database Pembelian
        $pembelian = ModelsPembelian::create([
            'nomor_nota'  => $this->nomor_nota,
            'created_by'  => $this->created_by,
            'tanggal'     => $this->tanggal,
            'supplier_id' => $final_supplier_id, // Gunakan ID final

            // ==========================================
            // TAMBAHKAN 4 BARIS INI AGAR DATABASE TIDAK ERROR
            // ==========================================
            'supplier_name'    => $this->supplier_name,
            'supplier_phone'   => $this->supplier_phone,
            'supplier_address' => $this->supplier_address,
            'supplier_npwp'    => $this->supplier_npwp,
            // ==========================================

            'status'      => $this->status,
            'catatan'     => $this->catatan,
            'foto'        => $paths ?? null,
            'sub_total'   => $this->sub_total ?: 0,
            'total_diskon' => $this->total_diskon ?: 0,
            'total_ppn'   => $this->total_ppn ?: 0,
            'ongkir'      => $this->ongkir ?: 0,
            'biaya_lain'  => $this->biaya_lain ?: 0,
            'grand_total' => $this->grand_total,
        ]);

        // Logic save ke database
        // Pembelian::create([...]);

        Notification::make()
            ->title('Berhasil!')
            ->body('Data pembelian berhasil disimpan.')
            ->success()
            ->send();
    }
}
