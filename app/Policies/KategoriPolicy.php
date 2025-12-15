<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Kategori;
use Illuminate\Auth\Access\HandlesAuthorization;

class KategoriPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Kategori');
    }

    public function view(AuthUser $authUser, Kategori $kategori): bool
    {
        return $authUser->can('View:Kategori');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Kategori');
    }

    public function update(AuthUser $authUser, Kategori $kategori): bool
    {
        return $authUser->can('Update:Kategori');
    }

    public function delete(AuthUser $authUser, Kategori $kategori): bool
    {
        return $authUser->can('Delete:Kategori');
    }

    public function restore(AuthUser $authUser, Kategori $kategori): bool
    {
        return $authUser->can('Restore:Kategori');
    }

    public function forceDelete(AuthUser $authUser, Kategori $kategori): bool
    {
        return $authUser->can('ForceDelete:Kategori');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Kategori');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Kategori');
    }

    public function replicate(AuthUser $authUser, Kategori $kategori): bool
    {
        return $authUser->can('Replicate:Kategori');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Kategori');
    }

}