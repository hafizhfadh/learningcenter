<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearningPathTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_learning_path_can_be_created(): void
    {
        $learningPath = LearningPath::factory()->create([
            'name' => 'Test Learning Path',
            'slug' => 'test-learning-path',
            'description' => 'This is a test learning path description.',
        ]);

        $this->assertDatabaseHas('learning_paths', [
            'name' => 'Test Learning Path',
            'slug' => 'test-learning-path',
            'description' => 'This is a test learning path description.',
        ]);

        $this->assertEquals('Test Learning Path', $learningPath->name);
        $this->assertEquals('test-learning-path', $learningPath->slug);
    }

    public function test_learning_path_slug_is_unique(): void
    {
        LearningPath::factory()->create(['slug' => 'unique-slug']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        LearningPath::factory()->create(['slug' => 'unique-slug']);
    }

    public function test_learning_path_can_have_courses(): void
    {
        $learningPath = LearningPath::factory()->create();
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();

        // Attach courses with order_index
        $learningPath->courses()->attach($course1->id, ['order_index' => 1]);
        $learningPath->courses()->attach($course2->id, ['order_index' => 2]);

        $this->assertEquals(2, $learningPath->courses()->count());
        
        // Test ordered courses
        $orderedCourses = $learningPath->courses()->orderBy('order_index')->get();
        $this->assertEquals($course1->id, $orderedCourses->first()->id);
        $this->assertEquals($course2->id, $orderedCourses->last()->id);
    }

    public function test_learning_path_courses_count_attribute(): void
    {
        $learningPath = LearningPath::factory()->create();
        $courses = Course::factory()->count(3)->create();

        foreach ($courses as $index => $course) {
            $learningPath->courses()->attach($course->id, ['order_index' => $index + 1]);
        }

        // Refresh to get the updated relationship
        $learningPath->refresh();
        
        $this->assertEquals(3, $learningPath->courses_count);
    }

    public function test_learning_path_banner_storage(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('banner.jpg', 800, 600);
        
        $learningPath = LearningPath::factory()->create([
            'banner' => 'learning-paths/banner.jpg'
        ]);

        $this->assertEquals('learning-paths/banner.jpg', $learningPath->banner);
    }

    public function test_learning_path_validation_rules(): void
    {
        // Test required name
        $this->expectException(\Illuminate\Database\QueryException::class);
        LearningPath::create([
            'slug' => 'test-slug',
            'description' => 'Test description'
        ]);
    }

    public function test_learning_path_soft_deletes(): void
    {
        $learningPath = LearningPath::factory()->create();
        $id = $learningPath->id;

        $learningPath->delete();

        // Should be soft deleted
        $this->assertSoftDeleted('learning_paths', ['id' => $id]);
        
        // Should not be found in normal queries
        $this->assertNull(LearningPath::find($id));
        
        // Should be found with trashed
        $this->assertNotNull(LearningPath::withTrashed()->find($id));
    }

    public function test_learning_path_factory_creates_valid_data(): void
    {
        $learningPath = LearningPath::factory()->create();

        $this->assertNotNull($learningPath->name);
        $this->assertNotNull($learningPath->slug);
        $this->assertNotNull($learningPath->description);
        $this->assertTrue(strlen($learningPath->name) >= 3);
        $this->assertTrue(strlen($learningPath->slug) >= 3);
    }
}
