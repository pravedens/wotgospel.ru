<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LiveStream;
use Illuminate\Auth\Access\HandlesAuthorization;

class LiveStreamPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LiveStream');
    }

    public function view(AuthUser $authUser, LiveStream $liveStream): bool
    {
        return $authUser->can('View:LiveStream');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LiveStream');
    }

    public function update(AuthUser $authUser, LiveStream $liveStream): bool
    {
        return $authUser->can('Update:LiveStream');
    }

    public function delete(AuthUser $authUser, LiveStream $liveStream): bool
    {
        return $authUser->can('Delete:LiveStream');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LiveStream');
    }

    public function restore(AuthUser $authUser, LiveStream $liveStream): bool
    {
        return $authUser->can('Restore:LiveStream');
    }

    public function forceDelete(AuthUser $authUser, LiveStream $liveStream): bool
    {
        return $authUser->can('ForceDelete:LiveStream');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LiveStream');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LiveStream');
    }

    public function replicate(AuthUser $authUser, LiveStream $liveStream): bool
    {
        return $authUser->can('Replicate:LiveStream');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LiveStream');
    }

}