<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BibleLessonComment;
use Illuminate\Auth\Access\HandlesAuthorization;

class BibleLessonCommentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BibleLessonComment');
    }

    public function view(AuthUser $authUser, BibleLessonComment $bibleLessonComment): bool
    {
        return $authUser->can('View:BibleLessonComment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BibleLessonComment');
    }

    public function update(AuthUser $authUser, BibleLessonComment $bibleLessonComment): bool
    {
        return $authUser->can('Update:BibleLessonComment');
    }

    public function delete(AuthUser $authUser, BibleLessonComment $bibleLessonComment): bool
    {
        return $authUser->can('Delete:BibleLessonComment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BibleLessonComment');
    }

    public function restore(AuthUser $authUser, BibleLessonComment $bibleLessonComment): bool
    {
        return $authUser->can('Restore:BibleLessonComment');
    }

    public function forceDelete(AuthUser $authUser, BibleLessonComment $bibleLessonComment): bool
    {
        return $authUser->can('ForceDelete:BibleLessonComment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BibleLessonComment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BibleLessonComment');
    }

    public function replicate(AuthUser $authUser, BibleLessonComment $bibleLessonComment): bool
    {
        return $authUser->can('Replicate:BibleLessonComment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BibleLessonComment');
    }

}