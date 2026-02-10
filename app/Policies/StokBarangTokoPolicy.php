<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StokBarangToko;
use Illuminate\Auth\Access\HandlesAuthorization;

class StokBarangTokoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StokBarangToko');
    }

    public function view(AuthUser $authUser, StokBarangToko $stokBarangToko): bool
    {
        return $authUser->can('View:StokBarangToko');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StokBarangToko');
    }

    public function update(AuthUser $authUser, StokBarangToko $stokBarangToko): bool
    {
        return $authUser->can('Update:StokBarangToko');
    }

    public function delete(AuthUser $authUser, StokBarangToko $stokBarangToko): bool
    {
        return $authUser->can('Delete:StokBarangToko');
    }

    public function restore(AuthUser $authUser, StokBarangToko $stokBarangToko): bool
    {
        return $authUser->can('Restore:StokBarangToko');
    }

    public function forceDelete(AuthUser $authUser, StokBarangToko $stokBarangToko): bool
    {
        return $authUser->can('ForceDelete:StokBarangToko');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StokBarangToko');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StokBarangToko');
    }

    public function replicate(AuthUser $authUser, StokBarangToko $stokBarangToko): bool
    {
        return $authUser->can('Replicate:StokBarangToko');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StokBarangToko');
    }

}