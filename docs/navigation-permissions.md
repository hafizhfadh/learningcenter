# Navigation Permissions System

## Overview

The Navigation Permissions System provides dynamic role-based access control for the Filament admin panel sidebar. It allows fine-grained control over which navigation items, resources, pages, and widgets are accessible to different user roles, with visual indicators for restricted access.

## Architecture

### Core Components

1. **NavigationService** - Central service for permission checking and navigation filtering
2. **FilamentNavigationMiddleware** - Middleware that integrates with Filament panels
3. **HasNavigationPermissions** - Trait for Filament resources to implement permission checks
4. **Configuration** - Centralized permission definitions in `config/navigation-permissions.php`

### Configuration Structure

The system is configured through `config/navigation-permissions.php` which defines:

- **Roles**: Permission mappings for each user role
- **Navigation Groups**: Group definitions with icons and sorting
- **Visual Indicators**: Styling for restricted items
- **Cache Settings**: Performance optimization configuration

## Role Configuration

### Role Structure

Each role in the configuration has the following structure:

```php
'role_name' => [
    'permissions' => ['permission1', 'permission2', ...],
    'navigation_groups' => ['Group1', 'Group2', ...],
    'resources' => ['ResourceClass1', 'ResourceClass2', ...],
    'pages' => ['PageClass1', 'PageClass2', ...],
    'widgets' => ['WidgetClass1', 'WidgetClass2', ...],
    'restrictions' => [
        'scope_filters' => [...],
        'disabled_actions' => [...],
        'read_only' => boolean,
    ],
],
```

### Built-in Roles

#### Super Admin
- **Access**: Full access to everything (wildcard permissions)
- **Use Case**: System administrators with unrestricted access

#### Admin
- **Access**: User management, all learning resources, system administration
- **Restrictions**: None
- **Use Case**: Institution administrators

#### Teacher
- **Access**: Learning management resources, limited user access
- **Restrictions**: Can only see/edit their own courses and enrolled students
- **Use Case**: Course instructors

#### Student
- **Access**: Read-only access to enrolled courses
- **Restrictions**: Cannot create, edit, or delete; scope limited to enrolled courses
- **Use Case**: Course participants

#### Panel User
- **Access**: Basic browsing capabilities
- **Restrictions**: Read-only access to limited resources
- **Use Case**: Guest or limited access users

## Implementation Guide

### Adding the System to Resources

To enable navigation permissions for a Filament resource:

1. **Add the trait** to your resource class:

```php
use App\Filament\Traits\HasNavigationPermissions;

class YourResource extends Resource
{
    use HasNavigationPermissions;
    
    // ... rest of your resource
}
```

2. **Configure permissions** in `config/navigation-permissions.php`:

```php
'your_role' => [
    'resources' => ['App\Filament\Resources\YourResource'],
    'permissions' => ['ViewAny:YourModel', 'Create:YourModel'],
    // ... other configuration
],
```

### Custom Scope Filters

Scope filters allow you to limit the data visible to users based on their role:

```php
// In NavigationService::applyScopeFilter()
case 'your_custom_filter':
    $query->where('user_id', $user->id);
    break;
```

### Adding New Roles

1. **Define the role** in `config/navigation-permissions.php`:

```php
'new_role' => [
    'permissions' => ['ViewAny:SomeResource'],
    'navigation_groups' => ['Some Group'],
    'resources' => ['App\Filament\Resources\SomeResource'],
    'pages' => ['Filament\Pages\Dashboard'],
    'widgets' => [],
    'restrictions' => [
        'disabled_actions' => ['create', 'delete'],
    ],
],
```

2. **Create the role** in your database:

```php
$role = Role::create(['name' => 'new_role']);
```

3. **Assign permissions** if using Spatie Permission:

```php
$permissions = Permission::whereIn('name', [
    'ViewAny:SomeResource',
    // ... other permissions
])->get();

$role->syncPermissions($permissions);
```

## Visual Indicators

### CSS Classes

The system applies CSS classes to navigation items based on their restriction status:

- `.navigation-restricted` - Applied to all restricted items
- `[data-readonly="true"]` - Applied to read-only items
- `[data-no-permission="true"]` - Applied to items without permission

### Styling

Custom CSS is provided in `resources/css/navigation-permissions.css`:

- **Opacity reduction** for restricted items
- **Border indicators** for different restriction types
- **Tooltip support** for accessibility
- **Hover effects** for better UX

### Badges

Restricted resources can display badges:

```php
// Configuration
'visual_indicators' => [
    'restricted_badge' => [
        'enabled' => true,
        'text' => 'Restricted',
        'color' => 'warning',
    ],
],
```

## Performance Optimization

### Caching

The system includes built-in caching for performance:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 3600, // 1 hour
    'key_prefix' => 'navigation_permissions',
],
```

### Cache Management

```php
// Clear cache for specific user
$navigationService->clearUserCache($userId);

// Clear all navigation cache
$navigationService->clearAllCache();
```

## Testing

### Unit Tests

Comprehensive unit tests are provided in `tests/Unit/NavigationServiceTest.php`:

- Role-based access control
- Permission checking
- Scope filtering
- Visual indicators
- Edge cases and error handling

### Feature Tests

Integration tests are provided in `tests/Feature/NavigationPermissionsTest.php`:

- End-to-end navigation filtering
- Middleware integration
- Multi-role scenarios
- Visual indicator rendering

### Running Tests

```bash
# Run all navigation permission tests
php artisan test --filter NavigationPermissions

# Run specific test class
php artisan test tests/Unit/NavigationServiceTest.php
```

## Security Considerations

### Best Practices

1. **Principle of Least Privilege**: Grant minimum necessary permissions
2. **Regular Audits**: Review role permissions periodically
3. **Secure Defaults**: Default to restrictive permissions
4. **Input Validation**: Validate all permission checks

### Security Features

- **Authentication Required**: All checks require authenticated users
- **Role Validation**: Roles must exist in configuration
- **Permission Verification**: Permissions are verified against Spatie Permission
- **Scope Filtering**: Data access is limited by user context

## Troubleshooting

### Common Issues

1. **Navigation items not showing**
   - Check role configuration in `config/navigation-permissions.php`
   - Verify user has the required role assigned
   - Ensure resource is listed in role's `resources` array

2. **Permissions not working**
   - Verify Spatie Permission is properly configured
   - Check that permissions exist in the database
   - Ensure role has the required permissions assigned

3. **Cache issues**
   - Clear navigation cache: `$navigationService->clearAllCache()`
   - Disable cache temporarily for debugging
   - Check cache configuration in config file

### Debug Mode

To debug permission issues:

1. **Disable caching** temporarily:
```php
'cache' => ['enabled' => false],
```

2. **Add logging** to NavigationService methods:
```php
Log::info('Permission check', [
    'user' => $this->user->id,
    'resource' => $resourceClass,
    'result' => $canAccess,
]);
```

3. **Check user roles and permissions**:
```php
dd($user->getRoleNames(), $user->getAllPermissions());
```

## Maintenance

### Regular Tasks

1. **Review Permissions**: Audit role permissions quarterly
2. **Update Documentation**: Keep role descriptions current
3. **Performance Monitoring**: Monitor cache hit rates
4. **Security Updates**: Review and update security practices

### Configuration Updates

When updating role configurations:

1. **Test thoroughly** in development environment
2. **Clear cache** after configuration changes
3. **Document changes** in version control
4. **Notify affected users** of permission changes

### Database Maintenance

```php
// Clean up orphaned permissions
Permission::whereNotIn('name', $configuredPermissions)->delete();

// Sync role permissions
foreach ($roles as $roleName => $config) {
    $role = Role::findByName($roleName);
    $role->syncPermissions($config['permissions']);
}
```

## API Reference

### NavigationService Methods

#### Access Control
- `canAccessNavigationGroup(string $group): bool`
- `canAccessResource(string $resourceClass): bool`
- `canAccessPage(string $pageClass): bool`
- `canAccessWidget(string $widgetClass): bool`

#### Permission Checking
- `hasPermission(string $permission): bool`
- `isSuperAdmin(): bool`
- `isActionDisabled(string $resourceClass, string $action): bool`
- `isResourceReadOnly(string $resourceClass): bool`

#### Data Filtering
- `getScopeFilter(string $resourceClass): ?string`
- `getResourceRestrictions(string $resourceClass): array`
- `getFilteredNavigationGroups(): array`

#### Visual Indicators
- `getNavigationBadge(string $resourceClass): ?array`
- `getTooltipMessage(string $type): string`
- `getVisualIndicators(): array`

#### Cache Management
- `clearUserCache(?int $userId = null): void`
- `clearAllCache(): void`

### HasNavigationPermissions Trait Methods

#### Navigation Control
- `shouldRegisterNavigation(): bool`
- `getNavigationItems(): array`
- `getNavigationGroup(): ?string`

#### Query Filtering
- `getEloquentQuery(): Builder`
- `applyScopeFilter(Builder $query, string $scopeFilter): Builder`

#### Permission Checks
- `canAccess(array $parameters = []): bool`
- `canViewAny(): bool`
- `canCreate(): bool`
- `canEdit($record): bool`
- `canDelete($record): bool`
- `canDeleteAny(): bool`

## Changelog

### Version 1.0.0
- Initial implementation
- Basic role-based navigation filtering
- Visual indicators for restricted access
- Comprehensive test suite
- Documentation and examples

## Contributing

When contributing to the navigation permissions system:

1. **Follow coding standards** established in the project
2. **Add tests** for new functionality
3. **Update documentation** for any changes
4. **Consider security implications** of modifications
5. **Test with multiple roles** to ensure compatibility

## License

This navigation permissions system is part of the learning center application and follows the same license terms.