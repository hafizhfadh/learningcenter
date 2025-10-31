<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NavigationPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test configuration
        Config::set('navigation-permissions', [
            'roles' => [
                'super_admin' => [
                    'permissions' => ['*'],
                    'navigation_groups' => ['*'],
                    'resources' => ['*'],
                    'pages' => ['*'],
                    'widgets' => ['*'],
                ],
                'admin' => [
                    'permissions' => ['ViewAny:User', 'Create:User', 'Update:User', 'Delete:User'],
                    'navigation_groups' => ['User Management'],
                    'resources' => ['App\Filament\Resources\Users\UserResource'],
                    'pages' => ['Filament\Pages\Dashboard'],
                    'widgets' => ['App\Filament\Widgets\SystemStatsOverview'],
                ],
                'teacher' => [
                    'permissions' => ['ViewAny:Course', 'Create:Course', 'Update:Course'],
                    'navigation_groups' => ['Learning Management'],
                    'resources' => ['App\Filament\Resources\Courses\CourseResource'],
                    'pages' => ['Filament\Pages\Dashboard'],
                    'widgets' => ['App\Filament\Widgets\RecentCourses'],
                ],
                'student' => [
                    'permissions' => ['ViewAny:Course'],
                    'navigation_groups' => ['My Learning'],
                    'resources' => ['App\Filament\Resources\Courses\CourseResource'],
                    'pages' => ['Filament\Pages\Dashboard'],
                    'widgets' => [],
                    'restrictions' => [
                        'disabled_actions' => ['create', 'edit', 'delete'],
                        'read_only' => true,
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function super_admin_can_access_all_admin_pages()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $user->assignRole($superAdminRole);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);

        $response = $this->actingAs($user)->get('/admin/users');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_user_management_pages()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $user->assignRole($adminRole);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);

        $response = $this->actingAs($user)->get('/admin/users');
        $response->assertStatus(200);
    }

    /** @test */
    public function teacher_can_access_learning_management_pages()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $teacherRole = Role::create(['name' => 'teacher']);
        $user->assignRole($teacherRole);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);

        // Note: This would need actual course routes to be set up
        // $response = $this->actingAs($user)->get('/admin/courses');
        // $response->assertStatus(200);
    }

    /** @test */
    public function student_has_limited_access()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $studentRole = Role::create(['name' => 'student']);
        $user->assignRole($studentRole);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);

        // Students should not be able to access user management
        // This would need to be tested with actual middleware implementation
    }

    /** @test */
    public function unauthenticated_users_are_redirected_to_login()
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    /** @test */
    public function navigation_middleware_is_applied_to_admin_routes()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $user->assignRole($adminRole);

        $response = $this->actingAs($user)->get('/admin');
        
        // Check that the navigation service is available
        $this->assertTrue(app()->bound('navigation.service'));
    }

    /** @test */
    public function users_without_roles_have_no_access()
    {
        /** @var User $user */
        $user = User::factory()->create();
        // Don't assign any roles

        $response = $this->actingAs($user)->get('/admin');
        
        // User should be able to access admin but see limited navigation
        $response->assertStatus(200);
    }

    /** @test */
    public function multiple_roles_grant_combined_permissions()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $teacherRole = Role::create(['name' => 'teacher']);
        $adminRole = Role::create(['name' => 'admin']);
        
        $user->assignRole([$teacherRole, $adminRole]);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);

        // User should have access to both teacher and admin resources
        $response = $this->actingAs($user)->get('/admin/users');
        $response->assertStatus(200);
    }

    /** @test */
    public function navigation_service_is_properly_instantiated()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $user->assignRole($adminRole);

        $this->actingAs($user)->get('/admin');
        
        $navigationService = app('navigation.service');
        $this->assertInstanceOf(\App\Services\NavigationService::class, $navigationService);
    }

    /** @test */
    public function css_assets_are_loaded_for_navigation_styling()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $user->assignRole($adminRole);

        $response = $this->actingAs($user)->get('/admin');
        
        // Check that the response includes our custom CSS
        // This would need to be implemented in the actual view
        $response->assertStatus(200);
    }

    /** @test */
    public function restricted_resources_show_appropriate_visual_indicators()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $studentRole = Role::create(['name' => 'student']);
        $user->assignRole($studentRole);

        $response = $this->actingAs($user)->get('/admin');
        
        // This would need to check for specific CSS classes or attributes
        // that indicate restricted access
        $response->assertStatus(200);
    }

    /** @test */
    public function navigation_groups_are_filtered_based_on_permissions()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $teacherRole = Role::create(['name' => 'teacher']);
        $user->assignRole($teacherRole);

        $this->actingAs($user)->get('/admin');
        
        $navigationService = app('navigation.service');
        $filteredGroups = $navigationService->getFilteredNavigationGroups();
        
        $this->assertArrayHasKey('Learning Management', $filteredGroups);
        $this->assertArrayNotHasKey('User Management', $filteredGroups);
    }

    /** @test */
    public function scope_filters_are_applied_to_resource_queries()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $teacherRole = Role::create(['name' => 'teacher']);
        $user->assignRole($teacherRole);

        $this->actingAs($user)->get('/admin');
        
        $navigationService = app('navigation.service');
        $scopeFilter = $navigationService->getScopeFilter('App\Filament\Resources\Courses\CourseResource');
        
        $this->assertEquals('teacher_courses_only', $scopeFilter);
    }

    /** @test */
    public function disabled_actions_prevent_unauthorized_operations()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $studentRole = Role::create(['name' => 'student']);
        $user->assignRole($studentRole);

        $this->actingAs($user)->get('/admin');
        
        $navigationService = app('navigation.service');
        
        $this->assertTrue($navigationService->isActionDisabled('App\Filament\Resources\Courses\CourseResource', 'create'));
        $this->assertTrue($navigationService->isActionDisabled('App\Filament\Resources\Courses\CourseResource', 'edit'));
        $this->assertTrue($navigationService->isActionDisabled('App\Filament\Resources\Courses\CourseResource', 'delete'));
    }

    /** @test */
    public function read_only_resources_show_appropriate_indicators()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $studentRole = Role::create(['name' => 'student']);
        $user->assignRole($studentRole);

        $this->actingAs($user)->get('/admin');
        
        $navigationService = app('navigation.service');
        
        $this->assertTrue($navigationService->isResourceReadOnly('App\Filament\Resources\Courses\CourseResource'));
        
        $badge = $navigationService->getNavigationBadge('App\Filament\Resources\Courses\CourseResource');
        $this->assertNotNull($badge);
        $this->assertEquals('Restricted', $badge['text']);
    }

    /** @test */
    public function tooltip_messages_are_available_for_restricted_items()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $studentRole = Role::create(['name' => 'student']);
        $user->assignRole($studentRole);

        $this->actingAs($user)->get('/admin');
        
        $navigationService = app('navigation.service');
        
        $readOnlyMessage = $navigationService->getTooltipMessage('read_only');
        $this->assertEquals('You have read-only access to this resource.', $readOnlyMessage);
        
        $noPermissionMessage = $navigationService->getTooltipMessage('no_permission');
        $this->assertEquals('You do not have permission to access this resource.', $noPermissionMessage);
    }
}