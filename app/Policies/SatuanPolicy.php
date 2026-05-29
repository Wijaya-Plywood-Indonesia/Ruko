<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Satuan;
use Illuminate\Auth\Access\HandlesAuthorization;

class SatuanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Satuan');
    }

    public function view(AuthUser $authUser, Satuan $satuan): bool
    {
        return $authUser->can('View:Satuan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Satuan');
    }

    public function update(AuthUser $authUser, Satuan $satuan): bool
    {
        return $authUser->can('Update:Satuan');
    }

    public function delete(AuthUser $authUser, Satuan $satuan): bool
    {
        return $authUser->can('Delete:Satuan');
    }

    public function restore(AuthUser $authUser, Satuan $satuan): bool
    {
        return $authUser->can('Restore:Satuan');
    }

    public function forceDelete(AuthUser $authUser, Satuan $satuan): bool
    {
        return $authUser->can('ForceDelete:Satuan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Satuan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Satuan');
    }

    public function replicate(AuthUser $authUser, Satuan $satuan): bool
    {
        return $authUser->can('Replicate:Satuan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Satuan');
    }

}