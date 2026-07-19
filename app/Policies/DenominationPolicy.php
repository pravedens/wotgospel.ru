<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Denomination;
use Illuminate\Auth\Access\HandlesAuthorization;

class DenominationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Denomination');
    }

    public function view(AuthUser $authUser, Denomination $denomination): bool
    {
        return $authUser->can('View:Denomination');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Denomination');
    }

    public function update(AuthUser $authUser, Denomination $denomination): bool
    {
        return $authUser->can('Update:Denomination');
    }

    public function delete(AuthUser $authUser, Denomination $denomination): bool
    {
        return $authUser->can('Delete:Denomination');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Denomination');
    }

    public function restore(AuthUser $authUser, Denomination $denomination): bool
    {
        return $authUser->can('Restore:Denomination');
    }

    public function forceDelete(AuthUser $authUser, Denomination $denomination): bool
    {
        return $authUser->can('ForceDelete:Denomination');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Denomination');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Denomination');
    }

    public function replicate(AuthUser $authUser, Denomination $denomination): bool
    {
        return $authUser->can('Replicate:Denomination');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Denomination');
    }

}