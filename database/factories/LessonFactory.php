<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lessonTypes = ['video', 'pages', 'quiz'];
        $title = fake()->sentence(4);
        
        return [
            'lesson_type' => fake()->randomElement($lessonTypes),
            'lesson_banner' => 'lessons/default-lesson-banner.jpg', // Default banner path
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . fake()->numberBetween(1000, 9999),
            'content_body' => fake()->paragraphs(5, true),
            'order_index' => fake()->numberBetween(1, 20),
            'course_id' => Course::factory(),
            'lesson_section_id' => function (array $attributes) {
                // Create a lesson section for the course if one doesn't exist
                return \App\Models\LessonSection::factory()->create([
                    'course_id' => $attributes['course_id'],
                ]);
            },
        ];
    }
    
    /**
     * Configure the lesson to be a video type.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'lesson_type' => 'video',
        ]);
    }
    
    /**
     * Configure the lesson to be a document type.
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'lesson_type' => 'document',
        ]);
    }
    
    /**
     * Configure the lesson to be a quiz type.
     */
    public function quiz(): static
    {
        return $this->state(fn (array $attributes) => [
            'lesson_type' => 'quiz',
        ]);
    }
}
