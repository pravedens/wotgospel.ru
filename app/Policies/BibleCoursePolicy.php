<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BibleCourse;
use Illuminate\Auth\Access\HandlesAuthorization;

class BibleCoursePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BibleCourse');
    }

    public function view(AuthUser $authUser, BibleCourse $bibleCourse): bool
    {
        return $authUser->can('View:BibleCourse');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BibleCourse');
    }

    public function update(AuthUser $authUser, BibleCourse $bibleCourse): bool
    {
        return $authUser->can('Update:BibleCourse');
    }

    public function delete(AuthUser $authUser, BibleCourse $bibleCourse): bool
    {
        return $authUser->can('Delete:BibleCourse');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BibleCourse');
    }

    public function restore(AuthUser $authUser, BibleCourse $bibleCourse): bool
    {
        return $authUser->can('Restore:BibleCourse');
    }

    public function forceDelete(AuthUser $authUser, BibleCourse $bibleCourse): bool
    {
        return $authUser->can('ForceDelete:BibleCourse');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BibleCourse');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BibleCourse');
    }

    public function replicate(AuthUser $authUser, BibleCourse $bibleCourse): bool
    {
        return $authUser->can('Replicate:BibleCourse');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BibleCourse');
    }

}