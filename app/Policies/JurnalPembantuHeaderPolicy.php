<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\JurnalPembantuHeader;
use Illuminate\Auth\Access\HandlesAuthorization;

class JurnalPembantuHeaderPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JurnalPembantuHeader');
    }

    public function view(AuthUser $authUser, JurnalPembantuHeader $jurnalPembantuHeader): bool
    {
        return $authUser->can('View:JurnalPembantuHeader');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JurnalPembantuHeader');
    }

    public function update(AuthUser $authUser, JurnalPembantuHeader $jurnalPembantuHeader): bool
    {
        return $authUser->can('Update:JurnalPembantuHeader');
    }

    public function delete(AuthUser $authUser, JurnalPembantuHeader $jurnalPembantuHeader): bool
    {
        return $authUser->can('Delete:JurnalPembantuHeader');
    }

    public function restore(AuthUser $authUser, JurnalPembantuHeader $jurnalPembantuHeader): bool
    {
        return $authUser->can('Restore:JurnalPembantuHeader');
    }

    public function forceDelete(AuthUser $authUser, JurnalPembantuHeader $jurnalPembantuHeader): bool
    {
        return $authUser->can('ForceDelete:JurnalPembantuHeader');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JurnalPembantuHeader');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JurnalPembantuHeader');
    }

    public function replicate(AuthUser $authUser, JurnalPembantuHeader $jurnalPembantuHeader): bool
    {
        return $authUser->can('Replicate:JurnalPembantuHeader');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JurnalPembantuHeader');
    }

}