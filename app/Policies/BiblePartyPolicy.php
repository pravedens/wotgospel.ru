<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BibleParty;
use Illuminate\Auth\Access\HandlesAuthorization;

class BiblePartyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BibleParty');
    }

    public function view(AuthUser $authUser, BibleParty $bibleParty): bool
    {
        return $authUser->can('View:BibleParty');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BibleParty');
    }

    public function update(AuthUser $authUser, BibleParty $bibleParty): bool
    {
        return $authUser->can('Update:BibleParty');
    }

    public function delete(AuthUser $authUser, BibleParty $bibleParty): bool
    {
        return $authUser->can('Delete:BibleParty');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BibleParty');
    }

    public function restore(AuthUser $authUser, BibleParty $bibleParty): bool
    {
        return $authUser->can('Restore:BibleParty');
    }

    public function forceDelete(AuthUser $authUser, BibleParty $bibleParty): bool
    {
        return $authUser->can('ForceDelete:BibleParty');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BibleParty');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BibleParty');
    }

    public function replicate(AuthUser $authUser, BibleParty $bibleParty): bool
    {
        return $authUser->can('Replicate:BibleParty');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BibleParty');
    }

}