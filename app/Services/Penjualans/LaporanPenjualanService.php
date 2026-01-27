<?php

namespace App\Services\Penjualans;

use App\Models\Penjualan;
use App\Models\DetailPenjualan;

class LaporanPenjualanService
{
    public function get(
        string $type = 'main',
        ?string $from = null,
        ?string $to = null
    ): array {
        return match ($type) {
            'detail' => $this->detail($from, $to),
            'full'   => $this->full($from, $to),
            default  => $this->main($from, $to),
        };
    }

    protected function baseQuery(?string $from, ?string $to)
    {
        return Penjualan::query()
            ->whereNotNull('validated_by')
            ->when($from && $to, fn ($q) =>
                $q->whereBetween('created_at', [
                    $from . ' 00:00:00',
                    $to . ' 23:59:59',
                ])
            )
            ->with(['user', 'validator']);
    }

    protected function main(?string $from, ?string $to): array
    {
        return $this->baseQuery($from, $to)
            ->get()
            ->map(fn ($p) => [
                'no_nota'           => $p->no_nota,
                'tanggal'           => $p->tanggal,
                'nama_customer'     => $p->nama_customer,
                'member'            => $p->is_member ? 'MEMBER' : 'REGULAR',
                'metode_pembayaran' => $p->metode_pembayaran,
                'total'             => $p->total,
                'kasir'             => $p->user?->name,
                'validator'         => $p->validator?->name,
                'status_transaksi'  => $p->status_transaksi,
            ])
            ->toArray();
    }

    protected function detail(?string $from, ?string $to): array
    {
        return $this->baseQuery($from, $to)
            ->get()
            ->map(fn ($p) => [
                'no_nota'       => $p->no_nota,
                'tanggal'       => $p->tanggal,
                'nama_customer' => $p->nama_customer,
                'kasir'         => $p->user?->name,
                'status'        => $p->status_transaksi,
                'items'         => $this->detailItems($p->id),
            ])
            ->toArray();
    }

    protected function full(?string $from, ?string $to): array
    {
        return $this->baseQuery($from, $to)
            ->get()
            ->map(fn ($p) => [
                'header' => [
                    'no_nota'       => $p->no_nota,
                    'tanggal'       => $p->tanggal,
                    'customer'      => $p->nama_customer,
                    'total'         => $p->total,
                    'kasir'         => $p->user?->name,
                ],
                'items' => $this->detailItems($p->id),
            ])
            ->toArray();
    }

    protected function detailItems(int $penjualanId): array
    {
        return DetailPenjualan::where('penjualan_id', $penjualanId)
            ->get()
            ->map(fn ($d) => [
                'nama_barang'  => $d->nama_barang,
                'harga_jual'   => $d->harga_jual,
                'qty'          => $d->qty . ' ' . $d->satuan,
                'diskon'       => (string) ($d->potongan ?? 0),
                'subtotal'     => $d->subtotal,
            ])
            ->toArray();
    }
}
