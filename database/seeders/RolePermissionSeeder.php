<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Institution;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for all resources
        $this->createResourcePermissions();
        
        // Create custom permissions
        $this->createCustomPermissions();
        
        // Create roles with hierarchical permissions
        $this->createRoles();
        
        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Create permissions for all Filament resources
     */
    private function createResourcePermissions(): void
    {
        $resources = [
            'User',
            'Course',
            'LearningPath',
            'Lesson',
            'LessonSection',
            'Enrollment',
            'Institution',
            'ProgressLog',
            'Task',
            'TaskQuestion',
            'TaskSubmission',
            'Role',
        ];

        $actions = [
            'ViewAny',
            'View',
            'Create',
            'Update',
            'Delete',
            'Restore',
            'ForceDelete',
            'ForceDeleteAny',
            'RestoreAny',
            'Replicate',
            'Reorder',
        ];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action}:{$resource}",
                    'guard_name' => 'web',
                ]);
            }
        }

        // Create page permissions
        $pages = [
            'Dashboard',
            'TeachingDashboard',
            'InstitutionSelector',
            'Analytics',
        ];

        foreach ($pages as $page) {
            Permission::firstOrCreate([
                'name' => "View:{$page}",
                'guard_name' => 'web',
            ]);
        }

        // Create widget permissions
        $widgets = [
            'SystemStatsOverview',
            'RecentCourses',
            'RecentLessons',
            'RecentUsers',
            'RecentEnrollments',
            'InstitutionStats',
            'TeachingStats',
            'StudentProgress',
        ];

        foreach ($widgets as $widget) {
            Permission::firstOrCreate([
                'name' => "View:{$widget}",
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * Create custom permissions for specific functionalities
     */
    private function createCustomPermissions(): void
    {
        $customPermissions = [
            'manage_institution_users',
            'manage_institution_data',
            'access_teaching_dashboard',
            'grade_submissions',
            'monitor_student_progress',
            'manage_enrollments',
            'access_institution_selector',
            'view_institution_analytics',
            'manage_institution_courses',
            'manage_institution_learning_paths',
            'access_admin_panel',
            'switch_institutions',
            'view_all_institutions',
            'manage_system_settings',
            'export_institution_data',
            'import_institution_data',
        ];

        foreach ($customPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * Create the 4-tier role hierarchy
     */
    private function createRoles(): void
    {
        // 1. Super Admin - Full unrestricted access
        Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        // 2. School Admin - Institution-level management
        Role::firstOrCreate([
            'name' => 'school_admin',
            'guard_name' => 'web',
        ]);

        // 3. School Teacher - Limited to teaching resources
        Role::firstOrCreate([
            'name' => 'school_teacher',
            'guard_name' => 'web',
        ]);

        // 4. Student - API-only access (no admin panel)
        Role::firstOrCreate([
            'name' => 'student',
            'guard_name' => 'web',
        ]);

        // Panel user role for basic access
        Role::firstOrCreate([
            'name' => 'panel_user',
            'guard_name' => 'web',
        ]);
    }

    /**
     * Assign permissions to roles based on hierarchy
     */
    private function assignPermissionsToRoles(): void
    {
        // Super Admin - All permissions
        $superAdmin = Role::findByName('super_admin');
        $superAdmin->givePermissionTo(Permission::all());

        // School Admin - Institution-level permissions
        $schoolAdmin = Role::findByName('school_admin');
        $schoolAdminPermissions = [
            // User management within institution
            'ViewAny:User', 'View:User', 'Create:User', 'Update:User', 'Delete:User',
            
            // Course management within institution
            'ViewAny:Course', 'View:Course', 'Create:Course', 'Update:Course', 'Delete:Course',
            'manage_institution_courses',
            
            // Learning path management within institution
            'ViewAny:LearningPath', 'View:LearningPath', 'Create:LearningPath', 'Update:LearningPath', 'Delete:LearningPath',
            'manage_institution_learning_paths',
            
            // Lesson management within institution
            'ViewAny:Lesson', 'View:Lesson', 'Create:Lesson', 'Update:Lesson', 'Delete:Lesson',
            'ViewAny:LessonSection', 'View:LessonSection', 'Create:LessonSection', 'Update:LessonSection', 'Delete:LessonSection',
            
            // Enrollment management
            'ViewAny:Enrollment', 'View:Enrollment', 'Create:Enrollment', 'Update:Enrollment', 'Delete:Enrollment',
            'manage_enrollments',
            
            // Institution data
            'View:Institution', 'Update:Institution',
            'manage_institution_data', 'manage_institution_users',
            
            // Progress monitoring
            'ViewAny:ProgressLog', 'View:ProgressLog',
            'monitor_student_progress',
            
            // Task management
            'ViewAny:Task', 'View:Task', 'Create:Task', 'Update:Task', 'Delete:Task',
            'ViewAny:TaskQuestion', 'View:TaskQuestion', 'Create:TaskQuestion', 'Update:TaskQuestion', 'Delete:TaskQuestion',
            'ViewAny:TaskSubmission', 'View:TaskSubmission', 'Update:TaskSubmission',
            'grade_submissions',
            
            // Dashboard and analytics
            'View:Dashboard', 'access_institution_selector', 'view_institution_analytics',
            'access_admin_panel',
            
            // Widgets
            'View:SystemStatsOverview', 'View:RecentCourses', 'View:RecentLessons', 
            'View:RecentUsers', 'View:RecentEnrollments', 'View:InstitutionStats',
            
            // Data management
            'export_institution_data', 'import_institution_data',
        ];
        $schoolAdmin->givePermissionTo($schoolAdminPermissions);

        // School Teacher - Limited to teaching resources
        $schoolTeacher = Role::findByName('school_teacher');
        $schoolTeacherPermissions = [
            // Limited user viewing (students only)
            'ViewAny:User', 'View:User',
            
            // Course viewing and limited management
            'ViewAny:Course', 'View:Course', 'Update:Course',
            
            // Learning path viewing
            'ViewAny:LearningPath', 'View:LearningPath',
            
            // Lesson viewing and management
            'ViewAny:Lesson', 'View:Lesson', 'Create:Lesson', 'Update:Lesson',
            'ViewAny:LessonSection', 'View:LessonSection', 'Create:LessonSection', 'Update:LessonSection',
            
            // Enrollment viewing
            'ViewAny:Enrollment', 'View:Enrollment',
            
            // Progress monitoring
            'ViewAny:ProgressLog', 'View:ProgressLog', 'Create:ProgressLog', 'Update:ProgressLog',
            'monitor_student_progress',
            
            // Task management and grading
            'ViewAny:Task', 'View:Task', 'Create:Task', 'Update:Task',
            'ViewAny:TaskQuestion', 'View:TaskQuestion', 'Create:TaskQuestion', 'Update:TaskQuestion',
            'ViewAny:TaskSubmission', 'View:TaskSubmission', 'Update:TaskSubmission',
            'grade_submissions',
            
            // Teaching dashboard
            'View:Dashboard', 'access_teaching_dashboard',
            'access_admin_panel',
            
            // Teaching widgets
            'View:RecentCourses', 'View:RecentLessons', 'View:TeachingStats', 'View:StudentProgress',
        ];
        $schoolTeacher->givePermissionTo($schoolTeacherPermissions);

        // Student - No admin panel access (API only)
        $student = Role::findByName('student');
        // Students get no admin panel permissions - they will use API endpoints only
        
        // Panel User - Basic access
        $panelUser = Role::findByName('panel_user');
        $panelUserPermissions = [
            'View:Dashboard',
            'access_admin_panel',
        ];
        $panelUser->givePermissionTo($panelUserPermissions);
    }
}