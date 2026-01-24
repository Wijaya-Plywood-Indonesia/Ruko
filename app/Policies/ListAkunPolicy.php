<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ListAkun;
use Illuminate\Auth\Access\HandlesAuthorization;

class ListAkunPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ListAkun');
    }

    public function view(AuthUser $authUser, ListAkun $listAkun): bool
    {
        return $authUser->can('View:ListAkun');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ListAkun');
    }

    public function update(AuthUser $authUser, ListAkun $listAkun): bool
    {
        return $authUser->can('Update:ListAkun');
    }

    public function delete(AuthUser $authUser, ListAkun $listAkun): bool
    {
        return $authUser->can('Delete:ListAkun');
    }

    public function restore(AuthUser $authUser, ListAkun $listAkun): bool
    {
        return $authUser->can('Restore:ListAkun');
    }

    public function forceDelete(AuthUser $authUser, ListAkun $listAkun): bool
    {
        return $authUser->can('ForceDelete:ListAkun');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ListAkun');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ListAkun');
    }

    public function replicate(AuthUser $authUser, ListAkun $listAkun): bool
    {
        return $authUser->can('Replicate:ListAkun');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ListAkun');
    }

}