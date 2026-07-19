<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Bible;
use Illuminate\Auth\Access\HandlesAuthorization;

class BiblePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Bible');
    }

    public function view(AuthUser $authUser, Bible $bible): bool
    {
        return $authUser->can('View:Bible');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Bible');
    }

    public function update(AuthUser $authUser, Bible $bible): bool
    {
        return $authUser->can('Update:Bible');
    }

    public function delete(AuthUser $authUser, Bible $bible): bool
    {
        return $authUser->can('Delete:Bible');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Bible');
    }

    public function restore(AuthUser $authUser, Bible $bible): bool
    {
        return $authUser->can('Restore:Bible');
    }

    public function forceDelete(AuthUser $authUser, Bible $bible): bool
    {
        return $authUser->can('ForceDelete:Bible');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Bible');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Bible');
    }

    public function replicate(AuthUser $authUser, Bible $bible): bool
    {
        return $authUser->can('Replicate:Bible');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Bible');
    }

}