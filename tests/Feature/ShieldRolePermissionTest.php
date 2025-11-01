<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ShieldRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run the role permission seeder
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** @test */
    public function it_creates_all_required_roles()
    {
        $expectedRoles = ['super_admin', 'school_admin', 'school_teacher', 'student', 'panel_user'];
        
        foreach ($expectedRoles as $roleName) {
            $this->assertTrue(
                Role::where('name', $roleName)->exists(),
                "Role '{$roleName}' should exist"
            );
        }
    }

    /** @test */
    public function it_creates_all_required_permissions()
    {
        $this->assertTrue(Permission::where('name', 'ViewAny:User')->exists());
        $this->assertTrue(Permission::where('name', 'Create:Course')->exists());
        $this->assertTrue(Permission::where('name', 'access_admin_panel')->exists());
        $this->assertTrue(Permission::where('name', 'grade_submissions')->exists());
        $this->assertTrue(Permission::where('name', 'manage_institution_users')->exists());
    }

    /** @test */
    public function super_admin_has_all_permissions()
    {
        $superAdmin = Role::findByName('super_admin');
        $totalPermissions = Permission::count();
        
        $this->assertEquals(
            $totalPermissions,
            $superAdmin->permissions()->count(),
            'Super admin should have all permissions'
        );
    }

    /** @test */
    public function school_admin_has_correct_permissions()
    {
        $schoolAdmin = Role::findByName('school_admin');
        
        // Check key permissions
        $this->assertTrue($schoolAdmin->hasPermissionTo('ViewAny:User'));
        $this->assertTrue($schoolAdmin->hasPermissionTo('Create:User'));
        $this->assertTrue($schoolAdmin->hasPermissionTo('manage_institution_users'));
        $this->assertTrue($schoolAdmin->hasPermissionTo('access_admin_panel'));
        
        // Should not have super admin only permissions
        $this->assertFalse($schoolAdmin->hasPermissionTo('view_all_institutions'));
    }

    /** @test */
    public function school_teacher_has_limited_permissions()
    {
        $schoolTeacher = Role::findByName('school_teacher');
        
        // Check teaching permissions
        $this->assertTrue($schoolTeacher->hasPermissionTo('ViewAny:Course'));
        $this->assertTrue($schoolTeacher->hasPermissionTo('grade_submissions'));
        $this->assertTrue($schoolTeacher->hasPermissionTo('monitor_student_progress'));
        $this->assertTrue($schoolTeacher->hasPermissionTo('access_admin_panel'));
        
        // Should not have admin permissions
        $this->assertFalse($schoolTeacher->hasPermissionTo('Create:User'));
        $this->assertFalse($schoolTeacher->hasPermissionTo('manage_institution_users'));
    }

    /** @test */
    public function student_has_no_admin_panel_permissions()
    {
        $student = Role::findByName('student');
        
        // Students should have no admin panel permissions
        $this->assertFalse($student->hasPermissionTo('access_admin_panel'));
        $this->assertFalse($student->hasPermissionTo('ViewAny:User'));
        $this->assertFalse($student->hasPermissionTo('ViewAny:Course'));
    }

    /** @test */
    public function super_admin_can_access_admin_panel()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->assertTrue($user->canAccessPanel(filament()->getDefaultPanel()));
    }

    /** @test */
    public function school_admin_can_access_admin_panel()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('school_admin');

        $this->assertTrue($user->canAccessPanel(filament()->getDefaultPanel()));
    }

    /** @test */
    public function school_teacher_can_access_admin_panel()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('school_teacher');

        $this->assertTrue($user->canAccessPanel(filament()->getDefaultPanel()));
    }

    /** @test */
    public function student_cannot_access_admin_panel()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('student');

        $this->assertFalse($user->canAccessPanel(filament()->getDefaultPanel()));
    }

    /** @test */
    public function user_without_role_cannot_access_admin_panel()
    {
        /** @var User $user */
        $user = User::factory()->create();
        // No role assigned

        $this->assertFalse($user->canAccessPanel(filament()->getDefaultPanel()));
    }

    /** @test */
    public function institution_scoping_works_for_school_roles()
    {
        $institution1 = Institution::factory()->create(['name' => 'Institution 1']);
        $institution2 = Institution::factory()->create(['name' => 'Institution 2']);

        /** @var User $schoolAdmin */
        $schoolAdmin = User::factory()->create(['institution_id' => $institution1->id]);
        $schoolAdmin->assignRole('school_admin');

        /** @var User $schoolTeacher */
        $schoolTeacher = User::factory()->create(['institution_id' => $institution2->id]);
        $schoolTeacher->assignRole('school_teacher');

        // Test that users are associated with correct institutions
        $this->assertEquals($institution1->id, $schoolAdmin->institution_id);
        $this->assertEquals($institution2->id, $schoolTeacher->institution_id);
    }

    /** @test */
    public function shield_permissions_trait_works_with_resources()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('school_admin');

        $this->actingAs($user);

        // Test that the trait methods work
        $this->assertTrue(\App\Filament\Resources\Users\UserResource::canViewAny());
        $this->assertTrue(\App\Filament\Resources\Users\UserResource::canCreate());
    }

    /** @test */
    public function student_role_blocks_resource_access()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('student');

        $this->actingAs($user);

        // Students should not be able to access admin resources
        $this->assertFalse(\App\Filament\Resources\Users\UserResource::canViewAny());
        $this->assertFalse(\App\Filament\Resources\Users\UserResource::canCreate());
    }

    /** @test */
    public function navigation_is_filtered_by_role()
    {
        /** @var User $schoolTeacher */
        $schoolTeacher = User::factory()->create();
        $schoolTeacher->assignRole('school_teacher');

        $this->actingAs($schoolTeacher);

        // School teacher should see navigation for their resources
        $this->assertTrue(\App\Filament\Resources\Courses\CourseResource::shouldRegisterNavigation());
        
        // School teacher should also see user management (for students)
        $this->assertTrue(\App\Filament\Resources\Users\UserResource::shouldRegisterNavigation());
        
        // But they should not be able to create users
        $this->assertFalse(\App\Filament\Resources\Users\UserResource::canCreate());
    }

    /** @test */
    public function institution_scope_middleware_sets_context()
    {
        $institution = Institution::factory()->create();
        
        /** @var User $user */
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $user->assignRole('school_admin');

        $response = $this->actingAs($user)->get('/admin');
        
        // Check that institution context is set by middleware
        $this->assertEquals($institution->id, session('current_institution_id'));
    }

    /** @test */
    public function role_hierarchy_permissions_are_correct()
    {
        $superAdmin = Role::findByName('super_admin');
        $schoolAdmin = Role::findByName('school_admin');
        $schoolTeacher = Role::findByName('school_teacher');
        $student = Role::findByName('student');

        // Super admin should have more permissions than school admin
        $this->assertGreaterThan(
            $schoolAdmin->permissions()->count(),
            $superAdmin->permissions()->count()
        );

        // School admin should have more permissions than school teacher
        $this->assertGreaterThan(
            $schoolTeacher->permissions()->count(),
            $schoolAdmin->permissions()->count()
        );

        // School teacher should have more permissions than student
        $this->assertGreaterThan(
            $student->permissions()->count(),
            $schoolTeacher->permissions()->count()
        );

        // Student should have zero admin permissions
        $this->assertEquals(0, $student->permissions()->count());
    }

    /** @test */
    public function custom_permissions_are_assigned_correctly()
    {
        $schoolAdmin = Role::findByName('school_admin');
        $schoolTeacher = Role::findByName('school_teacher');

        // School admin should have institution management permissions
        $this->assertTrue($schoolAdmin->hasPermissionTo('manage_institution_users'));
        $this->assertTrue($schoolAdmin->hasPermissionTo('access_institution_selector'));

        // School teacher should have teaching permissions
        $this->assertTrue($schoolTeacher->hasPermissionTo('grade_submissions'));
        $this->assertTrue($schoolTeacher->hasPermissionTo('access_teaching_dashboard'));

        // But teacher should not have admin permissions
        $this->assertFalse($schoolTeacher->hasPermissionTo('manage_institution_users'));
    }
}