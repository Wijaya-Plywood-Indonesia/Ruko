<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BarangMasuk;
use Illuminate\Auth\Access\HandlesAuthorization;

class BarangMasukPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BarangMasuk');
    }

    public function view(AuthUser $authUser, BarangMasuk $barangMasuk): bool
    {
        return $authUser->can('View:BarangMasuk');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BarangMasuk');
    }

    public function update(AuthUser $authUser, BarangMasuk $barangMasuk): bool
    {
        return $authUser->can('Update:BarangMasuk');
    }

    public function delete(AuthUser $authUser, BarangMasuk $barangMasuk): bool
    {
        return $authUser->can('Delete:BarangMasuk');
    }

    public function restore(AuthUser $authUser, BarangMasuk $barangMasuk): bool
    {
        return $authUser->can('Restore:BarangMasuk');
    }

    public function forceDelete(AuthUser $authUser, BarangMasuk $barangMasuk): bool
    {
        return $authUser->can('ForceDelete:BarangMasuk');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BarangMasuk');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BarangMasuk');
    }

    public function replicate(AuthUser $authUser, BarangMasuk $barangMasuk): bool
    {
        return $authUser->can('Replicate:BarangMasuk');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BarangMasuk');
    }

}