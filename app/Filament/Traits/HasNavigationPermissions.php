<?php

namespace App\Filament\Traits;

use App\Services\NavigationService;
use Filament\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasNavigationPermissions
{
    /**
     * Get the navigation service instance
     */
    protected static function getNavigationService(): NavigationService
    {
        return app(NavigationService::class);
    }

    /**
     * Determine if the resource should be shown in navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $navigationService = static::getNavigationService();
        return $navigationService->canAccessResource(static::class);
    }

    /**
     * Get the navigation item with permission-based modifications
     */
    public static function getNavigationItems(): array
    {
        if (!static::shouldRegisterNavigation()) {
            return [];
        }

        $navigationService = static::getNavigationService();
        $items = parent::getNavigationItems();

        // Apply visual indicators for restricted access
        foreach ($items as &$item) {
            $item = static::applyNavigationRestrictions($item, $navigationService);
        }

        return $items;
    }

    /**
     * Apply navigation restrictions and visual indicators
     */
    protected static function applyNavigationRestrictions(NavigationItem $item, NavigationService $navigationService): NavigationItem
    {
        $isReadOnly = $navigationService->isResourceReadOnly(static::class);
        $badge = $navigationService->getNavigationBadge(static::class);

        if ($badge) {
            $item = $item->badge($badge['text'], $badge['color']);
        }

        // Add custom CSS class for styling restricted items
        if ($isReadOnly || !$navigationService->canAccessResource(static::class)) {
            $item = $item->extraAttributes([
                'class' => 'navigation-restricted',
                'title' => $navigationService->getTooltipMessage($isReadOnly ? 'read_only' : 'no_permission'),
            ]);
        }

        return $item;
    }

    /**
     * Get the navigation group with permission checks
     */
    public static function getNavigationGroup(): ?string
    {
        $group = parent::getNavigationGroup();
        
        if (!$group) {
            return null;
        }

        $navigationService = static::getNavigationService();
        
        if (!$navigationService->canAccessNavigationGroup($group)) {
            return null;
        }

        return $group;
    }

    /**
     * Apply scope filters based on user role
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        if (!Auth::check()) {
            return $query;
        }

        $navigationService = static::getNavigationService();
        $scopeFilter = $navigationService->getScopeFilter(static::class);

        if ($scopeFilter) {
            $query = static::applyScopeFilter($query, $scopeFilter);
        }

        return $query;
    }

    /**
     * Apply specific scope filter to the query
     */
    protected static function applyScopeFilter(Builder $query, string $scopeFilter): Builder
    {
        $user = Auth::user();

        switch ($scopeFilter) {
            case 'teacher_courses_only':
                // Teachers can only see courses they created or are assigned to
                $query->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhereHas('teachers', function ($teacherQuery) use ($user) {
                          $teacherQuery->where('user_id', $user->id);
                      });
                });
                break;

            case 'enrolled_students_only':
                // Teachers can only see students enrolled in their courses
                if ($user->hasRole('teacher')) {
                    $query->whereHas('enrollments.course', function ($courseQuery) use ($user) {
                        $courseQuery->where('created_by', $user->id)
                                   ->orWhereHas('teachers', function ($teacherQuery) use ($user) {
                                       $teacherQuery->where('user_id', $user->id);
                                   });
                    });
                }
                break;

            case 'enrolled_courses_only':
                // Students can only see courses they're enrolled in
                if ($user->hasRole('student')) {
                    $query->whereHas('enrollments', function ($enrollmentQuery) use ($user) {
                        $enrollmentQuery->where('user_id', $user->id);
                    });
                }
                break;

            case 'own_enrollments_only':
                // Users can only see their own enrollments
                $query->where('user_id', $user->id);
                break;

            default:
                // Custom scope filters can be added here
                break;
        }

        return $query;
    }

    /**
     * Check if user can perform a specific action on the resource
     */
    public static function canAccess(array $parameters = []): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $navigationService = static::getNavigationService();
        return $navigationService->canAccessResource(static::class);
    }

    /**
     * Check if user can view any records
     */
    public static function canViewAny(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $navigationService = static::getNavigationService();
        return $navigationService->hasPermission('ViewAny:' . static::getModelLabel());
    }

    /**
     * Check if user can create records
     */
    public static function canCreate(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $navigationService = static::getNavigationService();
        
        if ($navigationService->isActionDisabled(static::class, 'create')) {
            return false;
        }

        return $navigationService->hasPermission('Create:' . static::getModelLabel());
    }

    /**
     * Check if user can edit records
     */
    public static function canEdit($record): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $navigationService = static::getNavigationService();
        
        if ($navigationService->isActionDisabled(static::class, 'edit')) {
            return false;
        }

        return $navigationService->hasPermission('Update:' . static::getModelLabel());
    }

    /**
     * Check if user can delete records
     */
    public static function canDelete($record): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $navigationService = static::getNavigationService();
        
        if ($navigationService->isActionDisabled(static::class, 'delete')) {
            return false;
        }

        return $navigationService->hasPermission('Delete:' . static::getModelLabel());
    }

    /**
     * Check if user can bulk delete records
     */
    public static function canDeleteAny(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $navigationService = static::getNavigationService();
        
        if ($navigationService->isActionDisabled(static::class, 'bulk_delete')) {
            return false;
        }

        return $navigationService->hasPermission('Delete:' . static::getModelLabel());
    }

    /**
     * Get the model label for permission checking
     */
    public static function getModelLabel(): string
    {
        $model = static::getModel();
        return class_basename($model);
    }
}