<?php

namespace App\Http\Middleware;

use App\Services\NavigationService;
use Closure;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FilamentNavigationMiddleware
{
    protected NavigationService $navigationService;

    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply to Filament admin panel requests
        if (!$request->is('admin*')) {
            return $next($request);
        }

        // Only apply if user is authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        // Register navigation filtering
        $this->registerNavigationFiltering();

        return $next($request);
    }

    /**
     * Register navigation filtering based on user permissions
     */
    protected function registerNavigationFiltering(): void
    {
        // Note: This middleware sets up the navigation service for use in resources
        // The actual navigation filtering will be handled in individual resources
        // and through the AdminPanelProvider configuration
        
        // Store navigation service in app container for use by resources
        app()->singleton('navigation.service', function () {
            return $this->navigationService;
        });
    }

    /**
     * Check if a navigation item should be included
     */
    protected function shouldIncludeNavigationItem(NavigationItem $item): bool
    {
        $url = $item->getUrl();
        
        // Check if this is a resource URL
        if ($this->isResourceUrl($url)) {
            $resourceClass = $this->getResourceClassFromUrl($url);
            if ($resourceClass && !$this->navigationService->canAccessResource($resourceClass)) {
                return !$this->navigationService->shouldHideNavigationItem($resourceClass);
            }
        }

        // Check if this is a page URL
        if ($this->isPageUrl($url)) {
            $pageClass = $this->getPageClassFromUrl($url);
            if ($pageClass && !$this->navigationService->canAccessPage($pageClass)) {
                return false; // Hide pages user can't access
            }
        }

        return true;
    }

    /**
     * Process navigation item to add visual indicators for restricted access
     */
    protected function processNavigationItem(NavigationItem $item): NavigationItem
    {
        $url = $item->getUrl();
        
        // Check if this is a resource URL
        if ($this->isResourceUrl($url)) {
            $resourceClass = $this->getResourceClassFromUrl($url);
            if ($resourceClass) {
                $item = $this->applyResourceRestrictions($item, $resourceClass);
            }
        }

        return $item;
    }

    /**
     * Apply visual restrictions to navigation item for restricted resources
     */
    protected function applyResourceRestrictions(NavigationItem $item, string $resourceClass): NavigationItem
    {
        $canAccess = $this->navigationService->canAccessResource($resourceClass);
        $isReadOnly = $this->navigationService->isResourceReadOnly($resourceClass);
        $visualIndicators = $this->navigationService->getVisualIndicators();

        if (!$canAccess || $isReadOnly) {
            // Add visual indicators for restricted access
            $badge = $this->navigationService->getNavigationBadge($resourceClass);
            if ($badge) {
                $item = $item->badge($badge['text'], $badge['color']);
            }

            // Add tooltip for restricted access
            $tooltipMessage = $isReadOnly 
                ? $this->navigationService->getTooltipMessage('read_only')
                : $this->navigationService->getTooltipMessage('no_permission');
            
            // Note: Filament doesn't have built-in tooltip support for navigation items
            // This would need to be implemented with custom CSS/JS
        }

        return $item;
    }

    /**
     * Check if URL is a resource URL
     */
    protected function isResourceUrl(string $url): bool
    {
        // Basic check for resource URLs (this might need refinement)
        return preg_match('/\/admin\/[a-z-]+$/', $url) || 
               preg_match('/\/admin\/[a-z-]+\/[0-9]+/', $url);
    }

    /**
     * Check if URL is a page URL
     */
    protected function isPageUrl(string $url): bool
    {
        // Basic check for page URLs
        return $url === '/admin' || preg_match('/\/admin\/[a-z-]+$/', $url);
    }

    /**
     * Get resource class from URL (simplified implementation)
     */
    protected function getResourceClassFromUrl(string $url): ?string
    {
        // This is a simplified implementation
        // In a real application, you'd need a more robust way to map URLs to resource classes
        $resourceMappings = [
            '/admin/users' => 'App\Filament\Resources\Users\UserResource',
            '/admin/courses' => 'App\Filament\Resources\Courses\CourseResource',
            '/admin/learning-paths' => 'App\Filament\Resources\LearningPaths\LearningPathResource',
            '/admin/lessons' => 'App\Filament\Resources\Lessons\LessonResource',
            '/admin/lesson-sections' => 'App\Filament\Resources\LessonSections\LessonSectionResource',
            '/admin/enrollments' => 'App\Filament\Resources\Enrollments\EnrollmentResource',
            '/admin/institutions' => 'App\Filament\Resources\Institutions\InstitutionResource',
            '/admin/shield/roles' => 'BezhanSalleh\FilamentShield\Resources\RoleResource',
        ];

        // Remove query parameters and fragments
        $cleanUrl = strtok($url, '?');
        $cleanUrl = strtok($cleanUrl, '#');

        // Check for exact matches first
        if (isset($resourceMappings[$cleanUrl])) {
            return $resourceMappings[$cleanUrl];
        }

        // Check for partial matches (for edit/view pages)
        foreach ($resourceMappings as $pattern => $resourceClass) {
            if (strpos($cleanUrl, $pattern) === 0) {
                return $resourceClass;
            }
        }

        return null;
    }

    /**
     * Get page class from URL (simplified implementation)
     */
    protected function getPageClassFromUrl(string $url): ?string
    {
        $pageMappings = [
            '/admin' => 'Filament\Pages\Dashboard',
        ];

        $cleanUrl = strtok($url, '?');
        $cleanUrl = strtok($cleanUrl, '#');

        return $pageMappings[$cleanUrl] ?? null;
    }
}