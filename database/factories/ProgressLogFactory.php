<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgressLog>
 */
class ProgressLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['viewed', 'completed', 'started', 'failed'];
        
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'lesson_id' => Lesson::factory(),
            'action' => fake()->randomElement($actions),
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
    
    /**
     * Configure the progress log to be a viewed action.
     */
    public function viewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'viewed',
        ]);
    }
    
    /**
     * Configure the progress log to be a completed action.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'completed',
        ]);
    }
    
    /**
     * Configure the progress log to be a started action.
     */
    public function started(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'started',
        ]);
    }
    
    /**
     * Configure the progress log to be a failed action.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'failed',
        ]);
    }
}
