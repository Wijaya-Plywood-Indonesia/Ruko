<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Pembeli;
use Illuminate\Auth\Access\HandlesAuthorization;

class PembeliPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Pembeli');
    }

    public function view(AuthUser $authUser, Pembeli $pembeli): bool
    {
        return $authUser->can('View:Pembeli');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Pembeli');
    }

    public function update(AuthUser $authUser, Pembeli $pembeli): bool
    {
        return $authUser->can('Update:Pembeli');
    }

    public function delete(AuthUser $authUser, Pembeli $pembeli): bool
    {
        return $authUser->can('Delete:Pembeli');
    }

    public function restore(AuthUser $authUser, Pembeli $pembeli): bool
    {
        return $authUser->can('Restore:Pembeli');
    }

    public function forceDelete(AuthUser $authUser, Pembeli $pembeli): bool
    {
        return $authUser->can('ForceDelete:Pembeli');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Pembeli');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pembeli');
    }

    public function replicate(AuthUser $authUser, Pembeli $pembeli): bool
    {
        return $authUser->can('Replicate:Pembeli');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pembeli');
    }

}