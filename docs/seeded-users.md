# Seeded Users and Roles

This document provides information about the users created by the `UserRoleSeeder` for testing the Shield-based role management system.

## Default Password

**All seeded users have the same password: `password`**

## User Categories

### 1. Super Administrators
Super admins have unrestricted access to all features and can manage all institutions.

| Name | Email | Institution | Role |
|------|-------|-------------|------|
| System Administrator | admin@learningcenter.com | None | super_admin |
| Platform Manager | manager@learningcenter.com | None | super_admin |

**Capabilities:**
- Full access to all admin panel features
- Can switch between any institution using the Institution Selector
- Can manage users, courses, and data across all institutions
- Access to system-wide analytics and settings

### 2. School Administrators
School admins have full administrative access within their assigned institution.

| Name | Email | Institution | Role |
|------|-------|-------------|------|
| Dr. Sarah Johnson | sarah.johnson@harvard.edu | Harvard University | school_admin |
| Prof. Michael Chen | michael.chen@stanford.edu | Stanford University | school_admin |
| Dr. Emily Rodriguez | emily.rodriguez@mit.edu | MIT | school_admin |

**Capabilities:**
- Full administrative access within their institution
- Can manage users, courses, and enrollments for their institution
- Access to institution-specific analytics
- Can use the Institution Selector (limited to their institution)
- Can create and manage learning paths and lessons

### 3. School Teachers
Teachers have access to teaching-related features and can manage their courses and students.

| Name | Email | Institution | Role |
|------|-------|-------------|------|
| Prof. David Wilson | david.wilson@harvard.edu | Harvard University | school_teacher |
| Dr. Lisa Thompson | lisa.thompson@harvard.edu | Harvard University | school_teacher |
| Prof. James Anderson | james.anderson@stanford.edu | Stanford University | school_teacher |
| Dr. Maria Garcia | maria.garcia@stanford.edu | Stanford University | school_teacher |
| Prof. Robert Kim | robert.kim@mit.edu | MIT | school_teacher |
| Dr. Jennifer Lee | jennifer.lee@mit.edu | MIT | school_teacher |

**Capabilities:**
- Access to the Teaching Dashboard
- Can view and manage their courses
- Can grade student submissions
- Can monitor student progress
- Can create and manage tasks and assignments
- Limited user management (can view students)

### 4. Students
Students have no admin panel access and are intended for API-only interactions.

| Name | Email | Institution | Role |
|------|-------|-------------|------|
| Alice Johnson | alice.johnson@student.harvard.edu | Harvard University | student |
| Bob Smith | bob.smith@student.harvard.edu | Harvard University | student |
| Carol Davis | carol.davis@student.harvard.edu | Harvard University | student |
| Daniel Brown | daniel.brown@student.stanford.edu | Stanford University | student |
| Emma Wilson | emma.wilson@student.stanford.edu | Stanford University | student |
| Frank Miller | frank.miller@student.stanford.edu | Stanford University | student |
| Grace Taylor | grace.taylor@student.mit.edu | MIT | student |
| Henry Anderson | henry.anderson@student.mit.edu | MIT | student |
| Ivy Chen | ivy.chen@student.mit.edu | MIT | student |

**Capabilities:**
- **No admin panel access** (blocked by design)
- Intended for future API-based interactions
- Can be enrolled in courses
- Can submit assignments and track progress (via API)

### 5. Panel Users
Basic users with minimal admin panel access for demonstration purposes.

| Name | Email | Institution | Role |
|------|-------|-------------|------|
| Guest User | guest@learningcenter.com | None | panel_user |
| Demo User | demo@learningcenter.com | None | panel_user |

**Capabilities:**
- Basic dashboard access only
- Limited navigation options
- Useful for demonstrating basic panel functionality

## Institutions Created

The seeder also creates three sample institutions:

1. **Harvard University**
   - Domain: harvard.edu
   - Slug: harvard-university

2. **Stanford University**
   - Domain: stanford.edu
   - Slug: stanford-university

3. **MIT**
   - Domain: mit.edu
   - Slug: mit

## Testing Scenarios

### 1. Super Admin Testing
Login as `admin@learningcenter.com` to test:
- Institution Selector functionality
- Cross-institution data access
- System-wide permissions
- User management across institutions

### 2. School Admin Testing
Login as `sarah.johnson@harvard.edu` to test:
- Institution-specific data isolation
- User management within institution
- Course and enrollment management
- Institution analytics

### 3. Teacher Testing
Login as `david.wilson@harvard.edu` to test:
- Teaching Dashboard functionality
- Course management capabilities
- Student progress monitoring
- Grading and task management

### 4. Student Access Testing
Try to login as `alice.johnson@student.harvard.edu` to verify:
- Admin panel access is blocked
- Proper error handling for unauthorized access

### 5. Role Switching Testing
Use super admin account to test:
- Institution switching functionality
- Permission inheritance
- Data filtering by institution context

## Running the Seeder

To populate your database with these users:

```bash
# Run just the UserRoleSeeder
php artisan db:seed --class=UserRoleSeeder

# Or run all seeders (includes UserRoleSeeder)
php artisan db:seed
```

## Security Notes

1. **Change Default Passwords**: In production, ensure all default passwords are changed
2. **Remove Test Users**: Remove or disable test users before deploying to production
3. **Audit Permissions**: Regularly review user roles and permissions
4. **Monitor Access**: Implement logging for administrative actions

## Verification Commands

Check that users were created correctly:

```bash
# Check total users and role distribution
php artisan tinker --execute="
use App\Models\User; 
use Spatie\Permission\Models\Role; 
echo 'Total users: ' . User::count() . PHP_EOL; 
foreach(Role::all() as \$role) { 
    echo \$role->name . ': ' . User::role(\$role->name)->count() . ' users' . PHP_EOL; 
}"

# Check specific user details
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'admin@learningcenter.com')->first();
echo 'User: ' . \$user->name . PHP_EOL;
echo 'Roles: ' . \$user->roles->pluck('name')->join(', ') . PHP_EOL;
echo 'Can access panel: ' . (\$user->canAccessPanel(filament()->getDefaultPanel()) ? 'YES' : 'NO') . PHP_EOL;
"
```

This seeded data provides a comprehensive testing environment for the Shield-based role management system with realistic user scenarios and proper permission hierarchies.