<style>
    /* ===============================
   GLOBAL POS STYLE
   =============================== */

    .pos-section {
        margin-bottom: 24px;
    }

    .pos-divider {
        border-top: 1px solid #e5e7eb;
        margin: 24px 0;
    }

    /* ===============================
   INPUT & SELECT
   =============================== */

    .pos-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        padding: 8px 10px;
        border-radius: 8px;
    }

    .pos-input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 1px #2563eb;
    }

    .pos-select {
        width: 100%;
        border: 1px solid #cbd5e1;
        padding: 8px 10px;
        border-radius: 8px;
        background: white;
    }

    /* ===============================
   BUTTON
   =============================== */

    .pos-btn {
        padding: 6px 10px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        cursor: pointer;
        font-weight: 600;
    }

    .pos-btn:hover {
        background: #e5e7eb;
    }

    .pos-btn-primary {
        background: #2563eb;
        color: white;
        border-color: #2563eb;
    }

    .pos-btn-primary:hover {
        background: #1d4ed8;
    }

    .pos-btn-danger {
        background: #fee2e2;
        border-color: #fca5a5;
        color: #b91c1c;
    }

    .pos-btn-danger:hover {
        background: #fecaca;
    }

    /* ===============================
   QTY CONTROL
   =============================== */

    .qty-box {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #cbd5e1;
        padding: 6px 10px;
        border-radius: 10px;
    }

    /* ===============================
   TABLE
   =============================== */

    .pos-table {
        width: 100%;
        border-collapse: collapse;
    }

    .pos-table th {
        padding: 10px;
        border-bottom: 2px solid #000;
        text-align: left;
    }

    .pos-table td {
        padding: 10px;
        border-bottom: 1px solid #e5e7eb;
    }

    /* ===============================
   TOTAL
   =============================== */

    .pos-total {
        font-size: 1.3rem;
        font-weight: bold;
    }
</style>

<x-filament::page>
    {{-- ===============================
        HEADER
    =============================== --}}
    <h2>Point of Sale</h2>

    {{-- ===============================
        SEARCH BARANG
    =============================== --}}
    <div class="pos-section" style="max-width: 300px; position: relative">
        <input
            type="text"
            wire:model.live="search"
            placeholder="Cari barang / barcode"
            class="pos-input"
        />

        @if ($searchResults->isNotEmpty())
        <div
            style="
                position: absolute;
                background: white;
                border: 1px solid #cbd5e1;
                width: 100%;
                z-index: 999;
            "
        >
            @foreach ($searchResults as $barang)
            <div
                wire:click="selectBarang({{ $barang->id }})"
                style="
                    padding: 8px;
                    cursor: pointer;
                    border-bottom: 1px solid #e5e7eb;
                "
            >
                <strong>{{ $barang->nama_barang }}</strong
                ><br />
                <small>Rp {{ number_format($barang->harga_jual) }}</small>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="pos-divider"></div>

    {{-- ===============================
        DATA PELANGGAN
    =============================== --}}
    <div class="pos-section" style="max-width: 400px">
        <h3>Data Pelanggan</h3>

        <input
            wire:model.live="nama_customer"
            placeholder="Nama Customer"
            class="pos-input"
        />
        <br /><br />

        <input
            wire:model.live="alamat"
            placeholder="Alamat"
            class="pos-input"
        />
        <br /><br />

        <select wire:model.live="metode_pembayaran" class="pos-select">
            <option value="cash">Cash</option>
            <option value="transfer">Transfer</option>
        </select>
    </div>

    <div class="pos-divider"></div>

    {{-- ===============================
        CART / TABEL BARANG
    =============================== --}}
    <table class="pos-table">
        <tr>
            <th>Barang</th>
            <th>Satuan</th>
            <th>Harga</th>
            <th>Qty</th>
            <th>Subtotal</th>
            <th>Aksi</th>
        </tr>

        @forelse ($cart as $id => $item)
        <tr>
            <td>{{ $item["nama_barang"] }}</td>
            <td>{{ $item["satuan"] }}</td>
            <td align="right">{{ number_format($item["harga_jual"]) }}</td>

            <td align="center">
                <div class="qty-box">
                    <button
                        class="pos-btn"
                        wire:click="decrementQty({{ $id }})"
                    >
                        −
                    </button>
                    {{ $item["qty"] }}
                    <button
                        class="pos-btn"
                        wire:click="incrementQty({{ $id }})"
                    >
                        +
                    </button>
                </div>
            </td>

            <td align="right">{{ number_format($item["subtotal"]) }}</td>

            <td align="center">
                <button
                    class="pos-btn pos-btn-danger"
                    wire:click="removeFromCart({{ $id }})"
                >
                    ✕
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" align="center">Keranjang kosong</td>
        </tr>
        @endforelse
    </table>

    <div class="pos-divider"></div>

    {{-- ===============================
        TOTAL & PEMBAYARAN
    =============================== --}}
    <div class="pos-section" style="max-width: 300px">
        <p class="pos-total">Total: Rp {{ number_format($this->total) }}</p>

        <input
            type="number"
            wire:model.live="bayar"
            placeholder="Bayar"
            class="pos-input"
        />
        <br /><br />

        <p>Kembalian: Rp {{ number_format($this->kembalian) }}</p>

        <button class="pos-btn pos-btn-primary" wire:click="simpanPenjualan">
            Simpan Penjualan
        </button>
    </div>
</x-filament::page>
