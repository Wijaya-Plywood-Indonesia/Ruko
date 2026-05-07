<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Pembelian;
use Illuminate\Auth\Access\HandlesAuthorization;

class PembelianPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Pembelian');
    }

    public function view(AuthUser $authUser, Pembelian $pembelian): bool
    {
        return $authUser->can('View:Pembelian');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Pembelian');
    }

    public function update(AuthUser $authUser, Pembelian $pembelian): bool
    {
        return $authUser->can('Update:Pembelian');
    }

    public function delete(AuthUser $authUser, Pembelian $pembelian): bool
    {
        return $authUser->can('Delete:Pembelian');
    }

    public function restore(AuthUser $authUser, Pembelian $pembelian): bool
    {
        return $authUser->can('Restore:Pembelian');
    }

    public function forceDelete(AuthUser $authUser, Pembelian $pembelian): bool
    {
        return $authUser->can('ForceDelete:Pembelian');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Pembelian');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pembelian');
    }

    public function replicate(AuthUser $authUser, Pembelian $pembelian): bool
    {
        return $authUser->can('Replicate:Pembelian');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pembelian');
    }

}