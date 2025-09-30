<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonSection;
use App\Models\ProgressLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SelfInitiationFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $course;
    protected $lesson;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);

        // Create test course
        $this->course = Course::factory()->create([
            'title' => 'Test Course',
            'slug' => 'test-course-slug',
            'description' => 'A test course for self-initiation',
            'is_published' => true
        ]);

        // Create lesson section
        $lessonSection = LessonSection::factory()->create([
            'course_id' => $this->course->id,
            'title' => 'Test Section',
            'order' => 1
        ]);

        // Create test lesson
        $this->lesson = Lesson::factory()->create([
            'course_id' => $this->course->id,
            'lesson_section_id' => $lessonSection->id,
            'title' => 'Test Lesson',
            'slug' => 'test-lesson',
            'order' => 1
        ]);
    }

    /** @test */
    public function user_can_self_initiate_course_with_auto_enrollment()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Make request to initiate course
        $response = $this->postJson(route('user.lesson.initiate', [
            'exam' => 'test-exam',
            'courseSlug' => $this->course->slug
        ]));

        // Assert successful response
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'redirect_url',
                    'course_info' => [
                        'id',
                        'title',
                        'slug',
                        'description'
                    ],
                    'initiation_details' => [
                        'auto_enrolled',
                        'first_lesson_id',
                        'first_lesson_title',
                        'progress_log_id',
                        'enrollment_id'
                    ]
                ]);

        // Verify auto-enrollment occurred
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'enrollment_status' => 'active'
        ]);

        // Verify progress log was created
        $this->assertDatabaseHas('progress_logs', [
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'action' => 'course_initiated'
        ]);

        // Verify response data
        $responseData = $response->json();
        $this->assertTrue($responseData['initiation_details']['auto_enrolled']);
        $this->assertEquals($this->course->id, $responseData['course_info']['id']);
        $this->assertEquals($this->lesson->id, $responseData['initiation_details']['first_lesson_id']);
    }

    /** @test */
    public function already_enrolled_user_can_initiate_course_without_duplicate_enrollment()
    {
        // Pre-enroll user
        $enrollment = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'enrollment_status' => 'active'
        ]);

        // Authenticate user
        $this->actingAs($this->user);

        // Make request to initiate course
        $response = $this->postJson(route('user.lesson.initiate', [
            'exam' => 'test-exam',
            'courseSlug' => $this->course->slug
        ]));

        // Assert successful response
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        // Verify no duplicate enrollment
        $enrollmentCount = Enrollment::where('user_id', $this->user->id)
                                   ->where('course_id', $this->course->id)
                                   ->count();
        $this->assertEquals(1, $enrollmentCount);

        // Verify response indicates no auto-enrollment
        $responseData = $response->json();
        $this->assertFalse($responseData['initiation_details']['auto_enrolled']);
    }

    /** @test */
    public function user_cannot_initiate_nonexistent_course()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Make request with invalid course slug
        $response = $this->postJson(route('user.lesson.initiate', [
            'exam' => 'test-exam',
            'courseSlug' => 'nonexistent-course'
        ]));

        // Assert error response
        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'error_type' => 'course_not_found'
                ]);
    }

    /** @test */
    public function user_cannot_initiate_unpublished_course()
    {
        // Create unpublished course
        $unpublishedCourse = Course::factory()->create([
            'title' => 'Unpublished Course',
            'slug' => 'unpublished-course',
            'is_published' => false
        ]);

        // Authenticate user
        $this->actingAs($this->user);

        // Make request to initiate unpublished course
        $response = $this->postJson(route('user.lesson.initiate', [
            'exam' => 'test-exam',
            'courseSlug' => $unpublishedCourse->slug
        ]));

        // Assert error response
        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error_type' => 'eligibility_failed'
                ]);
    }

    /** @test */
    public function invalid_course_slug_returns_validation_error()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Make request with invalid course slug (empty string)
        $response = $this->postJson(route('user.lesson.initiate', [
            'exam' => 'test-exam',
            'courseSlug' => ''
        ]));

        // Assert validation error
        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'error_type' => 'validation_error'
                ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_initiate_course()
    {
        // Make request without authentication
        $response = $this->postJson(route('user.lesson.initiate', [
            'exam' => 'test-exam',
            'courseSlug' => $this->course->slug
        ]));

        // Assert unauthorized response
        $response->assertStatus(401);
    }

    /** @test */
    public function course_initiation_creates_proper_progress_log()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Make request to initiate course
        $response = $this->postJson(route('user.lesson.initiate', [
            'exam' => 'test-exam',
            'courseSlug' => $this->course->slug
        ]));

        // Assert successful response
        $response->assertStatus(200);

        // Verify progress log details
        $progressLog = ProgressLog::where('user_id', $this->user->id)
                                 ->where('course_id', $this->course->id)
                                 ->where('action', 'course_initiated')
                                 ->first();

        $this->assertNotNull($progressLog);
        $this->assertEquals($this->lesson->id, $progressLog->lesson_id);
        $this->assertEquals('course_initiated', $progressLog->action);
        $this->assertNotNull($progressLog->created_at);
    }
}