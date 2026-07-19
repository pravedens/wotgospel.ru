<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Conference;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConferencePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Conference');
    }

    public function view(AuthUser $authUser, Conference $conference): bool
    {
        return $authUser->can('View:Conference');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Conference');
    }

    public function update(AuthUser $authUser, Conference $conference): bool
    {
        return $authUser->can('Update:Conference');
    }

    public function delete(AuthUser $authUser, Conference $conference): bool
    {
        return $authUser->can('Delete:Conference');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Conference');
    }

    public function restore(AuthUser $authUser, Conference $conference): bool
    {
        return $authUser->can('Restore:Conference');
    }

    public function forceDelete(AuthUser $authUser, Conference $conference): bool
    {
        return $authUser->can('ForceDelete:Conference');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Conference');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Conference');
    }

    public function replicate(AuthUser $authUser, Conference $conference): bool
    {
        return $authUser->can('Replicate:Conference');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Conference');
    }

}