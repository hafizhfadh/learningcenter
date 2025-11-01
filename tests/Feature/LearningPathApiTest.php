<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Institution;
use App\Models\LearningPath;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;

class LearningPathApiTest extends TestCase
{
    use RefreshDatabase;

    protected $student;
    protected $institution;
    protected $learningPath;
    protected $course;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'student']);
        Role::create(['name' => 'school_admin']);
        
        // Create institution
        $this->institution = Institution::create([
            'name' => 'Test University',
            'slug' => 'test-university',
            'domain' => 'test.edu',
            'settings' => []
        ]);
        
        // Create student user
        $this->student = User::factory()->create([
            'institution_id' => $this->institution->id,
            'email' => 'student@test.edu'
        ]);
        $this->student->assignRole('student');
        
        // Create learning path
        $this->learningPath = LearningPath::create([
            'name' => 'Test Learning Path',
            'slug' => 'test-learning-path',
            'description' => 'A test learning path for students',
            'institution_id' => $this->institution->id,
            'is_active' => 1
        ]);
        
        // Create course
        $this->course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'banner' => 'test-banner.jpg',
            'description' => 'A test course',
            'is_published' => true,
            'estimated_time' => 30
        ]);
        
        // Associate course with learning path
        $this->learningPath->courses()->attach($this->course->id, ['order_index' => 1]);
        
        // Create lessons for the course
        Lesson::factory(5)->create(['course_id' => $this->course->id]);
    }

    /** @test */
    public function authenticated_student_can_get_learning_paths_list()
    {
        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/learning-paths');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'code',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'slug',
                             'description',
                             'banner_url',
                             'is_active',
                             'total_estimated_time',
                             'courses_count',
                             'is_enrolled',
                             'progress',
                             'institution' => [
                                 'id',
                                 'name',
                                 'slug'
                             ],
                             'created_at',
                             'updated_at'
                         ]
                     ],
                     'pagination'
                 ]);

        $this->assertEquals(200, $response->json('code'));
        $this->assertEquals('Learning paths retrieved successfully', $response->json('message'));
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function student_can_only_see_learning_paths_from_their_institution()
    {
        // Create another institution and learning path
        $otherInstitution = Institution::create([
            'name' => 'Other University',
            'slug' => 'other-university',
            'domain' => 'other.edu',
            'settings' => []
        ]);
        
        LearningPath::create([
            'name' => 'Other Learning Path',
            'slug' => 'other-learning-path',
            'description' => 'A learning path from another institution',
            'institution_id' => $otherInstitution->id,
            'is_active' => 1
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/learning-paths');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->learningPath->id, $response->json('data.0.id'));
    }

    /** @test */
    public function student_can_search_learning_paths()
    {
        // Create another learning path with different name
        LearningPath::create([
            'name' => 'Advanced Programming',
            'slug' => 'advanced-programming',
            'description' => 'Advanced programming concepts',
            'institution_id' => $this->institution->id,
            'is_active' => 1
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/learning-paths?search=Test');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Test Learning Path', $response->json('data.0.name'));
    }

    /** @test */
    public function student_can_filter_learning_paths_by_enrollment_status()
    {
        // Enroll student in the learning path
        Enrollment::create([
            'user_id' => $this->student->id,
            'learning_path_id' => $this->learningPath->id,
            'progress' => 0,
            'enrollment_status' => 'enrolled'
        ]);

        Sanctum::actingAs($this->student);

        // Test enrolled filter
        $response = $this->getJson('/api/learning-paths?enrolled=enrolled');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // Test not_enrolled filter
        $response = $this->getJson('/api/learning-paths?enrolled=not_enrolled');
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    /** @test */
    public function student_can_get_learning_path_details()
    {
        Sanctum::actingAs($this->student);

        $response = $this->getJson("/api/learning-paths/{$this->learningPath->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'code',
                     'message',
                     'data' => [
                         'id',
                         'name',
                         'slug',
                         'description',
                         'banner_url',
                         'is_active',
                         'total_estimated_time',
                         'courses_count',
                         'is_enrolled',
                         'progress',
                         'institution',
                         'courses' => [
                             '*' => [
                                 'id',
                                 'title',
                                 'slug',
                                 'description',
                                 'banner_url',
                                 'estimated_time',
                                 'is_published',
                                 'order_index',
                                 'lessons_count',
                                 'user_progress' => [
                                     'is_enrolled',
                                     'progress',
                                     'completed_lessons',
                                     'total_lessons'
                                 ],
                                 'created_at'
                             ]
                         ],
                         'enrollment',
                         'created_at',
                         'updated_at'
                     ],
                     'pagination'
                 ]);

        $this->assertEquals(200, $response->json('code'));
        $this->assertEquals('Learning path details retrieved successfully', $response->json('message'));
    }

    /** @test */
    public function student_cannot_access_learning_path_from_other_institution()
    {
        // Create learning path from another institution
        $otherInstitution = Institution::create([
            'name' => 'Other University',
            'slug' => 'other-university',
            'domain' => 'other.edu',
            'settings' => []
        ]);
        
        $otherLearningPath = LearningPath::create([
            'name' => 'Other Learning Path',
            'slug' => 'other-learning-path',
            'description' => 'A learning path from another institution',
            'institution_id' => $otherInstitution->id,
            'is_active' => 1
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson("/api/learning-paths/{$otherLearningPath->id}");

        $response->assertStatus(404)
                 ->assertJson([
                     'code' => 404,
                     'message' => 'Learning path not found or not accessible'
                 ]);
    }

    /** @test */
    public function student_can_enroll_in_learning_path()
    {
        Sanctum::actingAs($this->student);

        $response = $this->postJson("/api/learning-paths/{$this->learningPath->id}/enroll");

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'code',
                     'message',
                     'data' => [
                         'learning_path_id',
                         'user_id',
                         'enrolled_at',
                         'progress',
                         'status',
                         'courses_enrolled'
                     ],
                     'pagination'
                 ]);

        $this->assertEquals(201, $response->json('code'));
        $this->assertEquals('Successfully enrolled in learning path', $response->json('message'));

        // Verify enrollment was created
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->student->id,
            'learning_path_id' => $this->learningPath->id,
            'enrollment_status' => 'enrolled'
        ]);

        // Verify course enrollment was created
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_status' => 'enrolled'
        ]);
    }

    /** @test */
    public function student_cannot_enroll_twice_in_same_learning_path()
    {
        // First enrollment
        Enrollment::create([
            'user_id' => $this->student->id,
            'learning_path_id' => $this->learningPath->id,
            'progress' => 0,
            'enrollment_status' => 'enrolled'
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->postJson("/api/learning-paths/{$this->learningPath->id}/enroll");

        $response->assertStatus(400)
                 ->assertJson([
                     'code' => 400,
                     'message' => 'You are already enrolled in this learning path'
                 ]);
    }

    /** @test */
    public function student_can_get_their_learning_path_progress()
    {
        // Create enrollment
        Enrollment::create([
            'user_id' => $this->student->id,
            'learning_path_id' => $this->learningPath->id,
            'progress' => 25.5,
            'enrollment_status' => 'enrolled'
        ]);

        // Create course enrollment
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'progress' => 50.0,
            'enrollment_status' => 'in_progress'
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/learning-paths/progress/my');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'code',
                     'message',
                     'data' => [
                         '*' => [
                             'learning_path' => [
                                 'id',
                                 'name',
                                 'slug',
                                 'banner_url',
                                 'total_estimated_time',
                                 'courses_count'
                             ],
                             'enrollment' => [
                                 'enrolled_at',
                                 'progress',
                                 'status'
                             ],
                             'course_progress' => [
                                 '*' => [
                                     'course_id',
                                     'course_title',
                                     'progress',
                                     'completed_lessons',
                                     'total_lessons',
                                     'status'
                                 ]
                             ]
                         ]
                     ],
                     'pagination'
                 ]);

        $this->assertEquals(200, $response->json('code'));
        $this->assertEquals('Learning path progress retrieved successfully', $response->json('message'));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_learning_paths()
    {
        $response = $this->getJson('/api/learning-paths');

        $response->assertStatus(401);
    }

    /** @test */
    public function learning_path_list_supports_pagination()
    {
        // Create multiple learning paths
        for ($i = 1; $i <= 20; $i++) {
            LearningPath::create([
                'name' => "Learning Path {$i}",
                'slug' => "learning-path-{$i}",
                'description' => "Description for learning path {$i}",
                'institution_id' => $this->institution->id,
                'is_active' => 1
            ]);
        }

        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/learning-paths?per_page=5&page=2');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('pagination.current_page'));
        $this->assertEquals(5, $response->json('pagination.per_page'));
        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function inactive_learning_paths_are_not_shown()
    {
        // Create inactive learning path
        LearningPath::create([
            'name' => 'Inactive Learning Path',
            'slug' => 'inactive-learning-path',
            'description' => 'An inactive learning path',
            'institution_id' => $this->institution->id,
            'is_active' => 0
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/learning-paths');

        $response->assertStatus(200);
        // Should only see the active learning path
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->learningPath->id, $response->json('data.0.id'));
    }
}