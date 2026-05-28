<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SatuanKonversi;
use Illuminate\Auth\Access\HandlesAuthorization;

class SatuanKonversiPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SatuanKonversi');
    }

    public function view(AuthUser $authUser, SatuanKonversi $satuanKonversi): bool
    {
        return $authUser->can('View:SatuanKonversi');
    }

    public function before(AuthUser $user, string $ability): ?bool
    {
        // Atau jika Anda menggunakan role super_admin
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SatuanKonversi');
    }

    public function update(AuthUser $authUser, SatuanKonversi $satuanKonversi): bool
    {
        return $authUser->can('Update:SatuanKonversi');
    }

    public function delete(AuthUser $authUser, SatuanKonversi $satuanKonversi): bool
    {
        return $authUser->can('Delete:SatuanKonversi');
    }

    public function restore(AuthUser $authUser, SatuanKonversi $satuanKonversi): bool
    {
        return $authUser->can('Restore:SatuanKonversi');
    }

    public function forceDelete(AuthUser $authUser, SatuanKonversi $satuanKonversi): bool
    {
        return $authUser->can('ForceDelete:SatuanKonversi');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SatuanKonversi');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SatuanKonversi');
    }

    public function replicate(AuthUser $authUser, SatuanKonversi $satuanKonversi): bool
    {
        return $authUser->can('Replicate:SatuanKonversi');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SatuanKonversi');
    }
}
