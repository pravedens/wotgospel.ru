<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BibleLesson;
use Illuminate\Auth\Access\HandlesAuthorization;

class BibleLessonPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BibleLesson');
    }

    public function view(AuthUser $authUser, BibleLesson $bibleLesson): bool
    {
        return $authUser->can('View:BibleLesson');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BibleLesson');
    }

    public function update(AuthUser $authUser, BibleLesson $bibleLesson): bool
    {
        return $authUser->can('Update:BibleLesson');
    }

    public function delete(AuthUser $authUser, BibleLesson $bibleLesson): bool
    {
        return $authUser->can('Delete:BibleLesson');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BibleLesson');
    }

    public function restore(AuthUser $authUser, BibleLesson $bibleLesson): bool
    {
        return $authUser->can('Restore:BibleLesson');
    }

    public function forceDelete(AuthUser $authUser, BibleLesson $bibleLesson): bool
    {
        return $authUser->can('ForceDelete:BibleLesson');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BibleLesson');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BibleLesson');
    }

    public function replicate(AuthUser $authUser, BibleLesson $bibleLesson): bool
    {
        return $authUser->can('Replicate:BibleLesson');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BibleLesson');
    }

}