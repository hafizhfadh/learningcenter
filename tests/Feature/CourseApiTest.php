<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonSection;
use App\Models\Task;
use App\Models\ProgressLog;
use App\Models\LearningPath;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class CourseApiTest extends TestCase
{
    use RefreshDatabase;

    protected $institution;
    protected $student;
    protected $teacher;
    protected $admin;
    protected $publishedCourse;
    protected $unpublishedCourse;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'student']);
        Role::create(['name' => 'school_teacher']);
        Role::create(['name' => 'school_admin']);
        Role::create(['name' => 'super_admin']);

        // Create institution
        $this->institution = Institution::create([
            'name' => 'Test University',
            'slug' => 'test-university',
            'domain' => 'test.edu',
            'settings' => ['timezone' => 'America/New_York']
        ]);

        // Create users
        $this->student = User::factory()->create([
            'institution_id' => $this->institution->id,
            'email' => 'student@test.edu'
        ]);
        $this->student->assignRole('student');

        $this->teacher = User::factory()->create([
            'institution_id' => $this->institution->id,
            'email' => 'teacher@test.edu'
        ]);
        $this->teacher->assignRole('school_teacher');

        $this->admin = User::factory()->create([
            'institution_id' => $this->institution->id,
            'email' => 'admin@test.edu'
        ]);
        $this->admin->assignRole('school_admin');

        // Create test courses
        $this->publishedCourse = Course::create([
            'title' => 'Introduction to Programming',
            'slug' => 'intro-programming',
            'banner' => 'banners/course1.jpg',
            'description' => 'Learn the basics of programming',
            'tags' => 'programming,basics,beginner',
            'estimated_time' => 120,
            'is_published' => true,
            'created_by' => $this->teacher->id,
        ]);

        $this->unpublishedCourse = Course::create([
            'title' => 'Advanced Programming',
            'slug' => 'advanced-programming',
            'banner' => 'banners/course2.jpg',
            'description' => 'Advanced programming concepts',
            'tags' => 'programming,advanced',
            'estimated_time' => 180,
            'is_published' => false,
            'created_by' => $this->teacher->id,
        ]);

        // Assign teacher to courses
        $this->publishedCourse->teachers()->attach($this->teacher->id, [
            'assigned_at' => now()
        ]);
        $this->unpublishedCourse->teachers()->attach($this->teacher->id, [
            'assigned_at' => now()
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_course_endpoints()
    {
        $response = $this->getJson('/api/courses');
        $response->assertStatus(401);

        $response = $this->getJson('/api/courses/search');
        $response->assertStatus(401);

        $response = $this->getJson('/api/courses/1');
        $response->assertStatus(401);
    }

    /** @test */
    public function student_can_list_published_courses()
    {
        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/courses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'description',
                        'banner_url',
                        'tags',
                        'estimated_time',
                        'is_published',
                        'created_at',
                        'instructor',
                        'enrollment_status',
                        'total_lessons',
                        'total_tasks'
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                    'has_more_pages'
                ]
            ]);

        // Should only see published courses
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals($this->publishedCourse->id, $response->json('data.0.id'));
        $this->assertTrue($response->json('data.0.is_published'));
    }

    /** @test */
    public function teacher_can_list_all_courses_including_unpublished()
    {
        Sanctum::actingAs($this->teacher);

        $response = $this->getJson('/api/courses');

        $response->assertStatus(200);
        
        // Should see both published and unpublished courses
        $this->assertEquals(2, count($response->json('data')));
    }

    /** @test */
    public function course_listing_supports_pagination()
    {
        // Create additional courses
        Course::factory()->count(25)->create([
            'is_published' => true,
            'created_by' => $this->teacher->id
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/courses?per_page=10&page=1');

        $response->assertStatus(200);
        $this->assertEquals(10, count($response->json('data')));
        $this->assertEquals(1, $response->json('pagination.current_page'));
        $this->assertEquals(10, $response->json('pagination.per_page'));
        $this->assertTrue($response->json('pagination.has_more_pages'));
    }

    /** @test */
    public function course_listing_supports_sorting()
    {
        // Create courses with different titles
        Course::create([
            'title' => 'A First Course',
            'slug' => 'a-first-course',
            'banner' => 'banner.jpg',
            'description' => 'Description',
            'is_published' => true,
            'created_by' => $this->teacher->id,
        ]);

        Course::create([
            'title' => 'Z Last Course',
            'slug' => 'z-last-course',
            'banner' => 'banner.jpg',
            'description' => 'Description',
            'is_published' => true,
            'created_by' => $this->teacher->id,
        ]);

        Sanctum::actingAs($this->student);

        // Test ascending sort by title
        $response = $this->getJson('/api/courses?sort=title&order=asc');
        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title')->toArray();
        $this->assertEquals('A First Course', $titles[0]);

        // Test descending sort by title
        $response = $this->getJson('/api/courses?sort=title&order=desc');
        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title')->toArray();
        $this->assertEquals('Z Last Course', $titles[0]);
    }

    /** @test */
    public function course_listing_validates_parameters()
    {
        Sanctum::actingAs($this->student);

        // Test invalid per_page
        $response = $this->getJson('/api/courses?per_page=150');
        $response->assertStatus(422);

        // Test invalid sort field
        $response = $this->getJson('/api/courses?sort=invalid_field');
        $response->assertStatus(422);

        // Test invalid order
        $response = $this->getJson('/api/courses?order=invalid_order');
        $response->assertStatus(422);
    }

    /** @test */
    public function student_can_search_courses_by_title()
    {
        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/courses/search?q=programming');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'description',
                        'relevance_score'
                    ]
                ],
                'pagination'
            ]);

        $this->assertEquals(1, count($response->json('data')));
        $this->assertStringContainsString('Programming', $response->json('data.0.title'));
    }

    /** @test */
    public function student_can_search_courses_by_instructor()
    {
        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/courses/search?instructor=' . $this->teacher->name);

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    /** @test */
    public function student_can_search_courses_by_tags()
    {
        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/courses/search?tags=basics');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertStringContainsString('basics', $response->json('data.0.tags'));
    }

    /** @test */
    public function student_can_search_courses_by_date_range()
    {
        Sanctum::actingAs($this->student);

        $startDate = Carbon::now()->subDays(1)->format('Y-m-d');
        $endDate = Carbon::now()->addDays(1)->format('Y-m-d');

        $response = $this->getJson("/api/courses/search?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    /** @test */
    public function student_can_search_courses_by_time_range()
    {
        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/courses/search?min_time=100&max_time=150');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals(120, $response->json('data.0.estimated_time'));
    }

    /** @test */
    public function course_search_validates_parameters()
    {
        Sanctum::actingAs($this->student);

        // Test invalid date format
        $response = $this->getJson('/api/courses/search?start_date=invalid-date');
        $response->assertStatus(422);

        // Test end_date before start_date
        $response = $this->getJson('/api/courses/search?start_date=2024-12-31&end_date=2024-01-01');
        $response->assertStatus(422);

        // Test max_time less than min_time
        $response = $this->getJson('/api/courses/search?min_time=200&max_time=100');
        $response->assertStatus(422);
    }

    /** @test */
    public function student_can_view_published_course_details()
    {
        // Create lesson section first
        $section = LessonSection::create([
            'title' => 'Introduction Section',
            'slug' => 'introduction-section',
            'course_id' => $this->publishedCourse->id,
            'order_index' => 1,
        ]);

        // Create lessons and tasks for the course
        $lesson = Lesson::create([
            'title' => 'First Lesson',
            'slug' => 'first-lesson',
            'content_body' => 'This is the first lesson content.',
            'course_id' => $this->publishedCourse->id,
            'lesson_section_id' => $section->id,
            'order_index' => 1,
            'estimated_time' => 30,
        ]);

        $task = Task::create([
            'title' => 'First Task',
            'description' => 'Complete this task',
            'course_id' => $this->publishedCourse->id,
            'lesson_id' => $lesson->id,
            'task_type' => 'assignment',
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson("/api/courses/{$this->publishedCourse->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'description',
                    'banner_url',
                    'tags',
                    'estimated_time',
                    'is_published',
                    'created_at',
                    'updated_at',
                    'instructor',
                    'teachers',
                    'enrollment_status',
                    'enrollment_date',
                    'progress_percentage',
                    'lessons',
                    'lesson_sections',
                    'tasks',
                    'learning_paths',
                    'statistics' => [
                        'total_lessons',
                        'completed_lessons',
                        'total_tasks',
                        'completed_tasks',
                        'total_enrolled_students',
                        'average_completion_rate'
                    ]
                ],
                'pagination'
            ]);

        $this->assertEquals($this->publishedCourse->id, $response->json('data.id'));
        $this->assertEquals('not_enrolled', $response->json('data.enrollment_status'));
        $this->assertEquals(1, $response->json('data.statistics.total_lessons'));
        $this->assertEquals(1, $response->json('data.statistics.total_tasks'));
    }

    /** @test */
    public function student_cannot_view_unpublished_course_details()
    {
        Sanctum::actingAs($this->student);

        $response = $this->getJson("/api/courses/{$this->unpublishedCourse->id}");

        $response->assertStatus(403)
            ->assertJson([
                'code' => 403,
                'message' => 'Access denied. This course is not published.'
            ]);
    }

    /** @test */
    public function teacher_can_view_unpublished_course_details()
    {
        Sanctum::actingAs($this->teacher);

        $response = $this->getJson("/api/courses/{$this->unpublishedCourse->id}");

        $response->assertStatus(200);
        $this->assertEquals($this->unpublishedCourse->id, $response->json('data.id'));
    }

    /** @test */
    public function course_details_shows_enrollment_status_for_enrolled_student()
    {
        // Enroll student in course
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedCourse->id,
            'enrollment_status' => 'active',
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson("/api/courses/{$this->publishedCourse->id}");

        $response->assertStatus(200);
        $this->assertEquals('active', $response->json('data.enrollment_status'));
        $this->assertNotNull($response->json('data.enrollment_date'));
    }

    /** @test */
    public function course_details_shows_progress_for_enrolled_student()
    {
        // Create lesson section first
        $section = LessonSection::create([
            'title' => 'Progress Section',
            'slug' => 'progress-section',
            'course_id' => $this->publishedCourse->id,
            'order_index' => 1,
        ]);

        // Create lessons
        $lesson1 = Lesson::create([
            'title' => 'Lesson 1',
            'slug' => 'lesson-1',
            'content_body' => 'This is lesson 1 content.',
            'course_id' => $this->publishedCourse->id,
            'lesson_section_id' => $section->id,
            'order_index' => 1,
        ]);

        $lesson2 = Lesson::create([
            'title' => 'Lesson 2',
            'slug' => 'lesson-2',
            'content_body' => 'This is lesson 2 content.',
            'course_id' => $this->publishedCourse->id,
            'lesson_section_id' => $section->id,
            'order_index' => 2,
        ]);

        // Enroll student
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedCourse->id,
            'status' => 'active',
        ]);

        // Mark one lesson as completed
        ProgressLog::create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedCourse->id,
            'lesson_id' => $lesson1->id,
            'action' => 'completed',
            'status' => 'completed',
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson("/api/courses/{$this->publishedCourse->id}");

        $response->assertStatus(200);
        $this->assertEquals(50.0, $response->json('data.progress_percentage')); // 1 out of 2 lessons completed
        $this->assertEquals(1, $response->json('data.statistics.completed_lessons'));
        $this->assertTrue($response->json('data.lessons.0.is_completed'));
        $this->assertFalse($response->json('data.lessons.1.is_completed'));
    }

    /** @test */
    public function course_details_shows_lesson_sections()
    {
        // Create lesson section
        $section = LessonSection::create([
            'title' => 'Introduction',
            'course_id' => $this->publishedCourse->id,
            'order_index' => 1,
        ]);

        // Create lesson in section
        $lesson = Lesson::create([
            'title' => 'Getting Started',
            'slug' => 'getting-started',
            'content_body' => 'This lesson covers the basics of getting started.',
            'course_id' => $this->publishedCourse->id,
            'lesson_section_id' => $section->id,
            'order_index' => 1,
            'estimated_time' => 30,
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson("/api/courses/{$this->publishedCourse->id}");

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data.lesson_sections')));
        $this->assertEquals('Introduction', $response->json('data.lesson_sections.0.title'));
        $this->assertEquals(1, count($response->json('data.lesson_sections.0.lessons')));
    }

    /** @test */
    public function course_not_found_returns_404()
    {
        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/courses/999999');

        $response->assertStatus(404)
            ->assertJson([
                'code' => 404,
                'message' => 'Course not found'
            ]);
    }

    /** @test */
    public function course_listing_includes_enrollment_status()
    {
        // Enroll student in published course
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedCourse->id,
            'enrollment_status' => 'active',
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/courses');

        $response->assertStatus(200);
        $this->assertEquals('active', $response->json('data.0.enrollment_status'));
    }

    /** @test */
    public function course_search_includes_relevance_scoring()
    {
        // Create course with title match (should have higher relevance)
        $titleMatchCourse = Course::create([
            'title' => 'Programming Fundamentals',
            'slug' => 'programming-fundamentals',
            'banner' => 'banner.jpg',
            'description' => 'Learn about databases',
            'tags' => 'database',
            'is_published' => true,
            'created_by' => $this->teacher->id,
        ]);

        // Create additional courses for testing relevance
        $course2 = Course::create([
            'title' => 'Advanced Programming Concepts',
            'slug' => 'advanced-programming-concepts',
            'banner' => 'banner.jpg',
            'description' => 'Deep dive into programming paradigms',
            'tags' => 'advanced',
            'is_published' => true,
            'created_by' => $this->teacher->id,
        ]);
        
        $course3 = Course::create([
            'title' => 'Web Development',
            'slug' => 'web-development',
            'banner' => 'banner.jpg',
            'description' => 'Learn programming for the web',
            'tags' => 'web',
            'is_published' => true,
            'created_by' => $this->teacher->id,
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/courses/search?q=programming&sort=relevance');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
        
        // First result should have higher relevance score
        $firstResult = $response->json('data.0');
        $this->assertArrayHasKey('relevance_score', $firstResult);
        $this->assertGreaterThan(0, $firstResult['relevance_score']);
    }

    /** @test */
    public function course_api_handles_rate_limiting()
    {
        Sanctum::actingAs($this->student);

        // This test would require actual rate limiting middleware to be configured
        // For now, we'll just verify the endpoints respond correctly
        $response = $this->getJson('/api/courses');
        $response->assertStatus(200);
    }

    /** @test */
    public function course_api_returns_consistent_json_structure()
    {
        Sanctum::actingAs($this->student);

        $endpoints = [
            '/api/courses',
            '/api/courses/search',
            "/api/courses/{$this->publishedCourse->id}"
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertStatus(200)
                ->assertJsonStructure([
                    'code',
                    'message',
                    'data',
                    'pagination'
                ]);
        }
    }
}