<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Institution;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;

class InstitutionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'student']);
        Role::create(['name' => 'school_teacher']);
        Role::create(['name' => 'school_admin']);
        Role::create(['name' => 'super_admin']);
    }

    /** @test */
    public function student_can_access_their_institution_information()
    {
        // Create institution
        $institution = Institution::create([
            'name' => 'Test University',
            'slug' => 'test-university',
            'domain' => 'test.edu',
            'settings' => [
                'timezone' => 'America/New_York',
                'academic_year' => '2024-2025'
            ]
        ]);
        
        // Create student user
        $student = User::factory()->create([
            'institution_id' => $institution->id,
            'email' => 'student@test.edu'
        ]);
        $student->assignRole('student');
        
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/institution');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'code',
                     'message',
                     'data' => [
                         'id',
                         'name',
                         'slug',
                         'domain',
                         'settings',
                         'created_at',
                         'updated_at'
                     ],
                     'pagination'
                 ])
                 ->assertJson([
                     'code' => 200,
                     'message' => 'Institution information retrieved successfully',
                     'data' => [
                         'id' => $institution->id,
                         'name' => 'Test University',
                         'slug' => 'test-university',
                         'domain' => 'test.edu',
                         'settings' => [
                             'timezone' => 'America/New_York',
                             'academic_year' => '2024-2025'
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function school_teacher_can_access_their_institution_information()
    {
        // Create institution
        $institution = Institution::create([
            'name' => 'Teacher University',
            'slug' => 'teacher-university',
            'domain' => 'teacher.edu',
            'settings' => []
        ]);
        
        // Create teacher user
        $teacher = User::factory()->create([
            'institution_id' => $institution->id,
            'email' => 'teacher@teacher.edu'
        ]);
        $teacher->assignRole('school_teacher');
        
        Sanctum::actingAs($teacher);

        $response = $this->getJson('/api/institution');

        $response->assertStatus(200)
                 ->assertJson([
                     'code' => 200,
                     'message' => 'Institution information retrieved successfully',
                     'data' => [
                         'id' => $institution->id,
                         'name' => 'Teacher University',
                         'slug' => 'teacher-university',
                         'domain' => 'teacher.edu'
                     ]
                 ]);
    }

    /** @test */
    public function school_admin_can_access_their_institution_information()
    {
        // Create institution
        $institution = Institution::create([
            'name' => 'Admin University',
            'slug' => 'admin-university',
            'domain' => 'admin.edu',
            'settings' => ['contact_email' => 'admin@admin.edu']
        ]);
        
        // Create admin user
        $admin = User::factory()->create([
            'institution_id' => $institution->id,
            'email' => 'admin@admin.edu'
        ]);
        $admin->assignRole('school_admin');
        
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/institution');

        $response->assertStatus(200)
                 ->assertJson([
                     'code' => 200,
                     'message' => 'Institution information retrieved successfully',
                     'data' => [
                         'id' => $institution->id,
                         'name' => 'Admin University',
                         'slug' => 'admin-university',
                         'domain' => 'admin.edu',
                         'settings' => ['contact_email' => 'admin@admin.edu']
                     ]
                 ]);
    }

    /** @test */
    public function super_admin_cannot_access_institution_information()
    {
        // Create super admin user (not bound to institution)
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        
        Sanctum::actingAs($superAdmin);

        $response = $this->getJson('/api/institution');

        $response->assertStatus(403)
                 ->assertJson([
                     'code' => 403,
                     'message' => 'Access denied. Only users with institution-bound roles can access institution information'
                 ]);
    }

    /** @test */
    public function user_without_institution_gets_404_error()
    {
        // Create student user without institution
        $student = User::factory()->create(['institution_id' => null]);
        $student->assignRole('student');
        
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/institution');

        $response->assertStatus(404)
                 ->assertJson([
                     'code' => 404,
                     'message' => 'No institution found for this user'
                 ]);
    }

    /** @test */
    public function user_with_invalid_institution_id_gets_404_error()
    {
        // Create student user with non-existent institution ID
        $student = User::factory()->create(['institution_id' => 999]);
        $student->assignRole('student');
        
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/institution');

        $response->assertStatus(404)
                 ->assertJson([
                     'code' => 404,
                     'message' => 'No institution found for this user'
                 ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_institution_information()
    {
        $response = $this->getJson('/api/institution');

        $response->assertStatus(401);
    }

    /** @test */
    public function institution_settings_are_properly_returned()
    {
        // Create institution with complex settings
        $institution = Institution::create([
            'name' => 'Settings University',
            'slug' => 'settings-university',
            'domain' => 'settings.edu',
            'settings' => [
                'timezone' => 'America/Los_Angeles',
                'academic_year' => '2024-2025',
                'contact_email' => 'contact@settings.edu',
                'phone' => '+1-555-0123',
                'address' => '123 University Ave',
                'website' => 'https://settings.edu',
                'enrollment_open' => true,
                'max_students' => 5000
            ]
        ]);
        
        // Create student user
        $student = User::factory()->create([
            'institution_id' => $institution->id
        ]);
        $student->assignRole('student');
        
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/institution');

        $response->assertStatus(200)
                 ->assertJson([
                     'code' => 200,
                     'data' => [
                         'settings' => [
                             'timezone' => 'America/Los_Angeles',
                             'academic_year' => '2024-2025',
                             'contact_email' => 'contact@settings.edu',
                             'phone' => '+1-555-0123',
                             'address' => '123 University Ave',
                             'website' => 'https://settings.edu',
                             'enrollment_open' => true,
                             'max_students' => 5000
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function institution_with_null_settings_returns_empty_array()
    {
        // Create institution with null settings
        $institution = Institution::create([
            'name' => 'No Settings University',
            'slug' => 'no-settings-university',
            'domain' => 'nosettings.edu',
            'settings' => null
        ]);
        
        // Create student user
        $student = User::factory()->create([
            'institution_id' => $institution->id
        ]);
        $student->assignRole('student');
        
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/institution');

        $response->assertStatus(200)
                 ->assertJson([
                     'code' => 200,
                     'data' => [
                         'settings' => []
                     ]
                 ]);
    }
}