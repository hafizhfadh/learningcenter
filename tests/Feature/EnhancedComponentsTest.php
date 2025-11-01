<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Institution;
use App\Models\Course;
use App\Models\TaskSubmission;
use App\Models\ProgressLog;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnhancedComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run the role permission seeder
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** @test */
    public function institution_selector_is_accessible_by_super_admin()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user);

        $this->assertTrue(\App\Filament\Pages\InstitutionSelector::shouldRegisterNavigation());
        $this->assertTrue(\App\Filament\Pages\InstitutionSelector::canAccess());
    }

    /** @test */
    public function institution_selector_is_not_accessible_by_other_roles()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('school_teacher');

        $this->actingAs($user);

        $this->assertFalse(\App\Filament\Pages\InstitutionSelector::shouldRegisterNavigation());
    }

    /** @test */
    public function super_admin_can_access_institution_selector_page()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get('/admin/institution-selector');
        $response->assertStatus(200);
    }

    /** @test */
    public function institution_selector_displays_available_institutions()
    {
        $institution1 = Institution::factory()->create(['name' => 'Test Institution 1']);
        $institution2 = Institution::factory()->create(['name' => 'Test Institution 2']);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get('/admin/institution-selector');
        
        $response->assertSee('Test Institution 1');
        $response->assertSee('Test Institution 2');
        $response->assertSee('Available Institutions');
    }

    /** @test */
    public function institution_selector_can_switch_institutions()
    {
        $institution = Institution::factory()->create(['name' => 'Test Institution']);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user);

        // Create an instance of the page
        $page = new \App\Filament\Pages\InstitutionSelector();
        $page->switchInstitution($institution->id);

        // Check that session was set
        $this->assertEquals($institution->id, session('current_institution_id'));
    }

    /** @test */
    public function institution_selector_prevents_unauthorized_access()
    {
        $institution1 = Institution::factory()->create(['name' => 'Institution 1']);
        $institution2 = Institution::factory()->create(['name' => 'Institution 2']);

        /** @var User $user */
        $user = User::factory()->create(['institution_id' => $institution1->id]);
        $user->assignRole('school_admin');

        $this->actingAs($user);

        $page = new \App\Filament\Pages\InstitutionSelector();
        
        // Should not be able to switch to institution they don't belong to
        $page->switchInstitution($institution2->id);
        
        // Session should not be set
        $this->assertNotEquals($institution2->id, session('current_institution_id'));
    }

    /** @test */
    public function teaching_dashboard_is_accessible_by_school_teacher()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('school_teacher');

        $this->actingAs($user);

        $this->assertTrue(\App\Filament\Pages\TeachingDashboard::shouldRegisterNavigation());
        $this->assertTrue(\App\Filament\Pages\TeachingDashboard::canAccess());
    }

    /** @test */
    public function teaching_dashboard_is_not_accessible_by_students()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('student');

        $this->actingAs($user);

        $this->assertFalse(\App\Filament\Pages\TeachingDashboard::shouldRegisterNavigation());
        $this->assertFalse(\App\Filament\Pages\TeachingDashboard::canAccess());
    }

    /** @test */
    public function school_teacher_can_access_teaching_dashboard_page()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('school_teacher');

        $response = $this->actingAs($user)->get('/admin/teaching-dashboard');
        $response->assertStatus(200);
    }

    /** @test */
    public function teaching_dashboard_displays_teacher_statistics()
    {
        $institution = Institution::factory()->create();
        
        /** @var User $teacher */
        $teacher = User::factory()->create(['institution_id' => $institution->id]);
        $teacher->assignRole('school_teacher');

        // Create some test data
        $course = Course::factory()->create();

        $enrollment = Enrollment::factory()->create([
            'course_id' => $course->id,
        ]);

        $response = $this->actingAs($teacher)->get('/admin/teaching-dashboard');
        
        $response->assertSee('My Courses');
        $response->assertSee('Students');
        $response->assertSee('Pending Submissions');
        $response->assertSee('Active Enrollments');
    }

    /** @test */
    public function teaching_dashboard_shows_teacher_courses()
    {
        $institution = Institution::factory()->create();
        
        /** @var User $teacher */
        $teacher = User::factory()->create(['institution_id' => $institution->id]);
        $teacher->assignRole('school_teacher');

        $course = Course::factory()->create([
            'title' => 'Test Course',
        ]);

        $response = $this->actingAs($teacher)->get('/admin/teaching-dashboard');
        
        $response->assertSee('My Courses');
    }

    /** @test */
    public function teaching_dashboard_shows_pending_submissions()
    {
        $institution = Institution::factory()->create();
        
        /** @var User $teacher */
        $teacher = User::factory()->create(['institution_id' => $institution->id]);
        $teacher->assignRole('school_teacher');

        /** @var User $student */
        $student = User::factory()->create(['institution_id' => $institution->id]);
        $student->assignRole('student');

        $course = Course::factory()->create();

        // Create a task submission
        $submission = TaskSubmission::factory()->create([
            'student_id' => $student->id,
        ]);

        $response = $this->actingAs($teacher)->get('/admin/teaching-dashboard');
        
        $response->assertSee('Pending Submissions');
    }

    /** @test */
    public function teaching_dashboard_header_actions_are_visible_with_permissions()
    {
        /** @var User $teacher */
        $teacher = User::factory()->create();
        $teacher->assignRole('school_teacher');

        $response = $this->actingAs($teacher)->get('/admin/teaching-dashboard');
        
        // Check that action buttons are present (teacher has these permissions)
        $response->assertSee('Quick Grade');
        $response->assertSee('Student Progress');
        $response->assertSee('Manage Tasks');
    }

    /** @test */
    public function institution_selector_clears_selection_correctly()
    {
        $institution = Institution::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user);

        // Set institution in session
        session(['current_institution_id' => $institution->id]);
        
        $page = new \App\Filament\Pages\InstitutionSelector();
        $page->clearSelection();

        // Check that session was cleared
        $this->assertNull(session('current_institution_id'));
    }

    /** @test */
    public function teaching_dashboard_shows_empty_state_for_new_teachers()
    {
        /** @var User $teacher */
        $teacher = User::factory()->create();
        $teacher->assignRole('school_teacher');

        $response = $this->actingAs($teacher)->get('/admin/teaching-dashboard');
        
        $response->assertSee('No courses assigned yet');
        $response->assertSee('No pending submissions');
    }

    /** @test */
    public function institution_selector_shows_current_institution_status()
    {
        $institution = Institution::factory()->create(['name' => 'Current Institution']);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        // Set current institution in session
        session(['current_institution_id' => $institution->id]);

        $response = $this->actingAs($user)->get('/admin/institution-selector');
        
        $response->assertSee('Currently Managing: Current Institution');
        $response->assertSee('Active');
    }

    /** @test */
    public function institution_selector_shows_no_selection_state()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get('/admin/institution-selector');
        
        $response->assertSee('No Institution Selected');
        $response->assertSee('You are viewing data based on your default permissions');
    }

    /** @test */
    public function teaching_dashboard_displays_teaching_tips()
    {
        /** @var User $teacher */
        $teacher = User::factory()->create();
        $teacher->assignRole('school_teacher');

        $response = $this->actingAs($teacher)->get('/admin/teaching-dashboard');
        
        $response->assertSee('Teaching Tips');
        $response->assertSee('Course Management');
        $response->assertSee('Timely Feedback');
        $response->assertSee('Track Progress');
        $response->assertSee('Communication');
    }

    /** @test */
    public function institution_selector_shows_institution_statistics()
    {
        $institution = Institution::factory()->create(['name' => 'Test Institution']);
        
        // Create some users and courses for the institution
        User::factory()->count(5)->create(['institution_id' => $institution->id]);
        Course::factory()->count(3)->create();

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get('/admin/institution-selector');
        
        $response->assertSee('users');
        $response->assertSee('courses');
    }

    /** @test */
    public function teaching_dashboard_personalizes_greeting()
    {
        /** @var User $teacher */
        $teacher = User::factory()->create(['name' => 'John Doe']);
        $teacher->assignRole('school_teacher');

        $response = $this->actingAs($teacher)->get('/admin/teaching-dashboard');
        
        $response->assertSee('Welcome back, John Doe!');
    }

    /** @test */
    public function institution_selector_provides_helpful_instructions()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get('/admin/institution-selector');
        
        $response->assertSee('How Institution Selection Works');
        $response->assertSee('When you select an institution');
        $response->assertSee('Your selection persists');
        $response->assertSee('Super admins can switch');
        $response->assertSee('Clear Selection');
    }
}