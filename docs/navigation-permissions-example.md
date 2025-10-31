# Navigation Permissions System - Implementation Example

## Quick Start Guide

This guide shows you how to implement the navigation permissions system in your Filament resources.

## Step 1: Update Your Resource

Add the `HasNavigationPermissions` trait to your Filament resource:

```php
<?php

namespace App\Filament\Resources\Courses;

use App\Filament\Traits\HasNavigationPermissions;
use App\Models\Course;
use Filament\Resources\Resource;

class CourseResource extends Resource
{
    use HasNavigationPermissions;

    protected static ?string $model = Course::class;
    protected static string $navigationIcon = 'heroicon-o-book-open';
    protected static string $navigationGroup = 'Learning Management';
    protected static ?int $navigationSort = 20;

    // ... rest of your resource implementation
}
```

## Step 2: Configure Permissions

Update `config/navigation-permissions.php` to include your resource:

```php
'teacher' => [
    'permissions' => [
        'ViewAny:Course', 
        'View:Course', 
        'Create:Course', 
        'Update:Course'
    ],
    'navigation_groups' => ['Learning Management'],
    'resources' => [
        'App\Filament\Resources\Courses\CourseResource',
    ],
    'pages' => ['Filament\Pages\Dashboard'],
    'widgets' => ['App\Filament\Widgets\RecentCourses'],
    'restrictions' => [
        'scope_filters' => [
            'App\Filament\Resources\Courses\CourseResource' => 'teacher_courses_only',
        ],
    ],
],
```

## Step 3: Create and Assign Roles

```php
// Create the role
$teacherRole = Role::create(['name' => 'teacher']);

// Create permissions
$permissions = [
    'ViewAny:Course',
    'View:Course', 
    'Create:Course',
    'Update:Course',
];

foreach ($permissions as $permission) {
    Permission::firstOrCreate(['name' => $permission]);
}

// Assign permissions to role
$teacherRole->syncPermissions($permissions);

// Assign role to user
$user->assignRole('teacher');
```

## Step 4: Test the Implementation

```php
// In your controller or test
use App\Services\NavigationService;

$navigationService = new NavigationService();

// Check if user can access the resource
$canAccess = $navigationService->canAccessResource('App\Filament\Resources\Courses\CourseResource');

// Check if user can access the navigation group
$canAccessGroup = $navigationService->canAccessNavigationGroup('Learning Management');

// Get filtered navigation groups for the user
$filteredGroups = $navigationService->getFilteredNavigationGroups();
```

## Example: Student Role with Read-Only Access

```php
// Configuration
'student' => [
    'permissions' => ['ViewAny:Course', 'View:Course'],
    'navigation_groups' => ['My Learning'],
    'resources' => ['App\Filament\Resources\Courses\CourseResource'],
    'pages' => ['Filament\Pages\Dashboard'],
    'widgets' => [],
    'restrictions' => [
        'disabled_actions' => ['create', 'edit', 'delete', 'bulk_delete'],
        'scope_filters' => [
            'App\Filament\Resources\Courses\CourseResource' => 'enrolled_courses_only',
        ],
        'read_only' => true,
    ],
],
```

## Example: Custom Scope Filter

Add a custom scope filter to the `NavigationService`:

```php
// In NavigationService::applyScopeFilter()
case 'my_custom_filter':
    // Only show records created by the current user
    $query->where('created_by', $user->id);
    break;

case 'department_filter':
    // Only show records from user's department
    $query->whereHas('department', function ($q) use ($user) {
        $q->where('id', $user->department_id);
    });
    break;
```

## Example: Visual Indicators

The system automatically applies visual indicators based on restrictions:

```css
/* Restricted items will have reduced opacity */
.navigation-restricted {
    opacity: 0.6;
}

/* Read-only items will have a warning border */
.navigation-restricted[data-readonly="true"] {
    border-left: 3px solid #f59e0b;
}

/* Items without permission will have a red border */
.navigation-restricted[data-no-permission="true"] {
    border-left: 3px solid #ef4444;
}
```

## Example: Testing Permissions

```php
// Unit test example
public function test_teacher_can_access_courses()
{
    $user = User::factory()->create();
    $teacherRole = Role::create(['name' => 'teacher']);
    $user->assignRole($teacherRole);
    
    Auth::login($user);
    $service = new NavigationService();
    
    $this->assertTrue($service->canAccessResource('App\Filament\Resources\Courses\CourseResource'));
    $this->assertTrue($service->canAccessNavigationGroup('Learning Management'));
    $this->assertFalse($service->canAccessNavigationGroup('User Management'));
}
```

## Example: Multiple Roles

Users can have multiple roles, and permissions are combined:

```php
// User with both teacher and admin roles
$user->assignRole(['teacher', 'admin']);

// Will have access to both teacher and admin resources
$navigationService = new NavigationService();
$canAccessCourses = $navigationService->canAccessResource('App\Filament\Resources\Courses\CourseResource'); // true
$canAccessUsers = $navigationService->canAccessResource('App\Filament\Resources\Users\UserResource'); // true
```

## Example: Dynamic Permission Checking

```php
// In your Filament resource
public static function canCreate(): bool
{
    $navigationService = app(NavigationService::class);
    
    if ($navigationService->isActionDisabled(static::class, 'create')) {
        return false;
    }
    
    return $navigationService->hasPermission('Create:' . static::getModelLabel());
}
```

## Example: Cache Management

```php
// Clear cache when roles or permissions change
$navigationService = app(NavigationService::class);

// Clear cache for specific user
$navigationService->clearUserCache($user->id);

// Clear all navigation cache (use sparingly)
$navigationService->clearAllCache();
```

## Troubleshooting Common Issues

### Issue: Navigation items not showing

**Solution**: Check the role configuration:

```php
// Debug user roles and permissions
$user = Auth::user();
dd($user->getRoleNames(), $user->getAllPermissions());

// Check if resource is configured for the role
$config = config('navigation-permissions.roles.teacher.resources');
dd($config);
```

### Issue: Permissions not working

**Solution**: Verify Spatie Permission setup:

```php
// Check if permissions exist
$permissions = Permission::all();
dd($permissions);

// Check role permissions
$role = Role::findByName('teacher');
dd($role->permissions);
```

### Issue: Scope filters not applying

**Solution**: Verify the scope filter implementation:

```php
// Debug the scope filter
$navigationService = app(NavigationService::class);
$scopeFilter = $navigationService->getScopeFilter('App\Filament\Resources\Courses\CourseResource');
dd($scopeFilter);
```

## Best Practices

1. **Start with restrictive permissions** and add access as needed
2. **Test with multiple roles** to ensure proper isolation
3. **Use descriptive permission names** that clearly indicate the action
4. **Document custom scope filters** for future maintenance
5. **Clear cache after configuration changes** in production
6. **Monitor performance** and adjust cache settings as needed

## Next Steps

1. Implement the trait in all your Filament resources
2. Configure roles and permissions for your use case
3. Test thoroughly with different user roles
4. Customize visual indicators to match your design
5. Set up monitoring for permission-related issues