<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BibleTestQuestion;
use Illuminate\Auth\Access\HandlesAuthorization;

class BibleTestQuestionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BibleTestQuestion');
    }

    public function view(AuthUser $authUser, BibleTestQuestion $bibleTestQuestion): bool
    {
        return $authUser->can('View:BibleTestQuestion');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BibleTestQuestion');
    }

    public function update(AuthUser $authUser, BibleTestQuestion $bibleTestQuestion): bool
    {
        return $authUser->can('Update:BibleTestQuestion');
    }

    public function delete(AuthUser $authUser, BibleTestQuestion $bibleTestQuestion): bool
    {
        return $authUser->can('Delete:BibleTestQuestion');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BibleTestQuestion');
    }

    public function restore(AuthUser $authUser, BibleTestQuestion $bibleTestQuestion): bool
    {
        return $authUser->can('Restore:BibleTestQuestion');
    }

    public function forceDelete(AuthUser $authUser, BibleTestQuestion $bibleTestQuestion): bool
    {
        return $authUser->can('ForceDelete:BibleTestQuestion');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BibleTestQuestion');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BibleTestQuestion');
    }

    public function replicate(AuthUser $authUser, BibleTestQuestion $bibleTestQuestion): bool
    {
        return $authUser->can('Replicate:BibleTestQuestion');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BibleTestQuestion');
    }

}