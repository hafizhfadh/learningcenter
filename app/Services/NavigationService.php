<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use App\Models\User;

class NavigationService
{
    protected array $config;
    protected ?User $user;
    protected array $userRoles;
    protected array $userPermissions;

    public function __construct()
    {
        $this->config = Config::get('navigation-permissions', []);
        $this->user = Auth::user();
        $this->loadUserRolesAndPermissions();
    }

    /**
     * Load user roles and permissions for the current user
     */
    protected function loadUserRolesAndPermissions(): void
    {
        if (!$this->user) {
            $this->userRoles = [];
            $this->userPermissions = [];
            return;
        }

        $cacheKey = $this->getCacheKey('user_roles_permissions', $this->user->id);
        
        if ($this->config['cache']['enabled'] ?? true) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                $this->userRoles = $cached['roles'];
                $this->userPermissions = $cached['permissions'];
                return;
            }
        }

        $this->userRoles = $this->user->getRoleNames()->toArray();
        $this->userPermissions = $this->user->getAllPermissions()->pluck('name')->toArray();

        if ($this->config['cache']['enabled'] ?? true) {
            Cache::put($cacheKey, [
                'roles' => $this->userRoles,
                'permissions' => $this->userPermissions,
            ], $this->config['cache']['ttl'] ?? 3600);
        }
    }

    /**
     * Check if user can access a specific navigation group
     */
    public function canAccessNavigationGroup(string $group): bool
    {
        if (!$this->user) {
            return false;
        }

        // Super admin has access to everything
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($this->userRoles as $roleName) {
            $roleConfig = $this->config['roles'][$roleName] ?? null;
            if (!$roleConfig) {
                continue;
            }

            $allowedGroups = $roleConfig['navigation_groups'] ?? [];
            
            // Check for wildcard access
            if (in_array('*', $allowedGroups)) {
                return true;
            }

            // Check for specific group access
            if (in_array($group, $allowedGroups)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can access a specific resource
     */
    public function canAccessResource(string $resourceClass): bool
    {
        if (!$this->user) {
            return false;
        }

        // Super admin has access to everything
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($this->userRoles as $roleName) {
            $roleConfig = $this->config['roles'][$roleName] ?? null;
            if (!$roleConfig) {
                continue;
            }

            $allowedResources = $roleConfig['resources'] ?? [];
            
            // Check for wildcard access
            if (in_array('*', $allowedResources)) {
                return true;
            }

            // Check for specific resource access
            if (in_array($resourceClass, $allowedResources)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can access a specific page
     */
    public function canAccessPage(string $pageClass): bool
    {
        if (!$this->user) {
            return false;
        }

        // Super admin has access to everything
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($this->userRoles as $roleName) {
            $roleConfig = $this->config['roles'][$roleName] ?? null;
            if (!$roleConfig) {
                continue;
            }

            $allowedPages = $roleConfig['pages'] ?? [];
            
            // Check for wildcard access
            if (in_array('*', $allowedPages)) {
                return true;
            }

            // Check for specific page access
            if (in_array($pageClass, $allowedPages)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can access a specific widget
     */
    public function canAccessWidget(string $widgetClass): bool
    {
        if (!$this->user) {
            return false;
        }

        // Super admin has access to everything
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($this->userRoles as $roleName) {
            $roleConfig = $this->config['roles'][$roleName] ?? null;
            if (!$roleConfig) {
                continue;
            }

            $allowedWidgets = $roleConfig['widgets'] ?? [];
            
            // Check for wildcard access
            if (in_array('*', $allowedWidgets)) {
                return true;
            }

            // Check for specific widget access
            if (in_array($widgetClass, $allowedWidgets)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get filtered navigation groups for the current user
     */
    public function getFilteredNavigationGroups(): array
    {
        $allGroups = $this->config['navigation_groups'] ?? [];
        $filteredGroups = [];

        foreach ($allGroups as $groupName => $groupConfig) {
            if ($this->canAccessNavigationGroup($groupName)) {
                $filteredGroups[$groupName] = $groupConfig;
            }
        }

        return $filteredGroups;
    }

    /**
     * Get restrictions for a specific resource
     */
    public function getResourceRestrictions(string $resourceClass): array
    {
        $restrictions = [];

        foreach ($this->userRoles as $roleName) {
            $roleConfig = $this->config['roles'][$roleName] ?? null;
            if (!$roleConfig) {
                continue;
            }

            $roleRestrictions = $roleConfig['restrictions'] ?? [];
            
            // Merge restrictions from all user roles
            $restrictions = array_merge_recursive($restrictions, $roleRestrictions);
        }

        return $restrictions;
    }

    /**
     * Check if an action is disabled for a resource
     */
    public function isActionDisabled(string $resourceClass, string $action): bool
    {
        $restrictions = $this->getResourceRestrictions($resourceClass);
        $disabledActions = $restrictions['disabled_actions'] ?? [];

        return in_array($action, $disabledActions);
    }

    /**
     * Check if a resource is read-only for the current user
     */
    public function isResourceReadOnly(string $resourceClass): bool
    {
        $restrictions = $this->getResourceRestrictions($resourceClass);
        return $restrictions['read_only'] ?? false;
    }

    /**
     * Get visual indicator configuration for restricted items
     */
    public function getVisualIndicators(): array
    {
        return $this->config['visual_indicators'] ?? [];
    }

    /**
     * Check if current user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return in_array('super_admin', $this->userRoles);
    }

    /**
     * Get scope filter for a resource
     */
    public function getScopeFilter(string $resourceClass): ?string
    {
        foreach ($this->userRoles as $roleName) {
            $roleConfig = $this->config['roles'][$roleName] ?? null;
            if (!$roleConfig) {
                continue;
            }

            $scopeFilters = $roleConfig['restrictions']['scope_filters'] ?? [];
            
            if (isset($scopeFilters[$resourceClass])) {
                return $scopeFilters[$resourceClass];
            }
        }

        return null;
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->user) {
            return false;
        }

        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->userPermissions);
    }

    /**
     * Get all permissions for current user's roles
     */
    public function getUserRolePermissions(): array
    {
        $permissions = [];

        foreach ($this->userRoles as $roleName) {
            $roleConfig = $this->config['roles'][$roleName] ?? null;
            if (!$roleConfig) {
                continue;
            }

            $rolePermissions = $roleConfig['permissions'] ?? [];
            $permissions = array_merge($permissions, $rolePermissions);
        }

        return array_unique($permissions);
    }

    /**
     * Clear navigation cache for a specific user
     */
    public function clearUserCache(?int $userId = null): void
    {
        $userId = $userId ?? $this->user?->id;
        if (!$userId) {
            return;
        }

        $cacheKey = $this->getCacheKey('user_roles_permissions', $userId);
        Cache::forget($cacheKey);
    }

    /**
     * Clear all navigation cache
     */
    public function clearAllCache(): void
    {
        $prefix = $this->config['cache']['key_prefix'] ?? 'navigation_permissions';
        Cache::flush(); // In production, you might want to be more selective
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(string $type, mixed $identifier): string
    {
        $prefix = $this->config['cache']['key_prefix'] ?? 'navigation_permissions';
        return "{$prefix}:{$type}:{$identifier}";
    }

    /**
     * Get tooltip message for restricted access
     */
    public function getTooltipMessage(string $type): string
    {
        $messages = $this->config['visual_indicators']['tooltip_messages'] ?? [];
        return $messages[$type] ?? 'Access restricted';
    }

    /**
     * Check if navigation item should be hidden vs disabled
     */
    public function shouldHideNavigationItem(string $resourceClass): bool
    {
        // For now, we'll show all items but disable them
        // This can be configured per role if needed
        return false;
    }

    /**
     * Get navigation badge configuration for restricted items
     */
    public function getNavigationBadge(string $resourceClass): ?array
    {
        if ($this->isResourceReadOnly($resourceClass)) {
            $badgeConfig = $this->config['visual_indicators']['restricted_badge'] ?? [];
            if ($badgeConfig['enabled'] ?? false) {
                return [
                    'text' => $badgeConfig['text'] ?? 'Restricted',
                    'color' => $badgeConfig['color'] ?? 'warning',
                ];
            }
        }

        return null;
    }
}