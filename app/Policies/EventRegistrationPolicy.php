<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EventRegistration;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventRegistrationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EventRegistration');
    }

    public function view(AuthUser $authUser, EventRegistration $eventRegistration): bool
    {
        return $authUser->can('View:EventRegistration');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EventRegistration');
    }

    public function update(AuthUser $authUser, EventRegistration $eventRegistration): bool
    {
        return $authUser->can('Update:EventRegistration');
    }

    public function delete(AuthUser $authUser, EventRegistration $eventRegistration): bool
    {
        return $authUser->can('Delete:EventRegistration');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EventRegistration');
    }

    public function restore(AuthUser $authUser, EventRegistration $eventRegistration): bool
    {
        return $authUser->can('Restore:EventRegistration');
    }

    public function forceDelete(AuthUser $authUser, EventRegistration $eventRegistration): bool
    {
        return $authUser->can('ForceDelete:EventRegistration');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EventRegistration');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EventRegistration');
    }

    public function replicate(AuthUser $authUser, EventRegistration $eventRegistration): bool
    {
        return $authUser->can('Replicate:EventRegistration');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EventRegistration');
    }

}