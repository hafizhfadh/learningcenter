<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Course;
use App\Models\User;
use App\Models\Institution;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class CourseTeacherRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'school_teacher']);
        Role::create(['name' => 'student']);
        Role::create(['name' => 'super_admin']);
    }

    /** @test */
    public function course_can_have_multiple_teachers()
    {
        $course = Course::factory()->create();
        $teacher1 = User::factory()->create();
        $teacher2 = User::factory()->create();
        
        $teacher1->assignRole('school_teacher');
        $teacher2->assignRole('school_teacher');

        // Attach teachers to course
        $course->teachers()->attach($teacher1->id, ['assigned_at' => now()]);
        $course->teachers()->attach($teacher2->id, ['assigned_at' => now()]);

        $this->assertCount(2, $course->teachers);
        $this->assertTrue($course->teachers->contains($teacher1));
        $this->assertTrue($course->teachers->contains($teacher2));
    }

    /** @test */
    public function teacher_can_have_multiple_courses()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('school_teacher');
        
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();

        // Attach courses to teacher
        $teacher->courses()->attach($course1->id, ['assigned_at' => now()]);
        $teacher->courses()->attach($course2->id, ['assigned_at' => now()]);

        $this->assertCount(2, $teacher->courses);
        $this->assertTrue($teacher->courses->contains($course1));
        $this->assertTrue($teacher->courses->contains($course2));
    }

    /** @test */
    public function teachers_relationship_only_returns_users_with_teacher_role()
    {
        $course = Course::factory()->create();
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        
        $teacher->assignRole('school_teacher');
        $student->assignRole('student');

        // Attach both users to course (this should only happen through proper assignment)
        $course->teachers()->attach($teacher->id, ['assigned_at' => now()]);
        
        // Manually insert student into pivot table to test the role filter
        DB::table('course_teachers')->insert([
            'course_id' => $course->id,
            'teacher_id' => $student->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // The teachers() relationship should only return the actual teacher
        $courseTeachers = $course->teachers;
        $this->assertCount(1, $courseTeachers);
        $this->assertTrue($courseTeachers->contains($teacher));
        $this->assertFalse($courseTeachers->contains($student));
    }

    /** @test */
    public function course_teachers_pivot_table_stores_assigned_at_timestamp()
    {
        $course = Course::factory()->create();
        $teacher = User::factory()->create();
        $teacher->assignRole('school_teacher');

        $assignedAt = now()->subDays(5);
        $course->teachers()->attach($teacher->id, ['assigned_at' => $assignedAt]);

        $pivotData = $course->teachers()->first()->pivot;
        $this->assertNotNull($pivotData->assigned_at);
        // Convert to string for comparison since pivot data might be stored as string
        $this->assertEquals($assignedAt->format('Y-m-d H:i:s'), $pivotData->assigned_at);
    }

    /** @test */
    public function teacher_can_be_detached_from_course()
    {
        $course = Course::factory()->create();
        $teacher = User::factory()->create();
        $teacher->assignRole('school_teacher');

        // Attach teacher
        $course->teachers()->attach($teacher->id, ['assigned_at' => now()]);
        $this->assertCount(1, $course->teachers);

        // Detach teacher
        $course->teachers()->detach($teacher->id);
        
        // Refresh the relationship to get updated data
        $course->refresh();
        $this->assertCount(0, $course->teachers);
    }

    /** @test */
    public function unique_constraint_prevents_duplicate_course_teacher_assignments()
    {
        $course = Course::factory()->create();
        $teacher = User::factory()->create();
        $teacher->assignRole('school_teacher');

        // First attachment should work
        $course->teachers()->attach($teacher->id, ['assigned_at' => now()]);
        $this->assertCount(1, $course->teachers);

        // Second attachment should throw an exception due to unique constraint
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        $course->teachers()->attach($teacher->id, ['assigned_at' => now()]);
    }

    /** @test */
    public function teaching_courses_alias_method_works()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('school_teacher');
        
        $course = Course::factory()->create();
        $teacher->courses()->attach($course->id, ['assigned_at' => now()]);

        // Test that teachingCourses() returns the same as courses()
        $this->assertEquals($teacher->courses->count(), $teacher->teachingCourses->count());
        $this->assertTrue($teacher->teachingCourses->contains($course));
    }

    /** @test */
    public function course_teachers_relationship_includes_pivot_data()
    {
        $course = Course::factory()->create();
        $teacher = User::factory()->create();
        $teacher->assignRole('school_teacher');

        $assignedAt = now();
        $course->teachers()->attach($teacher->id, ['assigned_at' => $assignedAt]);

        $courseTeacher = $course->teachers()->first();
        
        $this->assertNotNull($courseTeacher->pivot);
        $this->assertNotNull($courseTeacher->pivot->assigned_at);
        $this->assertNotNull($courseTeacher->pivot->created_at);
        $this->assertNotNull($courseTeacher->pivot->updated_at);
    }

    /** @test */
    public function teachers_method_exists_and_is_callable()
    {
        $course = Course::factory()->create();
        
        $this->assertTrue(method_exists($course, 'teachers'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $course->teachers());
    }

    /** @test */
    public function courses_method_exists_on_user_model()
    {
        $user = User::factory()->create();
        
        $this->assertTrue(method_exists($user, 'courses'));
        $this->assertTrue(method_exists($user, 'teachingCourses'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->courses());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->teachingCourses());
    }
}