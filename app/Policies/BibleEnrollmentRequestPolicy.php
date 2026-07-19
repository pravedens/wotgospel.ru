<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BibleEnrollmentRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class BibleEnrollmentRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BibleEnrollmentRequest');
    }

    public function view(AuthUser $authUser, BibleEnrollmentRequest $bibleEnrollmentRequest): bool
    {
        return $authUser->can('View:BibleEnrollmentRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BibleEnrollmentRequest');
    }

    public function update(AuthUser $authUser, BibleEnrollmentRequest $bibleEnrollmentRequest): bool
    {
        return $authUser->can('Update:BibleEnrollmentRequest');
    }

    public function delete(AuthUser $authUser, BibleEnrollmentRequest $bibleEnrollmentRequest): bool
    {
        return $authUser->can('Delete:BibleEnrollmentRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BibleEnrollmentRequest');
    }

    public function restore(AuthUser $authUser, BibleEnrollmentRequest $bibleEnrollmentRequest): bool
    {
        return $authUser->can('Restore:BibleEnrollmentRequest');
    }

    public function forceDelete(AuthUser $authUser, BibleEnrollmentRequest $bibleEnrollmentRequest): bool
    {
        return $authUser->can('ForceDelete:BibleEnrollmentRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BibleEnrollmentRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BibleEnrollmentRequest');
    }

    public function replicate(AuthUser $authUser, BibleEnrollmentRequest $bibleEnrollmentRequest): bool
    {
        return $authUser->can('Replicate:BibleEnrollmentRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BibleEnrollmentRequest');
    }

}