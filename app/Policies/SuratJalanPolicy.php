<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SuratJalan;
use Illuminate\Auth\Access\HandlesAuthorization;

class SuratJalanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SuratJalan');
    }

    public function view(AuthUser $authUser, SuratJalan $suratJalan): bool
    {
        return $authUser->can('View:SuratJalan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SuratJalan');
    }

    public function update(AuthUser $authUser, SuratJalan $suratJalan): bool
    {
        return $authUser->can('Update:SuratJalan');
    }

    public function delete(AuthUser $authUser, SuratJalan $suratJalan): bool
    {
        return $authUser->can('Delete:SuratJalan');
    }

    public function restore(AuthUser $authUser, SuratJalan $suratJalan): bool
    {
        return $authUser->can('Restore:SuratJalan');
    }

    public function forceDelete(AuthUser $authUser, SuratJalan $suratJalan): bool
    {
        return $authUser->can('ForceDelete:SuratJalan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SuratJalan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SuratJalan');
    }

    public function replicate(AuthUser $authUser, SuratJalan $suratJalan): bool
    {
        return $authUser->can('Replicate:SuratJalan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SuratJalan');
    }

}