<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DetailSuratJalan;
use Illuminate\Auth\Access\HandlesAuthorization;

class DetailSuratJalanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DetailSuratJalan');
    }

    public function view(AuthUser $authUser, DetailSuratJalan $detailSuratJalan): bool
    {
        return $authUser->can('View:DetailSuratJalan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DetailSuratJalan');
    }

    public function update(AuthUser $authUser, DetailSuratJalan $detailSuratJalan): bool
    {
        return $authUser->can('Update:DetailSuratJalan');
    }

    public function delete(AuthUser $authUser, DetailSuratJalan $detailSuratJalan): bool
    {
        return $authUser->can('Delete:DetailSuratJalan');
    }

    public function restore(AuthUser $authUser, DetailSuratJalan $detailSuratJalan): bool
    {
        return $authUser->can('Restore:DetailSuratJalan');
    }

    public function forceDelete(AuthUser $authUser, DetailSuratJalan $detailSuratJalan): bool
    {
        return $authUser->can('ForceDelete:DetailSuratJalan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DetailSuratJalan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DetailSuratJalan');
    }

    public function replicate(AuthUser $authUser, DetailSuratJalan $detailSuratJalan): bool
    {
        return $authUser->can('Replicate:DetailSuratJalan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DetailSuratJalan');
    }

}