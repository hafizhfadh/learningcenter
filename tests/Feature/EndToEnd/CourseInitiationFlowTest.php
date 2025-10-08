<?php

namespace Tests\Feature\EndToEnd;

use App\Models\Course;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseInitiationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_self_initiate_course(): void
    {
        $user = User::factory()->create();
        $learningPath = LearningPath::factory()->create([
            'slug' => 'fullstack-path',
        ]);

        $course = Course::factory()->create([
            'slug' => 'laravel-mastery',
            'is_published' => false,
        ]);

        $section = LessonSection::factory()->create([
            'course_id' => $course->id,
            'order_index' => 1,
        ]);

        $firstLesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'lesson_section_id' => $section->id,
            'order_index' => 1,
            'is_published' => true,
            'slug' => 'intro-lesson',
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('user.course.initiate', [$learningPath->slug, $course->slug]));

        $response->assertOk()
            ->assertJson([ 'success' => true ])
            ->assertJsonPath('course_info.slug', $course->slug)
            ->assertJsonPath('initiation_details.first_lesson_id', $firstLesson->id);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_status' => 'enrolled',
        ]);

        $this->assertDatabaseHas('progress_logs', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $firstLesson->id,
            'action' => 'course_started',
        ]);
    }
}
