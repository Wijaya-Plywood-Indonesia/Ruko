<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StokLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class StokLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StokLog');
    }

    public function view(AuthUser $authUser, StokLog $stokLog): bool
    {
        return $authUser->can('View:StokLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StokLog');
    }

    public function update(AuthUser $authUser, StokLog $stokLog): bool
    {
        return $authUser->can('Update:StokLog');
    }

    public function delete(AuthUser $authUser, StokLog $stokLog): bool
    {
        return $authUser->can('Delete:StokLog');
    }

    public function restore(AuthUser $authUser, StokLog $stokLog): bool
    {
        return $authUser->can('Restore:StokLog');
    }

    public function forceDelete(AuthUser $authUser, StokLog $stokLog): bool
    {
        return $authUser->can('ForceDelete:StokLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StokLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StokLog');
    }

    public function replicate(AuthUser $authUser, StokLog $stokLog): bool
    {
        return $authUser->can('Replicate:StokLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StokLog');
    }

}