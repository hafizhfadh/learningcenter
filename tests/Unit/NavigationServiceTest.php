<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NavigationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NavigationService $navigationService;
    protected User $user;

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
                    'permissions' => ['ViewAny:User', 'Create:User'],
                    'navigation_groups' => ['User Management'],
                    'resources' => ['App\Filament\Resources\Users\UserResource'],
                    'pages' => ['Filament\Pages\Dashboard'],
                    'widgets' => ['App\Filament\Widgets\SystemStatsOverview'],
                ],
                'teacher' => [
                    'permissions' => ['ViewAny:Course', 'Create:Course'],
                    'navigation_groups' => ['Learning Management'],
                    'resources' => ['App\Filament\Resources\Courses\CourseResource'],
                    'pages' => ['Filament\Pages\Dashboard'],
                    'widgets' => ['App\Filament\Widgets\RecentCourses'],
                    'restrictions' => [
                        'scope_filters' => [
                            'App\Filament\Resources\Courses\CourseResource' => 'teacher_courses_only',
                        ],
                    ],
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
            'navigation_groups' => [
                'User Management' => ['icon' => 'heroicon-o-users', 'sort' => 10],
                'Learning Management' => ['icon' => 'heroicon-o-academic-cap', 'sort' => 20],
                'My Learning' => ['icon' => 'heroicon-o-book-open', 'sort' => 30],
            ],
            'visual_indicators' => [
                'disabled_opacity' => 0.5,
                'restricted_badge' => ['enabled' => true, 'text' => 'Restricted', 'color' => 'warning'],
                'tooltip_messages' => [
                    'no_permission' => 'You do not have permission to access this resource.',
                    'restricted_access' => 'Your access to this resource is limited.',
                    'read_only' => 'You have read-only access to this resource.',
                ],
            ],
            'cache' => ['enabled' => false], // Disable cache for testing
        ]);

        $this->user = User::factory()->create();
        $this->navigationService = new NavigationService();
    }

    /** @test */
    public function it_allows_super_admin_access_to_everything()
    {
        // Create super admin role and assign to user
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $this->user->assignRole($superAdminRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        $this->assertTrue($service->canAccessNavigationGroup('User Management'));
        $this->assertTrue($service->canAccessNavigationGroup('Learning Management'));
        $this->assertTrue($service->canAccessResource('App\Filament\Resources\Users\UserResource'));
        $this->assertTrue($service->canAccessPage('Filament\Pages\Dashboard'));
        $this->assertTrue($service->canAccessWidget('App\Filament\Widgets\SystemStatsOverview'));
        $this->assertTrue($service->isSuperAdmin());
    }

    /** @test */
    public function it_restricts_admin_access_correctly()
    {
        $adminRole = Role::create(['name' => 'admin']);
        $this->user->assignRole($adminRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        $this->assertTrue($service->canAccessNavigationGroup('User Management'));
        $this->assertFalse($service->canAccessNavigationGroup('Learning Management'));
        $this->assertTrue($service->canAccessResource('App\Filament\Resources\Users\UserResource'));
        $this->assertFalse($service->canAccessResource('App\Filament\Resources\Courses\CourseResource'));
        $this->assertFalse($service->isSuperAdmin());
    }

    /** @test */
    public function it_restricts_teacher_access_correctly()
    {
        $teacherRole = Role::create(['name' => 'teacher']);
        $this->user->assignRole($teacherRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        $this->assertFalse($service->canAccessNavigationGroup('User Management'));
        $this->assertTrue($service->canAccessNavigationGroup('Learning Management'));
        $this->assertFalse($service->canAccessResource('App\Filament\Resources\Users\UserResource'));
        $this->assertTrue($service->canAccessResource('App\Filament\Resources\Courses\CourseResource'));
    }

    /** @test */
    public function it_restricts_student_access_correctly()
    {
        $studentRole = Role::create(['name' => 'student']);
        $this->user->assignRole($studentRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        $this->assertFalse($service->canAccessNavigationGroup('User Management'));
        $this->assertFalse($service->canAccessNavigationGroup('Learning Management'));
        $this->assertTrue($service->canAccessNavigationGroup('My Learning'));
        $this->assertTrue($service->canAccessResource('App\Filament\Resources\Courses\CourseResource'));
        $this->assertTrue($service->isResourceReadOnly('App\Filament\Resources\Courses\CourseResource'));
    }

    /** @test */
    public function it_handles_unauthenticated_users()
    {
        Auth::logout();
        $service = new NavigationService();

        $this->assertFalse($service->canAccessNavigationGroup('User Management'));
        $this->assertFalse($service->canAccessResource('App\Filament\Resources\Users\UserResource'));
        $this->assertFalse($service->canAccessPage('Filament\Pages\Dashboard'));
        $this->assertFalse($service->isSuperAdmin());
    }

    /** @test */
    public function it_returns_correct_scope_filters()
    {
        $teacherRole = Role::create(['name' => 'teacher']);
        $this->user->assignRole($teacherRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        $scopeFilter = $service->getScopeFilter('App\Filament\Resources\Courses\CourseResource');
        $this->assertEquals('teacher_courses_only', $scopeFilter);

        $noScopeFilter = $service->getScopeFilter('App\Filament\Resources\Users\UserResource');
        $this->assertNull($noScopeFilter);
    }

    /** @test */
    public function it_checks_disabled_actions_correctly()
    {
        $studentRole = Role::create(['name' => 'student']);
        $this->user->assignRole($studentRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        $this->assertTrue($service->isActionDisabled('App\Filament\Resources\Courses\CourseResource', 'create'));
        $this->assertTrue($service->isActionDisabled('App\Filament\Resources\Courses\CourseResource', 'edit'));
        $this->assertTrue($service->isActionDisabled('App\Filament\Resources\Courses\CourseResource', 'delete'));
        $this->assertFalse($service->isActionDisabled('App\Filament\Resources\Courses\CourseResource', 'view'));
    }

    /** @test */
    public function it_returns_correct_visual_indicators()
    {
        $service = new NavigationService();
        $indicators = $service->getVisualIndicators();

        $this->assertEquals(0.5, $indicators['disabled_opacity']);
        $this->assertTrue($indicators['restricted_badge']['enabled']);
        $this->assertEquals('Restricted', $indicators['restricted_badge']['text']);
        $this->assertEquals('warning', $indicators['restricted_badge']['color']);
    }

    /** @test */
    public function it_returns_correct_tooltip_messages()
    {
        $service = new NavigationService();

        $this->assertEquals(
            'You do not have permission to access this resource.',
            $service->getTooltipMessage('no_permission')
        );
        $this->assertEquals(
            'You have read-only access to this resource.',
            $service->getTooltipMessage('read_only')
        );
        $this->assertEquals(
            'Access restricted',
            $service->getTooltipMessage('unknown_type')
        );
    }

    /** @test */
    public function it_returns_navigation_badge_for_restricted_resources()
    {
        $studentRole = Role::create(['name' => 'student']);
        $this->user->assignRole($studentRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        $badge = $service->getNavigationBadge('App\Filament\Resources\Courses\CourseResource');
        $this->assertNotNull($badge);
        $this->assertEquals('Restricted', $badge['text']);
        $this->assertEquals('warning', $badge['color']);
    }

    /** @test */
    public function it_returns_null_badge_for_unrestricted_resources()
    {
        $adminRole = Role::create(['name' => 'admin']);
        $this->user->assignRole($adminRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        $badge = $service->getNavigationBadge('App\Filament\Resources\Users\UserResource');
        $this->assertNull($badge);
    }

    /** @test */
    public function it_handles_multiple_roles_correctly()
    {
        $teacherRole = Role::create(['name' => 'teacher']);
        $adminRole = Role::create(['name' => 'admin']);
        
        $this->user->assignRole([$teacherRole, $adminRole]);
        
        Auth::login($this->user);
        $service = new NavigationService();

        // Should have access from both roles
        $this->assertTrue($service->canAccessNavigationGroup('User Management')); // from admin
        $this->assertTrue($service->canAccessNavigationGroup('Learning Management')); // from teacher
        $this->assertTrue($service->canAccessResource('App\Filament\Resources\Users\UserResource')); // from admin
        $this->assertTrue($service->canAccessResource('App\Filament\Resources\Courses\CourseResource')); // from teacher
    }

    /** @test */
    public function it_handles_permission_checking_correctly()
    {
        $adminRole = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'ViewAny:User']);
        $adminRole->givePermissionTo($permission);
        
        $this->user->assignRole($adminRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        $this->assertTrue($service->hasPermission('ViewAny:User'));
        $this->assertFalse($service->hasPermission('Delete:User'));
    }

    /** @test */
    public function it_returns_filtered_navigation_groups()
    {
        $teacherRole = Role::create(['name' => 'teacher']);
        $this->user->assignRole($teacherRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        $filteredGroups = $service->getFilteredNavigationGroups();
        
        $this->assertArrayHasKey('Learning Management', $filteredGroups);
        $this->assertArrayNotHasKey('User Management', $filteredGroups);
        $this->assertArrayNotHasKey('My Learning', $filteredGroups);
    }

    /** @test */
    public function it_handles_cache_operations()
    {
        // Enable cache for this test
        Config::set('navigation-permissions.cache.enabled', true);
        
        $adminRole = Role::create(['name' => 'admin']);
        $this->user->assignRole($adminRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        // Clear cache
        $service->clearUserCache($this->user->id);
        $this->assertFalse(Cache::has("navigation_permissions:user_roles_permissions:{$this->user->id}"));

        // Access should populate cache
        $service->canAccessResource('App\Filament\Resources\Users\UserResource');
        $this->assertTrue(Cache::has("navigation_permissions:user_roles_permissions:{$this->user->id}"));
    }

    /** @test */
    public function it_handles_edge_cases_gracefully()
    {
        // Test with non-existent role
        $unknownRole = Role::create(['name' => 'unknown_role']);
        $this->user->assignRole($unknownRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        $this->assertFalse($service->canAccessNavigationGroup('User Management'));
        $this->assertFalse($service->canAccessResource('App\Filament\Resources\Users\UserResource'));
        
        // Test with empty configuration
        Config::set('navigation-permissions.roles', []);
        $service = new NavigationService();
        
        $this->assertFalse($service->canAccessNavigationGroup('User Management'));
        $this->assertFalse($service->canAccessResource('App\Filament\Resources\Users\UserResource'));
    }

    /** @test */
    public function it_handles_resource_restrictions_correctly()
    {
        $studentRole = Role::create(['name' => 'student']);
        $this->user->assignRole($studentRole);
        
        Auth::login($this->user);
        $service = new NavigationService();

        $restrictions = $service->getResourceRestrictions('App\Filament\Resources\Courses\CourseResource');
        
        $this->assertArrayHasKey('disabled_actions', $restrictions);
        $this->assertContains('create', $restrictions['disabled_actions']);
        $this->assertContains('edit', $restrictions['disabled_actions']);
        $this->assertContains('delete', $restrictions['disabled_actions']);
        $this->assertTrue($restrictions['read_only']);
    }
}