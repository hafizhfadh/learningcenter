<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\ProgressLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseInitiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_initiate_course_successfully()
    {
        // Create test data
        /** @var User $user */
        $user = User::factory()->create();
        $course = Course::factory()->create([
            'slug' => 'introduction-to-html-and-css'
        ]);
        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'order_index' => 1
        ]);

        // Act as the user
        $this->actingAs($user);

        // Make the request
        $response = $this->postJson("/user/test-exam/{$course->slug}/initiate");

        // Assert response
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Course initiation tracked successfully'
                 ]);

        // Assert database
        $this->assertDatabaseHas('progress_logs', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'action' => 'initiated'
        ]);
    }

    public function test_course_initiation_prevents_duplicates()
    {
        // Create test data
        /** @var User $user */
        $user = User::factory()->create();
        $course = Course::factory()->create([
            'slug' => 'introduction-to-html-and-css'
        ]);
        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'order_index' => 1
        ]);

        // Create existing progress log
        ProgressLog::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'action' => 'initiated'
        ]);

        // Act as the user
        $this->actingAs($user);

        // Make the request
        $response = $this->postJson("/user/test-exam/{$course->slug}/initiate");

        // Assert response
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Course initiation already tracked'
                 ]);

        // Assert only one record exists
        $this->assertEquals(1, ProgressLog::where([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'action' => 'initiated'
        ])->count());
    }

    public function test_course_initiation_fails_for_nonexistent_course()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson("/user/nonexistent-exam/nonexistent-course/initiate");

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Course not found'
                 ]);
    }

    public function test_course_initiation_requires_authentication()
    {
        $course = Course::factory()->create([
            'slug' => 'introduction-to-html-and-css'
        ]);

        $response = $this->postJson("/user/test-exam/{$course->slug}/initiate");

        $response->assertStatus(401);
    }
}