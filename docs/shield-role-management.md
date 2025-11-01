# Shield-Based Multi-Role Permission Management System

## Overview

This document describes the comprehensive multi-role permission management system implemented using Filament Shield v4 for the Learning Center application. The system provides hierarchical role-based access control (RBAC) with institution-level data isolation.

## Architecture

### 4-Tier Role Hierarchy

#### 1. Super Admin (`super_admin`)
- **Access Level**: Full unrestricted access to all admin panel features and resources
- **Permissions**: All permissions (wildcard access)
- **Data Scope**: Can view and manage data across all institutions
- **Use Case**: System administrators and platform owners

#### 2. School Admin (`school_admin`)
- **Access Level**: Institution-level management with full administrative capabilities within their assigned institution
- **Permissions**: 
  - User management within institution
  - Course and learning path management
  - Enrollment management
  - Institution data management
  - Progress monitoring and analytics
  - Task and submission management
- **Data Scope**: Limited to their assigned institution
- **Use Case**: Institution administrators and principals

#### 3. School Teacher (`school_teacher`)
- **Access Level**: Limited to teaching-related resources with grading and monitoring capabilities
- **Permissions**:
  - View students (limited user access)
  - Course viewing and limited management
  - Lesson creation and management
  - Task creation and grading
  - Student progress monitoring
  - Submission grading
- **Data Scope**: Limited to their institution and assigned courses
- **Use Case**: Teachers and instructors

#### 4. Student (`student`)
- **Access Level**: Complete admin panel access restriction
- **Permissions**: None (API-only access for future implementation)
- **Data Scope**: N/A (no admin panel access)
- **Use Case**: Students who will interact via API endpoints only

#### 5. Panel User (`panel_user`)
- **Access Level**: Basic panel access for authenticated users
- **Permissions**: Dashboard access only
- **Data Scope**: Limited to basic navigation
- **Use Case**: Users who need minimal admin panel access

## Technical Implementation

### Core Components

#### 1. Filament Shield Plugin Configuration
- **File**: `config/filament-shield.php`
- **Multi-tenancy**: Enabled with `Institution` model
- **Custom Permissions**: Defined for specific functionalities
- **Super Admin**: Configured with gate-based access

#### 2. Role and Permission Seeder
- **File**: `database/seeders/RolePermissionSeeder.php`
- **Functionality**: 
  - Creates all required roles and permissions
  - Assigns permissions hierarchically
  - Supports resource, page, and widget permissions
  - Includes custom permissions for specific features

#### 3. Institution Scope Middleware
- **File**: `app/Http/Middleware/InstitutionScopeMiddleware.php`
- **Functionality**:
  - Applies institution-level data isolation
  - Sets current institution context
  - Manages session-based institution switching

#### 4. Institution Scope Trait
- **File**: `app/Traits/HasInstitutionScope.php`
- **Functionality**:
  - Automatic institution scoping for Eloquent models
  - Global scope application
  - Institution-based query filtering
  - Access control methods

#### 5. Shield Permissions Trait
- **File**: `app/Filament/Traits/HasShieldPermissions.php`
- **Functionality**:
  - Integrates Shield permissions with Filament resources
  - Applies institution-level data scoping
  - Resource-specific access control
  - Navigation filtering

### Permission Structure

#### Resource Permissions
Format: `{Action}:{Resource}`

**Actions**: ViewAny, View, Create, Update, Delete, Restore, ForceDelete, ForceDeleteAny, RestoreAny, Replicate, Reorder

**Resources**: User, Course, LearningPath, Lesson, LessonSection, Enrollment, Institution, ProgressLog, Task, TaskQuestion, TaskSubmission, Role

#### Page Permissions
Format: `View:{Page}`

**Pages**: Dashboard, TeachingDashboard, InstitutionSelector, Analytics

#### Widget Permissions
Format: `View:{Widget}`

**Widgets**: SystemStatsOverview, RecentCourses, RecentLessons, RecentUsers, RecentEnrollments, InstitutionStats, TeachingStats, StudentProgress

#### Custom Permissions
- `manage_institution_users`
- `manage_institution_data`
- `access_teaching_dashboard`
- `grade_submissions`
- `monitor_student_progress`
- `manage_enrollments`
- `access_institution_selector`
- `view_institution_analytics`
- `manage_institution_courses`
- `manage_institution_learning_paths`
- `access_admin_panel`
- `switch_institutions`
- `view_all_institutions`
- `manage_system_settings`
- `export_institution_data`
- `import_institution_data`

## Data Isolation Implementation

### Institution-Level Scoping

#### Automatic Scoping
- Applied via global Eloquent scopes
- Triggered for `school_admin` and `school_teacher` roles
- Based on user's `institution_id`

#### Manual Scoping
- Available through trait methods
- Supports custom filtering logic
- Allows scope bypassing for super admins

#### Resource-Specific Scoping

**User Resource**:
- Teachers see only students in their institution
- Admins see all users in their institution

**Course Resource**:
- Teachers see only courses they created or are assigned to
- Admins see all courses in their institution

**Enrollment Resource**:
- Teachers see enrollments for their courses only
- Admins see all enrollments in their institution

**Task Submission Resource**:
- Teachers see submissions for their tasks only
- Admins see all submissions in their institution

## Security Features

### Access Control
- **Authentication Required**: All admin panel access requires authentication
- **Role Validation**: Roles must exist in configuration
- **Permission Verification**: All actions verified against Spatie Permission
- **Institution Boundaries**: Data access limited by institution context

### Session Management
- **Institution Context**: Stored in session for persistence
- **Role Changes**: Proper session handling for role updates
- **Security Validation**: Server-side permission checks

### Data Protection
- **Scope Filtering**: Automatic data filtering based on user context
- **Access Validation**: Record-level access control
- **Permission Escalation Prevention**: Hierarchical permission structure

## Performance Optimization

### Caching Strategy
- **Permission Caching**: Leverages Spatie Permission caching
- **Query Optimization**: Efficient scope filtering
- **Session Storage**: Institution context cached in session

### Database Optimization
- **Indexed Columns**: Proper indexing for `institution_id`
- **Efficient Queries**: Optimized scope filtering
- **Minimal Queries**: Reduced database calls through caching

## Testing and Validation

### Comprehensive Test Suite
- **File**: `tests/Feature/ShieldRolePermissionTest.php`
- **Coverage**: 
  - Role creation and permission assignment
  - Access control validation
  - Institution scoping verification
  - Navigation filtering
  - Resource access control
  - Middleware functionality

### Test Scenarios
- Role hierarchy validation
- Permission assignment verification
- Admin panel access control
- Institution-level data isolation
- Resource-specific scoping
- Navigation filtering by role

## Usage Examples

### Assigning Roles to Users

```php
// Create user with institution
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@school.edu',
    'institution_id' => $institution->id,
]);

// Assign role
$user->assignRole('school_admin');
```

### Checking Permissions

```php
// Check if user can access resource
if ($user->can('ViewAny:Course')) {
    // User can view courses
}

// Check custom permission
if ($user->can('grade_submissions')) {
    // User can grade submissions
}
```

### Resource Implementation

```php
use App\Filament\Traits\HasShieldPermissions;

class CourseResource extends Resource
{
    use HasShieldPermissions;
    
    // Resource automatically inherits permission checking
    // and institution-level scoping
}
```

### Institution Scoping

```php
// Get courses for current institution
$courses = Course::forCurrentInstitution()->get();

// Bypass scoping (super admin only)
$allCourses = Course::withoutInstitutionScope()->get();
```

## Maintenance and Updates

### Adding New Roles

1. **Update Configuration**: Add role to `config/filament-shield.php`
2. **Update Seeder**: Add role and permissions to `RolePermissionSeeder.php`
3. **Run Seeder**: Execute `php artisan db:seed --class=RolePermissionSeeder`
4. **Update Tests**: Add test cases for new role

### Adding New Permissions

1. **Define Permission**: Add to seeder's permission arrays
2. **Assign to Roles**: Update role permission assignments
3. **Implement Logic**: Add permission checks to resources/pages
4. **Test**: Verify permission functionality

### Updating Institution Scoping

1. **Model Updates**: Add `HasInstitutionScope` trait to new models
2. **Migration**: Add `institution_id` column if needed
3. **Resource Updates**: Apply `HasShieldPermissions` trait
4. **Test**: Verify scoping works correctly

## Troubleshooting

### Common Issues

#### Users Cannot Access Admin Panel
- **Check Role Assignment**: Verify user has appropriate role
- **Check Permissions**: Ensure role has `access_admin_panel` permission
- **Check Institution**: Verify user has valid `institution_id`

#### Data Not Properly Scoped
- **Check Trait Usage**: Ensure model uses `HasInstitutionScope` trait
- **Check Middleware**: Verify `InstitutionScopeMiddleware` is applied
- **Check Session**: Confirm institution context is set

#### Permissions Not Working
- **Clear Cache**: Run `php artisan permission:cache-reset`
- **Check Database**: Verify permissions exist in database
- **Check Assignment**: Confirm role has required permissions

### Debug Commands

```bash
# Check user roles and permissions
php artisan tinker
>>> $user = User::find(1);
>>> $user->getRoleNames();
>>> $user->getAllPermissions();

# Clear permission cache
php artisan permission:cache-reset

# Re-run seeder
php artisan db:seed --class=RolePermissionSeeder

# Run tests
php artisan test tests/Feature/ShieldRolePermissionTest.php
```

## Security Considerations

### Best Practices
1. **Principle of Least Privilege**: Grant minimum necessary permissions
2. **Regular Audits**: Review role permissions periodically
3. **Secure Defaults**: Default to restrictive permissions
4. **Input Validation**: Validate all permission-related inputs

### Security Checklist
- [ ] All admin panel routes protected by authentication
- [ ] Student role cannot access admin panel
- [ ] Institution scoping properly applied
- [ ] Permission checks implemented at resource level
- [ ] Session security properly configured
- [ ] Database queries properly scoped

## Future Enhancements

### Planned Features
1. **API Endpoints**: Student role API access implementation
2. **Advanced Analytics**: Role-based analytics dashboards
3. **Audit Logging**: Comprehensive permission change logging
4. **Dynamic Permissions**: Runtime permission management
5. **Multi-Institution Users**: Support for users across multiple institutions

### Extensibility
- **Plugin Architecture**: Support for additional permission plugins
- **Custom Scopes**: Framework for custom data scoping
- **Role Templates**: Predefined role configurations
- **Permission Inheritance**: Advanced permission hierarchy

## Conclusion

The Shield-based multi-role permission management system provides a robust, secure, and scalable solution for managing access control in the Learning Center application. With its hierarchical role structure, institution-level data isolation, and comprehensive testing suite, it ensures that users have appropriate access to resources while maintaining data security and integrity.

The system is designed for maintainability and extensibility, allowing for easy addition of new roles, permissions, and features as the application evolves.