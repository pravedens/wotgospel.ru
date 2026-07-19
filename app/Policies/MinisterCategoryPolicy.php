<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MinisterCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class MinisterCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MinisterCategory');
    }

    public function view(AuthUser $authUser, MinisterCategory $ministerCategory): bool
    {
        return $authUser->can('View:MinisterCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MinisterCategory');
    }

    public function update(AuthUser $authUser, MinisterCategory $ministerCategory): bool
    {
        return $authUser->can('Update:MinisterCategory');
    }

    public function delete(AuthUser $authUser, MinisterCategory $ministerCategory): bool
    {
        return $authUser->can('Delete:MinisterCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MinisterCategory');
    }

    public function restore(AuthUser $authUser, MinisterCategory $ministerCategory): bool
    {
        return $authUser->can('Restore:MinisterCategory');
    }

    public function forceDelete(AuthUser $authUser, MinisterCategory $ministerCategory): bool
    {
        return $authUser->can('ForceDelete:MinisterCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MinisterCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MinisterCategory');
    }

    public function replicate(AuthUser $authUser, MinisterCategory $ministerCategory): bool
    {
        return $authUser->can('Replicate:MinisterCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MinisterCategory');
    }

}