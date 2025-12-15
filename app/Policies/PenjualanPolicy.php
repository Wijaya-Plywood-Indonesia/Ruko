<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Penjualan;
use Illuminate\Auth\Access\HandlesAuthorization;

class PenjualanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Penjualan');
    }

    public function view(AuthUser $authUser, Penjualan $penjualan): bool
    {
        return $authUser->can('View:Penjualan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Penjualan');
    }

    public function update(AuthUser $authUser, Penjualan $penjualan): bool
    {
        return $authUser->can('Update:Penjualan');
    }

    public function delete(AuthUser $authUser, Penjualan $penjualan): bool
    {
        return $authUser->can('Delete:Penjualan');
    }

    public function restore(AuthUser $authUser, Penjualan $penjualan): bool
    {
        return $authUser->can('Restore:Penjualan');
    }

    public function forceDelete(AuthUser $authUser, Penjualan $penjualan): bool
    {
        return $authUser->can('ForceDelete:Penjualan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Penjualan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Penjualan');
    }

    public function replicate(AuthUser $authUser, Penjualan $penjualan): bool
    {
        return $authUser->can('Replicate:Penjualan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Penjualan');
    }

}