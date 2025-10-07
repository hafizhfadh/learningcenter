<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonSection;
use App\Models\User;
use App\Services\LessonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LessonServiceTest extends TestCase
{
    use RefreshDatabase;

    private LessonService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->service = app(LessonService::class);
    }

    public function test_it_tracks_course_progress_and_navigation(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create([
            'slug' => 'laravel-mastery',
            'is_published' => true,
        ]);

        $firstSection = LessonSection::factory()->create([
            'course_id' => $course->id,
            'order_index' => 1,
        ]);

        $secondSection = LessonSection::factory()->create([
            'course_id' => $course->id,
            'order_index' => 2,
        ]);

        $firstLesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'lesson_section_id' => $firstSection->id,
            'order_index' => 1,
            'is_published' => true,
            'slug' => 'lesson-one',
        ]);

        $secondLesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'lesson_section_id' => $firstSection->id,
            'order_index' => 2,
            'is_published' => true,
            'slug' => 'lesson-two',
        ]);

        $thirdLesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'lesson_section_id' => $secondSection->id,
            'order_index' => 1,
            'is_published' => true,
            'slug' => 'lesson-three',
        ]);

        Enrollment::factory()->enrolled()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $courseWithLessons = $this->service->getCourseWithLessons($course->slug);

        $this->assertCount(2, $courseWithLessons->lessonSections);
        $this->assertSame($firstLesson->id, $courseWithLessons->lessonSections->first()->lessons->first()->id);

        $initialProgress = $this->service->calculateCourseProgress($courseWithLessons, $user->id);

        $this->assertSame(0, $initialProgress['completed_lessons']);
        $this->assertSame(3, $initialProgress['total_lessons']);
        $this->assertSame(0, (int) $initialProgress['progress_percentage']);

        $firstPublishedLesson = $this->service->getFirstLesson($courseWithLessons);
        $this->assertTrue($firstPublishedLesson->is($firstLesson));

        $nextLesson = $this->service->completeLesson($firstLesson, $courseWithLessons, $user->id);

        $this->assertTrue($nextLesson->is($secondLesson));
        $this->assertDatabaseHas('progress_logs', [
            'user_id' => $user->id,
            'lesson_id' => $firstLesson->id,
            'status' => 'completed',
        ]);

        $progressAfterFirstCompletion = $this->service->calculateCourseProgress($courseWithLessons, $user->id);

        $this->assertSame(1, $progressAfterFirstCompletion['completed_lessons']);
        $this->assertSame(3, $progressAfterFirstCompletion['total_lessons']);
        $this->assertSame(33, (int) $progressAfterFirstCompletion['progress_percentage']);

        $userProgress = $this->service->getUserProgress($courseWithLessons, $user->id);
        $this->assertTrue($userProgress->has($firstLesson->id));

        $finalLesson = $this->service->completeLesson($secondLesson, $courseWithLessons, $user->id);
        $this->assertTrue($finalLesson->is($thirdLesson));

        $previousLesson = $this->service->getPreviousLesson($thirdLesson, $courseWithLessons);
        $this->assertTrue($previousLesson->is($secondLesson));
    }
}
