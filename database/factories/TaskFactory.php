<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taskTypes = ['mcq', 'essay', 'file_upload'];
        
        return [
            'task_type' => fake()->randomElement($taskTypes),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraphs(2, true),
            'settings' => json_encode([
                'time_limit' => fake()->numberBetween(5, 60),
                'attempts_allowed' => fake()->numberBetween(1, 5),
                'passing_score' => fake()->numberBetween(60, 80),
            ]),
            'course_id' => Course::factory(),
            'lesson_id' => Lesson::factory(),
        ];
    }
    
    /**
     * Configure the task to be a multiple choice question type.
     */
    public function mcq(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_type' => 'mcq',
        ]);
    }
    
    /**
     * Configure the task to be an essay type.
     */
    public function essay(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_type' => 'essay',
        ]);
    }
    
    /**
     * Configure the task to be a file upload type.
     */
    public function fileUpload(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_type' => 'file_upload',
        ]);
    }
}
