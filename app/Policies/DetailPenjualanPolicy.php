<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DetailPenjualan;

class DetailPenjualanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DetailPenjualan $detail): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true; // create tidak punya $detail
    }

    public function update(User $user, DetailPenjualan $detail): bool
    {
        return $detail->penjualan?->status_transaksi !== 'LUNAS';
    }

    public function delete(User $user, DetailPenjualan $detail): bool
    {
        return $detail->penjualan?->status_transaksi !== 'LUNAS';
    }
}
