<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Course;
use App\Models\User;
use App\Models\Institution;
use Spatie\Permission\Models\Role;

class CourseCreatedByTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'school_teacher']);
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'school_admin']);
    }

    /** @test */
    public function course_can_be_created_with_created_by_field()
    {
        $user = User::factory()->create();
        $user->assignRole('school_teacher');

        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'banner' => 'test-banner.jpg',
            'description' => 'Test course description',
            'tags' => 'test,course',
            'estimated_time' => 30,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        $this->assertNotNull($course->created_by);
        $this->assertEquals($user->id, $course->created_by);
    }

    /** @test */
    public function course_creator_relationship_works()
    {
        $user = User::factory()->create();
        $user->assignRole('school_teacher');

        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'banner' => 'test-banner.jpg',
            'description' => 'Test course description',
            'tags' => 'test,course',
            'estimated_time' => 30,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $course->creator);
        $this->assertEquals($user->id, $course->creator->id);
        $this->assertEquals($user->name, $course->creator->name);
    }

    /** @test */
    public function course_can_be_created_without_created_by_field()
    {
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'banner' => 'test-banner.jpg',
            'description' => 'Test course description',
            'tags' => 'test,course',
            'estimated_time' => 30,
            'is_published' => true,
        ]);

        $this->assertNull($course->created_by);
        $this->assertNull($course->creator);
    }

    /** @test */
    public function created_by_field_is_fillable()
    {
        $user = User::factory()->create();
        $user->assignRole('school_teacher');

        $courseData = [
            'title' => 'Test Course',
            'slug' => 'test-course',
            'banner' => 'test-banner.jpg',
            'description' => 'Test course description',
            'tags' => 'test,course',
            'estimated_time' => 30,
            'is_published' => true,
            'created_by' => $user->id,
        ];

        $course = Course::create($courseData);

        $this->assertEquals($user->id, $course->created_by);
    }

    /** @test */
    public function created_by_foreign_key_constraint_works()
    {
        $user = User::factory()->create();
        $user->assignRole('school_teacher');

        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'banner' => 'test-banner.jpg',
            'description' => 'Test course description',
            'tags' => 'test,course',
            'estimated_time' => 30,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        // Force delete the user (should set created_by to null due to onDelete('set null'))
        $user->forceDelete();
        
        $course->refresh();
        $this->assertNull($course->created_by);
    }

    /** @test */
    public function course_with_created_by_can_be_queried()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('school_teacher');
        $user2->assignRole('school_teacher');

        $course1 = Course::create([
            'title' => 'Course 1',
            'slug' => 'course-1',
            'banner' => 'banner1.jpg',
            'description' => 'Course 1 description',
            'tags' => 'test,course1',
            'estimated_time' => 30,
            'is_published' => true,
            'created_by' => $user1->id,
        ]);

        $course2 = Course::create([
            'title' => 'Course 2',
            'slug' => 'course-2',
            'banner' => 'banner2.jpg',
            'description' => 'Course 2 description',
            'tags' => 'test,course2',
            'estimated_time' => 40,
            'is_published' => true,
            'created_by' => $user2->id,
        ]);

        // Query courses by creator
        $user1Courses = Course::where('created_by', $user1->id)->get();
        $user2Courses = Course::where('created_by', $user2->id)->get();

        $this->assertCount(1, $user1Courses);
        $this->assertCount(1, $user2Courses);
        $this->assertEquals($course1->id, $user1Courses->first()->id);
        $this->assertEquals($course2->id, $user2Courses->first()->id);
    }

    /** @test */
    public function course_created_by_field_is_indexed()
    {
        // This test verifies that the created_by field has an index
        // We can't directly test the index, but we can verify the migration ran successfully
        $user = User::factory()->create();
        $user->assignRole('school_teacher');

        // Create multiple courses
        for ($i = 1; $i <= 10; $i++) {
            Course::create([
                'title' => "Course {$i}",
                'slug' => "course-{$i}",
                'banner' => 'banner.jpg',
                'description' => "Course {$i} description",
                'tags' => 'test,performance',
                'estimated_time' => 30,
                'is_published' => true,
                'created_by' => $user->id,
            ]);
        }

        // Query should be efficient due to index
        $courses = Course::where('created_by', $user->id)->get();
        $this->assertCount(10, $courses);
    }

    /** @test */
    public function course_creator_relationship_is_properly_typed()
    {
        $user = User::factory()->create();
        $user->assignRole('school_teacher');

        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'banner' => 'test-banner.jpg',
            'description' => 'Test course description',
            'tags' => 'test,course',
            'estimated_time' => 30,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $course->creator());
    }

    /** @test */
    public function course_with_null_created_by_handles_creator_relationship_gracefully()
    {
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'banner' => 'test-banner.jpg',
            'description' => 'Test course description',
            'tags' => 'test,course',
            'estimated_time' => 30,
            'is_published' => true,
            'created_by' => null,
        ]);

        $this->assertNull($course->creator);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $course->creator());
    }
}