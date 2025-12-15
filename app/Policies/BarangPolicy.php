<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Barang;
use Illuminate\Auth\Access\HandlesAuthorization;

class BarangPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Barang');
    }

    public function view(AuthUser $authUser, Barang $barang): bool
    {
        return $authUser->can('View:Barang');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Barang');
    }

    public function update(AuthUser $authUser, Barang $barang): bool
    {
        return $authUser->can('Update:Barang');
    }

    public function delete(AuthUser $authUser, Barang $barang): bool
    {
        return $authUser->can('Delete:Barang');
    }

    public function restore(AuthUser $authUser, Barang $barang): bool
    {
        return $authUser->can('Restore:Barang');
    }

    public function forceDelete(AuthUser $authUser, Barang $barang): bool
    {
        return $authUser->can('ForceDelete:Barang');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Barang');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Barang');
    }

    public function replicate(AuthUser $authUser, Barang $barang): bool
    {
        return $authUser->can('Replicate:Barang');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Barang');
    }

}