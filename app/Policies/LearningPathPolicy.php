<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LearningPath;
use Illuminate\Auth\Access\HandlesAuthorization;

class LearningPathPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LearningPath');
    }

    public function view(AuthUser $authUser, LearningPath $learningPath): bool
    {
        return $authUser->can('View:LearningPath');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LearningPath');
    }

    public function update(AuthUser $authUser, LearningPath $learningPath): bool
    {
        return $authUser->can('Update:LearningPath');
    }

    public function delete(AuthUser $authUser, LearningPath $learningPath): bool
    {
        return $authUser->can('Delete:LearningPath');
    }

    public function restore(AuthUser $authUser, LearningPath $learningPath): bool
    {
        return $authUser->can('Restore:LearningPath');
    }

    public function forceDelete(AuthUser $authUser, LearningPath $learningPath): bool
    {
        return $authUser->can('ForceDelete:LearningPath');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LearningPath');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LearningPath');
    }

    public function replicate(AuthUser $authUser, LearningPath $learningPath): bool
    {
        return $authUser->can('Replicate:LearningPath');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LearningPath');
    }

}