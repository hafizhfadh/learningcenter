<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\LearningPath;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LearningPathCourse>
 */
class LearningPathCourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'learning_path_id' => LearningPath::factory(),
            'course_id' => Course::factory(),
            'order_index' => fake()->numberBetween(1, 10),
        ];
    }
}
