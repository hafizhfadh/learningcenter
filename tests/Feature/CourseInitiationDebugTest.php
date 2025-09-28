<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseInitiationDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_course_creation_and_lookup()
    {
        // Disable exception handling to see actual errors
        $this->withoutExceptionHandling();
        
        // Clear cache first
        \Illuminate\Support\Facades\Cache::flush();
        
        // Create test data step by step
        /** @var User $user */
        $user = User::factory()->create();
        
        $course = Course::factory()->create([
            'slug' => 'test-course-slug',
            'is_published' => 1
        ]);
        
        $lessonSection = LessonSection::factory()->create([
            'course_id' => $course->id,
            'order_index' => 1
        ]);
        
        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'lesson_section_id' => $lessonSection->id,
            'order_index' => 1,
            'is_published' => true
        ]);

        // Create enrollment for the user
        $enrollment = \App\Models\Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_status' => 'enrolled'
        ]);

        // Debug: Check if course exists
        $foundCourse = Course::where('slug', 'test-course-slug')->first();
        $this->assertNotNull($foundCourse, 'Course should exist in database');
        dump('Found course: ' . $foundCourse->id . ' - ' . $foundCourse->slug);
        
        // Debug: Check if lesson exists
        $foundLesson = Lesson::where('course_id', $course->id)->first();
        $this->assertNotNull($foundLesson, 'Lesson should exist in database');
        dump('Found lesson: ' . $foundLesson->id . ' for course: ' . $foundLesson->course_id);

        // Debug: Check if enrollment exists
        $foundEnrollment = \App\Models\Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->first();
        $this->assertNotNull($foundEnrollment, 'Enrollment should exist in database');
        dump('Found enrollment: ' . $foundEnrollment->id . ' for user: ' . $foundEnrollment->user_id);

        // Debug: Test the service method directly
        try {
            $lessonService = app(\App\Services\LessonService::class);
            $serviceResult = $lessonService->getCourseWithLessons('test-course-slug');
            dump('Service found course: ' . $serviceResult->id);
        } catch (\Exception $e) {
            dump('Service error: ' . $e->getMessage());
        }

        // Act as the user and start session
        $this->actingAs($user);

        // Try to test the controller method directly instead of through HTTP
        $lessonController = app(\App\Http\Controllers\User\LessonController::class);
        
        try {
            // Call the initiateCourse method directly
            $response = $lessonController->initiateCourse('test-exam', 'test-course-slug');
            dump('Direct controller call response:', $response->getData());
        } catch (\Exception $e) {
            dump('Direct controller call error:', $e->getMessage());
        }
        
        // Also try with proper headers for tenant
        $response = $this->withHeaders([
            'X-Tenant-Domain' => 'learningcenter.local',
            'X-Tenant-Subdomain' => 'test',
        ])->post("/user/test-exam/test-course-slug/initiate", [
            '_token' => csrf_token()
        ]);

        // Debug the response
        if ($response->status() !== 200) {
            dump('Response status: ' . $response->status());
            dump('Response content: ' . $response->getContent());
        }

        // Assert response
        $response->assertStatus(200);
    }
}