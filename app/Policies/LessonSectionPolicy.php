<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LessonSection;
use Illuminate\Auth\Access\HandlesAuthorization;

class LessonSectionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LessonSection');
    }

    public function view(AuthUser $authUser, LessonSection $lessonSection): bool
    {
        return $authUser->can('View:LessonSection');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LessonSection');
    }

    public function update(AuthUser $authUser, LessonSection $lessonSection): bool
    {
        return $authUser->can('Update:LessonSection');
    }

    public function delete(AuthUser $authUser, LessonSection $lessonSection): bool
    {
        return $authUser->can('Delete:LessonSection');
    }

    public function restore(AuthUser $authUser, LessonSection $lessonSection): bool
    {
        return $authUser->can('Restore:LessonSection');
    }

    public function forceDelete(AuthUser $authUser, LessonSection $lessonSection): bool
    {
        return $authUser->can('ForceDelete:LessonSection');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LessonSection');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LessonSection');
    }

    public function replicate(AuthUser $authUser, LessonSection $lessonSection): bool
    {
        return $authUser->can('Replicate:LessonSection');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LessonSection');
    }

}