<?php

namespace App\Filament\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

trait HasShieldPermissions
{
    /**
     * Check if the resource should be registered in navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    /**
     * Get the Eloquent query with proper scoping
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        if (!Auth::check()) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }

        $user = Auth::user();
        
        // Super admin sees everything
        if ($user->hasRole('super_admin')) {
            return $query->withoutGlobalScopes();
        }

        // Apply institution scoping for school roles
        if ($user->hasAnyRole(['school_admin', 'school_teacher'])) {
            return static::applyInstitutionScoping($query, $user);
        }

        // Students should not access admin panel
        if ($user->hasRole('student')) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }

        return $query;
    }

    /**
     * Apply institution-level scoping to the query
     */
    protected static function applyInstitutionScoping(Builder $query, $user): Builder
    {
        $model = static::getModel();
        $modelInstance = new $model;

        // Check if model has institution_id column
        if (in_array('institution_id', $modelInstance->getFillable()) || 
            $modelInstance->getConnection()->getSchemaBuilder()->hasColumn($modelInstance->getTable(), 'institution_id')) {
            
            if ($user->institution_id) {
                $query->where('institution_id', $user->institution_id);
            }
        }

        // Apply specific scoping rules based on resource type
        return static::applyResourceSpecificScoping($query, $user);
    }

    /**
     * Apply resource-specific scoping rules
     */
    protected static function applyResourceSpecificScoping(Builder $query, $user): Builder
    {
        $resourceClass = static::class;

        // User resource scoping
        if (str_contains($resourceClass, 'UserResource')) {
            if ($user->hasRole('school_teacher')) {
                // Teachers can only see students in their institution
                $query->where('institution_id', $user->institution_id)
                      ->whereHas('roles', function ($roleQuery) {
                          $roleQuery->where('name', 'student');
                      });
            }
        }

        // Course resource scoping
        if (str_contains($resourceClass, 'CourseResource')) {
            if ($user->hasRole('school_teacher')) {
                // Teachers can only see courses they're assigned to or created
                $query->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhereHas('teachers', function ($teacherQuery) use ($user) {
                          $teacherQuery->where('teacher_id', $user->id);
                      });
                });
            }
        }

        // Enrollment resource scoping
        if (str_contains($resourceClass, 'EnrollmentResource')) {
            if ($user->hasRole('school_teacher')) {
                // Teachers can only see enrollments for their courses
                $query->whereHas('course', function ($courseQuery) use ($user) {
                    $courseQuery->where('created_by', $user->id)
                               ->orWhereHas('teachers', function ($teacherQuery) use ($user) {
                                   $teacherQuery->where('teacher_id', $user->id);
                               });
                });
            }
        }

        // Task submission scoping
        if (str_contains($resourceClass, 'TaskSubmissionResource')) {
            if ($user->hasRole('school_teacher')) {
                // Teachers can only see submissions for their tasks
                $query->whereHas('task.lesson.course', function ($courseQuery) use ($user) {
                    $courseQuery->where('created_by', $user->id)
                               ->orWhereHas('teachers', function ($teacherQuery) use ($user) {
                                   $teacherQuery->where('teacher_id', $user->id);
                               });
                });
            }
        }

        return $query;
    }

    /**
     * Check if user can view any records
     */
    public static function canViewAny(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        $modelName = class_basename(static::getModel());

        // Students cannot access admin panel
        if ($user->hasRole('student')) {
            return false;
        }

        // Check specific permission
        return $user->can("ViewAny:{$modelName}");
    }

    /**
     * Check if user can view a specific record
     */
    public static function canView($record): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        $modelName = class_basename(static::getModel());

        // Students cannot access admin panel
        if ($user->hasRole('student')) {
            return false;
        }

        // Super admin can view everything
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check permission first
        if (!$user->can("View:{$modelName}")) {
            return false;
        }

        // Check institution access
        if ($user->hasAnyRole(['school_admin', 'school_teacher'])) {
            return static::canAccessRecord($record, $user);
        }

        return true;
    }

    /**
     * Check if user can create records
     */
    public static function canCreate(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        $modelName = class_basename(static::getModel());

        // Students cannot access admin panel
        if ($user->hasRole('student')) {
            return false;
        }

        return $user->can("Create:{$modelName}");
    }

    /**
     * Check if user can edit a specific record
     */
    public static function canEdit($record): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        $modelName = class_basename(static::getModel());

        // Students cannot access admin panel
        if ($user->hasRole('student')) {
            return false;
        }

        // Super admin can edit everything
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check permission first
        if (!$user->can("Update:{$modelName}")) {
            return false;
        }

        // Check institution access
        if ($user->hasAnyRole(['school_admin', 'school_teacher'])) {
            return static::canAccessRecord($record, $user);
        }

        return true;
    }

    /**
     * Check if user can delete a specific record
     */
    public static function canDelete($record): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        $modelName = class_basename(static::getModel());

        // Students cannot access admin panel
        if ($user->hasRole('student')) {
            return false;
        }

        // Super admin can delete everything
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check permission first
        if (!$user->can("Delete:{$modelName}")) {
            return false;
        }

        // Check institution access
        if ($user->hasAnyRole(['school_admin', 'school_teacher'])) {
            return static::canAccessRecord($record, $user);
        }

        return true;
    }

    /**
     * Check if user can delete any records
     */
    public static function canDeleteAny(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        $modelName = class_basename(static::getModel());

        // Students cannot access admin panel
        if ($user->hasRole('student')) {
            return false;
        }

        return $user->can("Delete:{$modelName}");
    }

    /**
     * Check if user can access a specific record based on institution
     */
    protected static function canAccessRecord($record, $user): bool
    {
        // If record has institution_id, check if it matches user's institution
        if (isset($record->institution_id)) {
            return $record->institution_id === $user->institution_id;
        }

        // For records without direct institution relationship, check related models
        $resourceClass = static::class;

        // User access check
        if (str_contains($resourceClass, 'UserResource')) {
            return $record->institution_id === $user->institution_id;
        }

        // Course access check
        if (str_contains($resourceClass, 'CourseResource')) {
            if ($user->hasRole('school_teacher')) {
                // Teachers can access courses they created, are assigned to, or courses in their institution
                return $record->created_by === $user->id ||
                       $record->teachers()->where('teacher_id', $user->id)->exists() || 
                       ($record->institution_id && $record->institution_id === $user->institution_id);
            }
            // For other roles, check created_by or institution access
            return $record->created_by === $user->id || 
                   (!isset($record->institution_id) || $record->institution_id === $user->institution_id);
        }

        // Enrollment access check
        if (str_contains($resourceClass, 'EnrollmentResource')) {
            if ($user->hasRole('school_teacher')) {
                // Teachers can access enrollments for courses they teach
                return $record->course->teachers()->where('teacher_id', $user->id)->exists() ||
                       ($record->course->institution_id && $record->course->institution_id === $user->institution_id);
            }
            // For other roles, check institution access
            return !isset($record->course->institution_id) || $record->course->institution_id === $user->institution_id;
        }

        // Default: allow access if same institution
        return true;
    }

    /**
     * Get navigation badge for role-based indicators
     */
    public static function getNavigationBadge(): ?string
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();

        if ($user->hasRole('school_teacher')) {
            return 'Teacher';
        }

        if ($user->hasRole('school_admin')) {
            return 'Admin';
        }

        return null;
    }

    /**
     * Get navigation sort order based on role
     */
    public static function getNavigationSort(): ?int
    {
        if (!Auth::check()) {
            return parent::getNavigationSort();
        }

        $user = Auth::user();
        $resourceClass = static::class;

        // Prioritize teaching resources for teachers
        if ($user->hasRole('school_teacher')) {
            if (str_contains($resourceClass, 'TaskSubmissionResource')) {
                return 10;
            }
            if (str_contains($resourceClass, 'ProgressLogResource')) {
                return 20;
            }
            if (str_contains($resourceClass, 'EnrollmentResource')) {
                return 30;
            }
        }

        return parent::getNavigationSort();
    }
}