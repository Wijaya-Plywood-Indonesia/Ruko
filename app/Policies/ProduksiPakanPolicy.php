<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProduksiPakan;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProduksiPakanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProduksiPakan');
    }

    public function view(AuthUser $authUser, ProduksiPakan $produksiPakan): bool
    {
        return $authUser->can('View:ProduksiPakan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProduksiPakan');
    }

    public function update(AuthUser $authUser, ProduksiPakan $produksiPakan): bool
    {
        return $authUser->can('Update:ProduksiPakan');
    }

    public function delete(AuthUser $authUser, ProduksiPakan $produksiPakan): bool
    {
        return $authUser->can('Delete:ProduksiPakan');
    }

    public function restore(AuthUser $authUser, ProduksiPakan $produksiPakan): bool
    {
        return $authUser->can('Restore:ProduksiPakan');
    }

    public function forceDelete(AuthUser $authUser, ProduksiPakan $produksiPakan): bool
    {
        return $authUser->can('ForceDelete:ProduksiPakan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProduksiPakan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProduksiPakan');
    }

    public function replicate(AuthUser $authUser, ProduksiPakan $produksiPakan): bool
    {
        return $authUser->can('Replicate:ProduksiPakan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProduksiPakan');
    }

}