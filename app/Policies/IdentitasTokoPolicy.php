<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\IdentitasToko;
use Illuminate\Auth\Access\HandlesAuthorization;

class IdentitasTokoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IdentitasToko');
    }

    public function view(AuthUser $authUser, IdentitasToko $identitasToko): bool
    {
        return $authUser->can('View:IdentitasToko');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IdentitasToko');
    }

    public function update(AuthUser $authUser, IdentitasToko $identitasToko): bool
    {
        return $authUser->can('Update:IdentitasToko');
    }

    public function delete(AuthUser $authUser, IdentitasToko $identitasToko): bool
    {
        return $authUser->can('Delete:IdentitasToko');
    }

    public function restore(AuthUser $authUser, IdentitasToko $identitasToko): bool
    {
        return $authUser->can('Restore:IdentitasToko');
    }

    public function forceDelete(AuthUser $authUser, IdentitasToko $identitasToko): bool
    {
        return $authUser->can('ForceDelete:IdentitasToko');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IdentitasToko');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IdentitasToko');
    }

    public function replicate(AuthUser $authUser, IdentitasToko $identitasToko): bool
    {
        return $authUser->can('Replicate:IdentitasToko');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IdentitasToko');
    }

}