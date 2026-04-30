<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Presensi;
use Illuminate\Auth\Access\HandlesAuthorization;

class PresensiPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Presensi');
    }

    public function view(AuthUser $authUser, Presensi $presensi): bool
    {
        return $authUser->can('View:Presensi');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Presensi');
    }

    public function update(AuthUser $authUser, Presensi $presensi): bool
    {
        return $authUser->can('Update:Presensi');
    }

    public function delete(AuthUser $authUser, Presensi $presensi): bool
    {
        return $authUser->can('Delete:Presensi');
    }

    public function restore(AuthUser $authUser, Presensi $presensi): bool
    {
        return $authUser->can('Restore:Presensi');
    }

    public function forceDelete(AuthUser $authUser, Presensi $presensi): bool
    {
        return $authUser->can('ForceDelete:Presensi');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Presensi');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Presensi');
    }

    public function replicate(AuthUser $authUser, Presensi $presensi): bool
    {
        return $authUser->can('Replicate:Presensi');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Presensi');
    }

}