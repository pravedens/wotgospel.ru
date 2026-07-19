<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BibleEssay;
use Illuminate\Auth\Access\HandlesAuthorization;

class BibleEssayPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BibleEssay');
    }

    public function view(AuthUser $authUser, BibleEssay $bibleEssay): bool
    {
        return $authUser->can('View:BibleEssay');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BibleEssay');
    }

    public function update(AuthUser $authUser, BibleEssay $bibleEssay): bool
    {
        return $authUser->can('Update:BibleEssay');
    }

    public function delete(AuthUser $authUser, BibleEssay $bibleEssay): bool
    {
        return $authUser->can('Delete:BibleEssay');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BibleEssay');
    }

    public function restore(AuthUser $authUser, BibleEssay $bibleEssay): bool
    {
        return $authUser->can('Restore:BibleEssay');
    }

    public function forceDelete(AuthUser $authUser, BibleEssay $bibleEssay): bool
    {
        return $authUser->can('ForceDelete:BibleEssay');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BibleEssay');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BibleEssay');
    }

    public function replicate(AuthUser $authUser, BibleEssay $bibleEssay): bool
    {
        return $authUser->can('Replicate:BibleEssay');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BibleEssay');
    }

}