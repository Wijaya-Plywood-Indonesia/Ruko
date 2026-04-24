<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ReturnPenjualan;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReturnPenjualanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReturnPenjualan');
    }

    public function view(AuthUser $authUser, ReturnPenjualan $returnPenjualan): bool
    {
        return $authUser->can('View:ReturnPenjualan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReturnPenjualan');
    }

    public function update(AuthUser $authUser, ReturnPenjualan $returnPenjualan): bool
    {
        return $authUser->can('Update:ReturnPenjualan');
    }

    public function delete(AuthUser $authUser, ReturnPenjualan $returnPenjualan): bool
    {
        return $authUser->can('Delete:ReturnPenjualan');
    }

    public function restore(AuthUser $authUser, ReturnPenjualan $returnPenjualan): bool
    {
        return $authUser->can('Restore:ReturnPenjualan');
    }

    public function forceDelete(AuthUser $authUser, ReturnPenjualan $returnPenjualan): bool
    {
        return $authUser->can('ForceDelete:ReturnPenjualan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReturnPenjualan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReturnPenjualan');
    }

    public function replicate(AuthUser $authUser, ReturnPenjualan $returnPenjualan): bool
    {
        return $authUser->can('Replicate:ReturnPenjualan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReturnPenjualan');
    }

}