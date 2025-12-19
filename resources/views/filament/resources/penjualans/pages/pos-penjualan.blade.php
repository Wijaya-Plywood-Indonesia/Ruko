<x-filament::page>
    {{-- =========================================================
        POS CASHIER STYLE (KHUSUS PAGE INI)
        ========================================================= --}}
    <style>
        /* ===============================
           GLOBAL POS LAYOUT
           =============================== */
        .pos-wrapper {
            font-family: ui-sans-serif, system-ui, -apple-system;
            background: #f9fafb;
            padding: 16px;
        }

        .pos-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        /* ===============================
           INPUT & SELECT
           =============================== */
        .pos-input,
        .pos-select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: #fff;
            font-size: 14px;
        }

        .pos-input:focus,
        .pos-select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }

        /* ===============================
           SEARCH DROPDOWN
           =============================== */
        .pos-search-box {
            width: 300px;
            background: #fff;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            overflow: hidden;
            margin-top: 4px;
        }

        .pos-search-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
        }

        .pos-search-item:hover {
            background: #f3f4f6;
        }

        /* ===============================
           DIVIDER
           =============================== */
        .pos-divider {
            border-top: 1px dashed #d1d5db;
            margin: 24px 0;
        }

        /* ===============================
           TABLE (CART)
           =============================== */
        .pos-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        .pos-table th {
            background: #111827;
            color: #fff;
            font-weight: 600;
            padding: 10px;
            font-size: 13px;
        }

        .pos-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .pos-table tr:last-child td {
            border-bottom: none;
        }

        /* ===============================
   QTY BUTTON
   =============================== */
        .qty-btn {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        /* ➖ MINUS */
        .qty-btn.minus {
            background: #fee2e2; /* merah muda */
            color: #b91c1c;
        }

        .qty-btn.minus:hover {
            background: #fecaca;
        }

        /* ➕ PLUS */
        .qty-btn.plus {
            background: #dcfce7; /* hijau muda */
            color: #166534;
        }

        .qty-btn.plus:hover {
            background: #bbf7d0;
        }

        /* Disabled state (opsional) */
        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        /* ===============================
           BUTTON
           =============================== */
        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 8px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        /* ===============================
           TOTAL
           =============================== */
        .pos-total {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }

        .pos-kembalian {
            font-size: 16px;
            font-weight: 600;
            color: #059669;
        }
    </style>

    {{-- =========================================================
        WRAPPER
        ========================================================= --}}
    <div class="pos-wrapper">
        {{-- ===================== TITLE ===================== --}}
        <h2 class="pos-title">Point of Sale</h2>

        {{-- ===================== SEARCH ===================== --}}
        <input
            type="text"
            wire:model.live="search"
            placeholder="Cari barang / barcode"
            class="pos-input"
            style="max-width: 300px"
        />

        @if ($searchResults->isNotEmpty())
        <div class="pos-search-box">
            @foreach ($searchResults as $barang)
            <div
                wire:click="selectBarang({{ $barang->id }})"
                class="pos-search-item"
            >
                <strong>{{ $barang->nama_barang }}</strong
                ><br />
                <small>Rp {{ number_format($barang->harga_jual) }}</small>
            </div>
            @endforeach
        </div>
        @endif
        <div style="margin-top: 12px; font-size: 14px">
            <strong>Kasir:</strong> {{ auth()->user()->name }}
        </div>
        <div class="pos-divider"></div>

        {{-- ===================== CART ===================== --}}
        <table class="pos-table">
            <thead>
                <tr>
                    <th>Barang</th>
                    <th>Satuan</th>
                    <th>Harga</th>
                    <th>Harga Jual</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cart as $id => $item)
                <tr>
                    <td>{{ $item["nama_barang"] }}</td>
                    <td align="center">{{ $item["satuan"] }}</td>
                    <td>Rp. {{ number_format($item["harga_awal"]) }}</td>

                    <td>
                        Rp.
                        <input
                            type="number"
                            wire:model.lazy="cart.{{ $id }}.harga_jual"
                            wire:change="updateHargaJual({{ $id }})"
                        />
                    </td>
                    <td align="center">
                        <button
                            wire:click="decrementQty({{ $id }})"
                            class="qty-btn minus"
                        >
                            −
                        </button>

                        <span style="margin: 0 6px; font-weight: 600">
                            {{ $item["qty"] }}
                        </span>

                        <button
                            wire:click="incrementQty({{ $id }})"
                            class="qty-btn plus"
                        >
                            +
                        </button>
                    </td>

                    <td align="right">
                        Rp. {{ number_format($item["subtotal"]) }}
                    </td>
                    <td align="center">
                        <button
                            wire:click="removeFromCart({{ $id }})"
                            class="btn-danger"
                        >
                            ❌
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" align="center">Keranjang kosong</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="pos-divider"></div>

        {{-- ===================== CUSTOMER ===================== --}}
        <h4>Data Pelanggan</h4>

        <div
            style="
                display: grid;
                grid-template-columns: 1fr 2fr 1fr;
                gap: 8px;
                max-width: 600px;
            "
        >
            <input
                wire:model.live="nama_customer"
                class="pos-input"
                placeholder="Nama customer"
            />
            <input
                wire:model.live="alamat"
                class="pos-input"
                placeholder="Alamat"
            />
            <select wire:model.live="metode_pembayaran" class="pos-select">
                <option value="TUNAI">TUNAI</option>
                <option value="TRANSFER">TRANSFER</option>
            </select>
            {{-- Muncul hanya jika TRANSFER --}}
            @if ($metode_pembayaran === 'TRANSFER')
            <div class="mt-2 space-y-2">
                <input
                    type="text"
                    wire:model.live="bank"
                    placeholder="Nama Bank"
                    class="pos-input"
                />

                <input
                    type="text"
                    wire:model.live="no_rekening"
                    placeholder="No Rekening"
                    class="pos-input"
                />
            </div>
            @endif
        </div>

        {{-- ===================== Pengiriman ===================== --}}

        <div class="pos-divider"></div>
        <h4 style="margin-top: 16px">Pengiriman</h4>

        <div
            style="
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                max-width: 600px;
            "
        >
            <input
                wire:model.live="kendaraan"
                class="pos-input"
                placeholder="Kendaraan (opsional)"
            />

            <input
                wire:model.live="nama_sopir"
                class="pos-input"
                placeholder="Nama Sopir (opsional)"
            />
        </div>
        <div class="pos-divider"></div>

        {{-- ===================== TOTAL ===================== --}}
        <h3 class="pos-total">Total: Rp {{ number_format($this->total) }}</h3>

        <div style="max-width: 300px; margin-top: 8px">
            <input
                type="number"
                wire:model.lazy="bayar"
                class="pos-input"
                placeholder="Bayar"
            />
            <p class="pos-kembalian">
                Kembalian: Rp {{ number_format($this->kembalian) }}
            </p>
        </div>

        <button wire:click="simpanPenjualan" class="btn-primary">
            Simpan Penjualan
        </button>
    </div>

    {{-- =========================================================
        OFFLINE SAFE (LOCAL STORAGE)
        ========================================================= --}}
    <script>
        document.addEventListener('livewire:update', () => {
            localStorage.setItem(
                'pos_cart',
                JSON.stringify(@json($cart))
            );
        });

        window.addEventListener('load', () => {
            const cart = localStorage.getItem('pos_cart');
            if (cart) {
                Livewire.dispatch('restoreCart', {
                    cart: JSON.parse(cart)
                });
            }
        });
    </script>
</x-filament::page>
