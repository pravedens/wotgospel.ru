<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Friend;
use Illuminate\Auth\Access\HandlesAuthorization;

class FriendPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Friend');
    }

    public function view(AuthUser $authUser, Friend $friend): bool
    {
        return $authUser->can('View:Friend');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Friend');
    }

    public function update(AuthUser $authUser, Friend $friend): bool
    {
        return $authUser->can('Update:Friend');
    }

    public function delete(AuthUser $authUser, Friend $friend): bool
    {
        return $authUser->can('Delete:Friend');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Friend');
    }

    public function restore(AuthUser $authUser, Friend $friend): bool
    {
        return $authUser->can('Restore:Friend');
    }

    public function forceDelete(AuthUser $authUser, Friend $friend): bool
    {
        return $authUser->can('ForceDelete:Friend');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Friend');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Friend');
    }

    public function replicate(AuthUser $authUser, Friend $friend): bool
    {
        return $authUser->can('Replicate:Friend');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Friend');
    }

}